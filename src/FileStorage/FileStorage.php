<?php

namespace Drupal\dmf_core\FileStorage;

use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\FileStorage\FileStorageInterface;
use DigitalMarketingFramework\Core\Log\LoggerAwareInterface;
use DigitalMarketingFramework\Core\Log\LoggerAwareTrait;
use DigitalMarketingFramework\Core\Model\Data\Value\FileValue;
use DigitalMarketingFramework\Core\Model\Data\Value\FileValueInterface;
use Drupal\Core\File\Exception\DirectoryNotReadyException;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use ValueError;

/**
 * Drupal implementation of FileStorageInterface.
 *
 * Uses Drupal's FileSystemInterface for all operations to ensure proper
 * stream wrapper support, error handling, and future compatibility.
 * Supports Drupal stream wrappers (private://, public://, temporary://).
 */
class FileStorage implements FileStorageInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        protected FileSystemInterface $fileSystem,
        protected FileUrlGeneratorInterface $fileUrlGenerator,
        protected MimeTypeGuesserInterface $mimeTypeGuesser,
    ) {
    }

    /**
     * Normalize file identifier.
     *
     * Ensures consistent path formatting by removing trailing slashes.
     */
    protected function normalizePath(string $identifier): string
    {
        return rtrim($identifier, '/');
    }

    public function getFileContents(string $fileIdentifier): ?string
    {
        $path = $this->normalizePath($fileIdentifier);
        if (!$this->fileExists($fileIdentifier)) {
            $this->logger->warning(sprintf('File %s does not exist.', $fileIdentifier));

            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->logger->warning(sprintf('Could not read file %s.', $fileIdentifier));

            return null;
        }

        return $contents;
    }

    public function putFileContents(string $fileIdentifier, string $fileContent): void
    {
        $path = $this->normalizePath($fileIdentifier);

        // Ensure parent directory exists using Drupal's dirname
        $directory = $this->fileSystem->dirname($path);
        if (!$this->folderExists($directory)) {
            $this->createFolder($directory);
        }

        // Write file
        $result = file_put_contents($path, $fileContent);

        if ($result === false) {
            throw new DigitalMarketingFrameworkException(sprintf('Failed to write file %s', $fileIdentifier), 1732020001);
        }
    }

    public function deleteFile(string $fileIdentifier): void
    {
        $path = $this->normalizePath($fileIdentifier);
        if ($this->fileExists($fileIdentifier)) {
            try {
                $this->fileSystem->delete($path);
            } catch (FileException $e) {
                $this->logger->warning(sprintf('Failed to delete file %s: %s', $fileIdentifier, $e->getMessage()));
            }
        }
    }

    public function getFileName(string $fileIdentifier): ?string
    {
        $path = $this->normalizePath($fileIdentifier);
        if (!$this->fileExists($fileIdentifier)) {
            return null;
        }

        return basename($path);
    }

    public function getFileBaseName(string $fileIdentifier): ?string
    {
        $fileName = $this->getFileName($fileIdentifier);
        if ($fileName === null) {
            return null;
        }

        // Use pathinfo on the filename (not the full path with stream wrapper)
        return pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function getFileExtension(string $fileIdentifier): ?string
    {
        $fileName = $this->getFileName($fileIdentifier);
        if ($fileName === null) {
            return null;
        }

        // Use pathinfo on the filename (not the full path with stream wrapper)
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return $extension !== '' ? $extension : null;
    }

    public function fileExists(string $fileIdentifier): bool
    {
        $path = $this->normalizePath($fileIdentifier);

        return file_exists($path) && is_file($path);
    }

    public function fileIsReadOnly(string $fileIdentifier): bool
    {
        $path = $this->normalizePath($fileIdentifier);
        if (!$this->fileExists($fileIdentifier)) {
            return false;
        }

        return !is_writable($path);
    }

    public function fileIsWriteable(string $fileIdentifier): bool
    {
        return !$this->fileIsReadOnly($fileIdentifier);
    }

    public function getFilesFromFolder(string $folderIdentifier): array
    {
        $path = $this->normalizePath($folderIdentifier);
        if (!$this->folderExists($folderIdentifier)) {
            return [];
        }

        $files = [];

        // Use Drupal's scanDirectory for better stream wrapper support
        try {
            // scanDirectory returns array of objects with 'uri' property
            $results = $this->fileSystem->scanDirectory($path, '/.*/', ['recurse' => false]);
            foreach ($results as $file) {
                if (is_file($file->uri)) {
                    $files[] = $file->uri;
                }
            }
        } catch (NotRegularDirectoryException $e) {
            $this->logger->warning(sprintf('Error scanning folder %s: %s', $folderIdentifier, $e->getMessage()));

            return [];
        }

        return $files;
    }

    public function folderExists(string $folderIdentifier): bool
    {
        $path = $this->normalizePath($folderIdentifier);

        return file_exists($path) && is_dir($path);
    }

    public function createFolder(string $folderIdentifier): void
    {
        $path = $this->normalizePath($folderIdentifier);
        if (!$this->folderExists($folderIdentifier)) {
            try {
                // Use Drupal's prepareDirectory which handles recursive creation
                // and sets proper permissions
                $result = $this->fileSystem->prepareDirectory(
                    $path,
                    FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
                );

                if (!$result) {
                    throw new DigitalMarketingFrameworkException(sprintf('Failed to create folder %s', $folderIdentifier), 1732020002);
                }
            } catch (DirectoryNotReadyException $e) {
                throw new DigitalMarketingFrameworkException(sprintf('Error creating folder %s: %s', $folderIdentifier, $e->getMessage()), 1732020003, $e);
            }
        }
    }

    public function copyFileToFolder(string $fileIdentifier, string $folderIdentifier): string
    {
        $sourcePath = $this->normalizePath($fileIdentifier);
        $targetFolder = $this->normalizePath($folderIdentifier);

        if (!$this->fileExists($fileIdentifier)) {
            throw new DigitalMarketingFrameworkException(sprintf('Source file "%s" not found', $fileIdentifier), 1732020004);
        }

        if (!$this->folderExists($folderIdentifier)) {
            throw new DigitalMarketingFrameworkException(sprintf('Target folder "%s" not found', $folderIdentifier), 1732020005);
        }

        $fileName = $this->getFileName($fileIdentifier);
        $targetPath = $targetFolder . '/' . $fileName;

        try {
            $this->fileSystem->copy($sourcePath, $targetPath, FileExists::Replace);

            return $targetPath;
        } catch (FileException|ValueError $e) {
            throw new DigitalMarketingFrameworkException(sprintf('Error copying file: %s', $e->getMessage()), 1732020007, $e);
        }
    }

    public function getPublicUrl(string $fileIdentifier): string
    {
        $path = $this->normalizePath($fileIdentifier);
        if (!$this->fileExists($fileIdentifier)) {
            return '';
        }

        // Use Drupal's FileUrlGenerator (Drupal 10+)
        try {
            return $this->fileUrlGenerator->generateAbsoluteString($path);
        } catch (InvalidStreamWrapperException $e) {
            $this->logger->warning(sprintf('Failed to generate URL for %s: %s', $fileIdentifier, $e->getMessage()));

            return '';
        }
    }

    public function getMimeType(string $fileIdentifier): string
    {
        $path = $this->normalizePath($fileIdentifier);
        if (!$this->fileExists($fileIdentifier)) {
            return '';
        }

        try {
            // Use Drupal's MIME type guesser service
            return $this->mimeTypeGuesser->guessMimeType($path);
        } catch (InvalidArgumentException|LogicException $e) {
            $this->logger->warning(sprintf('Failed to determine MIME type for %s: %s', $fileIdentifier, $e->getMessage()));

            return '';
        }
    }

    public function getFileValue(string $fileIdentifier): ?FileValueInterface
    {
        if (!$this->fileExists($fileIdentifier)) {
            return null;
        }

        $fileName = $this->getFileName($fileIdentifier);
        if ($fileName === null) {
            return null;
        }

        return new FileValue(
            $fileIdentifier,
            $fileName,
            $this->getPublicUrl($fileIdentifier),
            $this->getMimeType($fileIdentifier)
        );
    }

    public function getTempPath(): string
    {
        // Use Drupal's getTempDirectory method
        return $this->fileSystem->getTempDirectory();
    }

    public function writeTempFile(string $filePrefix = '', string $fileContent = '', string $fileSuffix = ''): string|bool
    {
        // Use Drupal's tempnam for proper temp file creation
        $tempFile = $this->fileSystem->tempnam($this->getTempPath(), $filePrefix);

        if ($tempFile === false) {
            $this->logger->warning('Failed to create temp file');

            return false;
        }

        // Add suffix if provided by renaming
        if ($fileSuffix !== '') {
            $newTempFile = $tempFile . $fileSuffix;
            if (!rename($tempFile, $newTempFile)) {
                $this->logger->warning(sprintf('Failed to rename temp file to add suffix %s', $fileSuffix));

                return false;
            }

            $tempFile = $newTempFile;
        }

        // Write content
        $result = file_put_contents($tempFile, $fileContent);

        if ($result === false) {
            $this->logger->warning(sprintf('Failed to write temp file %s', $tempFile));

            return false;
        }

        return $tempFile;
    }
}
