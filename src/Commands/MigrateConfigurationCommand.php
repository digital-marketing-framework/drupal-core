<?php

namespace Drupal\dmf_core\Commands;

use DigitalMarketingFramework\Core\ConfigurationDocument\ConfigurationDocumentMaintenanceServiceInterface;
use DigitalMarketingFramework\Core\Model\ConfigurationDocument\DataSourceMigratable;
use DigitalMarketingFramework\Core\Model\ConfigurationDocument\MigratableInterface;
use DigitalMarketingFramework\Core\SchemaDocument\SchemaDocument;
use Drupal\dmf_core\Registry\RegistryCollection;
use Drush\Commands\DrushCommands;
use Exception;

/**
 * Drush command to migrate all configuration documents.
 */
class MigrateConfigurationCommand extends DrushCommands
{
    /**
     * Constructs a MigrateConfigurationCommand object.
     *
     * @param RegistryCollection $registryCollection
     *   The registry collection
     */
    public function __construct(
        protected RegistryCollection $registryCollection,
    ) {
        parent::__construct();
    }

    /**
     * Migrate all outdated configuration documents.
     *
     * @param string|null $identifier
     *   Optional: migrate only the document with this identifier
     * @param array<string,mixed> $options
     *   Command options
     *
     * @command anyrel:migrate
     *
     * @aliases anyrel-migrate
     *
     * @option dry-run Show what would be migrated without making changes.
     *
     * @usage anyrel:migrate
     *   Migrate all outdated configuration documents.
     * @usage anyrel:migrate --dry-run
     *   Show what would be migrated without making changes.
     * @usage anyrel:migrate my-document
     *   Migrate only the document with identifier "my-document".
     */
    public function migrate(?string $identifier = null, array $options = ['dry-run' => false]): int
    {
        $schemaDocument = $this->registryCollection->getConfigurationSchemaDocument();
        $dryRun = (bool)$options['dry-run'];

        $this->displaySchemaVersions($schemaDocument);

        $maintenanceService = $this->registryCollection->getConfigurationDocumentMaintenanceService();

        if ($identifier !== null) {
            return $this->executeSingleDocument($maintenanceService, $schemaDocument, $identifier, $dryRun);
        }

        $migratables = $maintenanceService->getAllMigratables($schemaDocument);

        return $this->executeAll($maintenanceService, $schemaDocument, $migratables, $dryRun);
    }

    protected function displaySchemaVersions(SchemaDocument $schemaDocument): void
    {
        $versions = $schemaDocument->getVersion(true);
        $nonBaselineVersions = array_filter($versions, static fn (string $version) => $version !== '1.0.0');

        $this->output()->writeln('');
        $this->output()->writeln('<info>Schema Versions</info>');
        $this->output()->writeln(str_repeat('-', 50));

        if ($nonBaselineVersions === []) {
            $this->output()->writeln(sprintf('  all %d packages at 1.0.0', count($versions)));
        }

        foreach ($nonBaselineVersions as $package => $version) {
            $this->output()->writeln(sprintf('  %s: %s', $package, $version));
        }
    }

    protected function executeSingleDocument(
        ConfigurationDocumentMaintenanceServiceInterface $maintenanceService,
        SchemaDocument $schemaDocument,
        string $identifier,
        bool $dryRun,
    ): int {
        $migratable = $maintenanceService->getMigratableByIdentifier($identifier, $schemaDocument);

        if (!$migratable instanceof MigratableInterface) {
            $this->output()->writeln('');
            $this->output()->writeln(sprintf('<error>Document "%s" not found.</error>', $identifier));
            $this->output()->writeln('');

            return self::EXIT_FAILURE;
        }

        $this->displayMigratables([$identifier => $migratable]);

        if (!$migratable->isOutdated()) {
            $this->output()->writeln('');
            $this->output()->writeln('<info>Document is already up to date.</info>');
            $this->output()->writeln('');

            return self::EXIT_SUCCESS;
        }

        if ($migratable->isReadOnly()) {
            $this->output()->writeln('');
            $this->output()->writeln('<fg=yellow>Document is readonly — cannot migrate.</>');
            $this->output()->writeln('');

            return self::EXIT_FAILURE;
        }

        if ($dryRun) {
            $this->output()->writeln('');
            $this->output()->writeln('<info>Dry run — no changes made.</info>');
            $this->output()->writeln('');

            return self::EXIT_SUCCESS;
        }

        $this->output()->writeln('');
        try {
            $migrated = $maintenanceService->migrateDocument($migratable, $schemaDocument);
            if ($migrated) {
                $this->output()->writeln(sprintf('  <fg=green>migrated</> %s', $identifier));
            } else {
                $this->output()->writeln(sprintf('  <comment>no changes</comment> %s', $identifier));
            }
        } catch (Exception $exception) {
            $this->output()->writeln(sprintf('  <fg=red>failed</>   %s: %s', $identifier, $exception->getMessage()));
            $this->output()->writeln('');

            return self::EXIT_FAILURE;
        }

        $this->output()->writeln('');

        return self::EXIT_SUCCESS;
    }

    /**
     * @param array<string, MigratableInterface> $migratables
     */
    protected function executeAll(
        ConfigurationDocumentMaintenanceServiceInterface $maintenanceService,
        SchemaDocument $schemaDocument,
        array $migratables,
        bool $dryRun,
    ): int {
        $this->displayMigratables($migratables);

        $outdatedCount = 0;
        foreach ($migratables as $migratable) {
            if ($migratable->isOutdated()) {
                ++$outdatedCount;
            }
        }

        if ($outdatedCount === 0) {
            $this->output()->writeln('');
            $this->output()->writeln('<info>All documents are up to date.</info>');
            $this->output()->writeln('');

            return self::EXIT_SUCCESS;
        }

        if ($dryRun) {
            $this->output()->writeln('');
            $this->output()->writeln('<info>Dry run — no changes made.</info>');
            $this->output()->writeln('');

            return self::EXIT_SUCCESS;
        }

        return $this->runMigrations($maintenanceService, $schemaDocument);
    }

    /**
     * @param array<string, MigratableInterface> $migratables
     */
    protected function displayMigratables(array $migratables): void
    {
        $this->output()->writeln('');
        $this->output()->writeln('<info>Configuration Documents</info>');
        $this->output()->writeln(str_repeat('-', 50));

        $outdatedCount = 0;
        $readOnlyCount = 0;

        foreach ($migratables as $migratable) {
            $flags = [];
            if ($migratable->isReadOnly()) {
                $flags[] = 'readonly';
                ++$readOnlyCount;
            }

            if ($migratable->isOutdated()) {
                $flags[] = '<fg=yellow>outdated</>';
                ++$outdatedCount;
            }

            if ($migratable instanceof DataSourceMigratable) {
                $flags[] = 'data-source';
            }

            $flagStr = $flags !== [] ? ' [' . implode(', ', $flags) . ']' : '';
            $includes = $migratable->getIncludes() !== [] ? ' includes=[' . implode(', ', $migratable->getIncludes()) . ']' : '';
            $includedBy = $migratable->getIncludedBy() !== [] ? ' includedBy=[' . implode(', ', $migratable->getIncludedBy()) . ']' : '';

            $this->output()->writeln(sprintf(
                '  <comment>%s</comment> "%s"%s%s%s',
                $migratable->getIdentifier(),
                $migratable->getName(),
                $flagStr,
                $includes,
                $includedBy
            ));

            if ($migratable->isOutdated() && $migratable->getMigrationInfo() !== []) {
                foreach ($migratable->getMigrationInfo() as $package => $info) {
                    $from = $info['from'] !== '' ? $info['from'] : '1.0.0';
                    $color = match ($info['status']) {
                        'error' => 'red',
                        'genuine' => 'yellow',
                        default => 'gray',
                    };
                    $line = sprintf('%s: %s → %s', $package, $from, $info['to']);
                    if ($info['message'] !== '') {
                        $line .= ' — ' . $info['message'];
                    }

                    $this->output()->writeln(sprintf('    <fg=%s>%s</>', $color, $line));
                }
            }
        }

        $this->output()->writeln('');
        $this->output()->writeln(sprintf(
            '  Total: %d documents, %d readonly, %d outdated',
            count($migratables),
            $readOnlyCount,
            $outdatedCount
        ));
    }

    protected function runMigrations(
        ConfigurationDocumentMaintenanceServiceInterface $maintenanceService,
        SchemaDocument $schemaDocument,
    ): int {
        $this->output()->writeln('');
        $this->output()->writeln('<info>Running migrations...</info>');
        $this->output()->writeln(str_repeat('-', 50));

        $result = $maintenanceService->migrateAll($schemaDocument);

        foreach ($result['migrated'] as $identifier) {
            $this->output()->writeln(sprintf('  <fg=green>migrated</> %s', $identifier));
        }

        foreach ($result['skipped'] as $identifier) {
            $this->output()->writeln(sprintf('  <fg=yellow>skipped</>  %s (readonly)', $identifier));
        }

        foreach ($result['failed'] as $identifier => $message) {
            $this->output()->writeln(sprintf('  <fg=red>failed</>   %s: %s', $identifier, $message));
        }

        $this->output()->writeln('');
        $this->output()->writeln(sprintf(
            '  Migrated: %d, Skipped: %d, Failed: %d',
            count($result['migrated']),
            count($result['skipped']),
            count($result['failed'])
        ));
        $this->output()->writeln('');

        return $result['failed'] !== [] ? self::EXIT_FAILURE : self::EXIT_SUCCESS;
    }
}
