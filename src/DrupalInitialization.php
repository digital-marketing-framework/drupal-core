<?php

namespace Drupal\dmf_core;

use DigitalMarketingFramework\Core\ConfigurationDocument\Migration\ConfigurationDocumentMigrationInterface;
use DigitalMarketingFramework\Core\GlobalConfiguration\Schema\GlobalConfigurationSchemaInterface;
use DigitalMarketingFramework\Core\Initialization;
use DigitalMarketingFramework\Core\InitializationInterface;
use DigitalMarketingFramework\Core\Plugin\PluginInterface;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\SchemaDocument\SchemaDocument;

class DrupalInitialization extends Initialization implements DrupalInitializationInterface
{
    protected const FRONTEND_SCRIPT_PATTERN = 'MOD:%s/res/assets/scripts/%s';

    protected const CONFIGURATION_DOCUMENT_FOLDER_PATTERN = 'MOD:%s/res/%s';

    protected const TEMPLATE_FOLDER_PATTERN = 'MOD:%s/res/%s';

    protected const PARTIAL_FOLDER_PATTERN = 'MOD:%s/res/%s';

    protected const LAYOUT_FOLDER_PATTERN = 'MOD:%s/res/%s';

    /**
     * @var array<"core"|"distributor"|"collector",array<class-string<PluginInterface>,array<string|int,class-string<PluginInterface>>>>
     */
    protected const PLUGINS = [];

    /**
     * @var array<class-string<ConfigurationDocumentMigrationInterface>>
     */
    protected const SCHEMA_MIGRATIONS = [];

    /**
     * @var array<string>
     */
    protected const CONFIGURATION_EDITOR_SCRIPTS = [];

    /**
     * @var array<string,array<string>>
     */
    protected const FRONTEND_SCRIPTS = [];

    /**
     * @var array<string>
     */
    protected const CONFIGURATION_DOCUMENT_FOLDERS = ['configuration'];

    /**
     * @var array<string,int>
     */
    protected const TEMPLATE_FOLDERS = ['templates/frontend' => 200];

    /**
     * @var array<string,int>
     */
    protected const LAYOUT_FOLDERS = ['layouts/frontend' => 200];

    /**
     * @var array<string,int>
     */
    protected const PARTIAL_FOLDERS = ['partials/frontend' => 200];

    /**
     * @var array<string,int>
     */
    protected const BACKEND_TEMPLATE_FOLDERS = ['templates/backend' => 200];

    /**
     * @var array<string,int>
     */
    protected const BACKEND_LAYOUT_FOLDERS = ['layouts/backend' => 200];

    /**
     * @var array<string,int>
     */
    protected const BACKEND_PARTIAL_FOLDERS = ['partials/backend' => 200];

    public function __construct(
        protected ?InitializationInterface $inner = null,
        string $packageName = '',
        string $schemaVersion = SchemaDocument::INITIAL_VERSION,
        string $packageAlias = '',
        ?GlobalConfigurationSchemaInterface $globalConfigurationSchema = null,
    ) {
        parent::__construct($packageName, $schemaVersion, $packageAlias, $globalConfigurationSchema);
    }

    protected function getPathIdentifier(): string
    {
        return $this->getPackageAlias();
    }

    public function getPackageAlias(): string
    {
        $alias = parent::getPackageAlias();
        if ($alias !== '') {
            return $alias;
        }

        return $this->inner?->getPackageAlias() ?? '';
    }

    public function getFullPackageName(): string
    {
        if ($this->packageName !== '') {
            return parent::getFullPackageName();
        }

        return $this->inner?->getFullPackageName() ?? '';
    }

    public function getGlobalConfigurationSchema(): ?GlobalConfigurationSchemaInterface
    {
        return parent::getGlobalConfigurationSchema()
            ?? $this->inner?->getGlobalConfigurationSchema();
    }

    public function initPlugins(string $domain, RegistryInterface $registry): void
    {
        $this->inner?->initPlugins($domain, $registry);
        parent::initPlugins($domain, $registry);
    }

    public function initServices(string $domain, RegistryInterface $registry): void
    {
        $this->inner?->initServices($domain, $registry);
        parent::initServices($domain, $registry);
    }

    public function initGlobalConfiguration(string $domain, RegistryInterface $registry): void
    {
        $this->inner?->initGlobalConfiguration($domain, $registry);
        parent::initGlobalConfiguration($domain, $registry);
    }

    public function initMetaData(RegistryInterface $registry): void
    {
        $this->inner?->initMetaData($registry);
        parent::initMetaData($registry);
    }
}
