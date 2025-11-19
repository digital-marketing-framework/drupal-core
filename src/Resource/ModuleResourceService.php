<?php

namespace Drupal\dmf_core\Resource;

use DigitalMarketingFramework\Core\Resource\ResourceService;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Resource service for Drupal module resources.
 *
 * Resolves MOD: identifiers to Drupal module paths.
 *
 * Supports two types of resources:
 * 1. Public assets (Drupal standard): css/, js/, images/
 *    - Directly web-accessible via Drupal's library system
 *    - Example: MOD:dmf_core/js/editor.js
 * 2. Anyrel framework resources: res/
 *    - Private resources (templates, configuration, etc.)
 *    - Example: MOD:dmf_core/res/configuration/example.config.yaml
 *
 * Pattern: MOD:module_name/path/to/file
 */
class ModuleResourceService extends ResourceService
{
    public const IDENTIFIER_PREFIX = 'MOD';

    public function __construct(
        protected ModuleExtensionList $moduleList,
    ) {
    }

    public function getIdentifierPrefix(): string
    {
        return self::IDENTIFIER_PREFIX;
    }

    public function getResourcePath(string $identifier): ?string
    {
        $fileInfo = $this->getModuleResourceFileInfo($identifier);

        if ($fileInfo === false) {
            return null;
        }

        // Get module path from Drupal's extension system
        $modulePath = $this->moduleList->getPath($fileInfo['module']);

        if ($modulePath === null) {
            return null;
        }

        // Return: /path/to/web/modules/contrib/dmf_core/res/configuration/example.yaml
        return $modulePath . '/' . $fileInfo['path'];
    }

    public function resourceIdentifierMatch(string $identifier): bool
    {
        return $this->getModuleResourceFileInfo($identifier) !== false;
    }

    public function getResourceRootIdentifier(string $identifier): ?string
    {
        $fileInfo = $this->getModuleResourceFileInfo($identifier);

        if ($fileInfo === false) {
            return null;
        }

        return self::IDENTIFIER_PREFIX . ':' . $fileInfo['module'] . '/res';
    }

    public function isAssetResource(string $identifier): bool
    {
        // Drupal modules: assets are in public folders (css/, js/, images/)
        // NOT in res/assets/ (that's only for plain PHP packages)
        return $this->isResourceInFolder($identifier, 'css')
            || $this->isResourceInFolder($identifier, 'js')
            || $this->isResourceInFolder($identifier, 'images')
            || $this->isResourceInFolder($identifier, 'fonts');
    }

    public function isPublicResource(string $identifier): bool
    {
        // Drupal standard public folders are web-accessible
        // res/ folder contains Anyrel private resources (not public)
        return $this->isResourceInFolder($identifier, 'css')
            || $this->isResourceInFolder($identifier, 'js')
            || $this->isResourceInFolder($identifier, 'images')
            || $this->isResourceInFolder($identifier, 'fonts');
    }

    /**
     * Parse module resource identifier.
     *
     * @return array{module:string,path:string}|false
     */
    protected function getModuleResourceFileInfo(string $identifier): array|false
    {
        $matches = [];

        // Pattern: MOD:module_name/folder/path/to/file
        // Allowed folders:
        //   - res/ (Anyrel private resources)
        //   - css/, js/, images/, fonts/ (Drupal public assets)
        // Module names: lowercase letters, numbers, underscores
        if (preg_match('/^' . self::IDENTIFIER_PREFIX . ':([a-z0-9_]+)\/((?:res|css|js|images|fonts)(?:\/.+)?)$/', $identifier, $matches)) {
            return [
                'module' => $matches[1],
                'path' => $matches[2],
            ];
        }

        return false;
    }
}
