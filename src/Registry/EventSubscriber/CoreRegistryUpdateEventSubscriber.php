<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use DigitalMarketingFramework\Core\Backend\UriBuilderInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\Parser\YamlConfigurationDocumentParser;
use DigitalMarketingFramework\Core\ConfigurationDocument\Storage\YamlFileConfigurationDocumentStorage;
use DigitalMarketingFramework\Core\CoreInitialization;
use DigitalMarketingFramework\Core\FileStorage\FileStorageInterface;
use DigitalMarketingFramework\Core\Log\LoggerFactoryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\Resource\ResourceServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\dmf_core\GlobalConfiguration\GlobalConfiguration;
use Drupal\dmf_core\GlobalConfiguration\Schema\CoreGlobalConfigurationSchema;

class CoreRegistryUpdateEventSubscriber extends AbstractCoreRegistryUpdateEventSubscriber
{
    public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected LoggerFactoryInterface $loggerFactory,
        protected UriBuilderInterface $uriBuilder,
        protected ResourceServiceInterface $moduleResourceService,
        protected FileStorageInterface $fileStorage,
    ) {
        $initialization = new CoreInitialization('dmf_core');
        $initialization->setGlobalConfigurationSchema(new CoreGlobalConfigurationSchema());
        parent::__construct($initialization);
    }

    protected function initGlobalConfiguration(RegistryInterface $registry): void
    {
        parent::initGlobalConfiguration($registry);

        $globalConfiguration = new GlobalConfiguration($registry, $this->configFactory);
        $registry->setGlobalConfiguration($globalConfiguration);
    }

    protected function initServices(RegistryInterface $registry): void
    {
        // Set logger factory
        $registry->setLoggerFactory($this->loggerFactory);

        // Set backend URI builder
        $registry->setBackendUriBuilder($this->uriBuilder);

        // Set FileStorage (Drupal implementation)
        $registry->setFileStorage($this->fileStorage);

        // Set ConfigurationDocumentStorage (user-created documents)
        $registry->setConfigurationDocumentStorage(
            $registry->createObject(YamlFileConfigurationDocumentStorage::class)
        );

        // Set ConfigurationDocumentParser
        $registry->setConfigurationDocumentParser(
            $registry->createObject(YamlConfigurationDocumentParser::class)
        );

        // Set StaticConfigurationDocumentStorage (package-bundled documents)
        $registry->setStaticConfigurationDocumentStorage(
            $registry->createObject(YamlFileConfigurationDocumentStorage::class)
        );

        // Set vendor path for vendor resource service
        $vendorResourceService = $registry->getVendorResourceService();
        $vendorResourceService->setVendorPath(DRUPAL_ROOT . '/../vendor');

        // Register Drupal module resource service for MOD: identifiers
        $registry->registerResourceService($this->moduleResourceService);

        // Register configuration document folder for this module
        $moduleKey = $this->initialization->getPackageAlias();
        if ($moduleKey !== '') {
            $registry->addStaticConfigurationDocumentFolderIdentifier(
                sprintf('MOD:%s/res/configuration', $moduleKey)
            );
        }

        // Configure AssetService for copying vendor/module assets to public directory
        $assetService = $registry->getAssetService();
        $publicFilePath = Settings::get('file_public_path', 'sites/default/files');
        $assetService->setAssetConfig([
            'tempBasePath' => DRUPAL_ROOT . '/' . $publicFilePath,
            'publicTempBasePath' => $publicFilePath,
            'salt' => Settings::get('hash_salt', ''),
        ]);

        parent::initServices($registry);
    }

    protected function initPlugins(RegistryInterface $registry): void
    {
        parent::initPlugins($registry);
        // TODO: Register Drupal-specific backend section controllers
    }
}