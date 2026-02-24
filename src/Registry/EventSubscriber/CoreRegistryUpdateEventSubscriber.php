<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use DigitalMarketingFramework\Core\Api\EndPoint\EndPointStorageInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\Parser\YamlConfigurationDocumentParser;
use DigitalMarketingFramework\Core\ConfigurationDocument\Storage\YamlFileConfigurationDocumentStorage;
use DigitalMarketingFramework\Core\CoreInitialization;
use DigitalMarketingFramework\Core\Crypto\HashServiceInterface;
use DigitalMarketingFramework\Core\FileStorage\FileStorageInterface;
use DigitalMarketingFramework\Core\Log\LoggerFactoryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\Resource\ResourceServiceInterface;
use DigitalMarketingFramework\Core\TestCase\TestCaseStorageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
use Drupal\dmf_core\Backend\AssetUriBuilder;
use Drupal\dmf_core\Backend\Controller\SectionController\ApiEditSectionController;
use Drupal\dmf_core\Backend\UriRouteResolver\DrupalDefaultUriRouteResolver;
use Drupal\dmf_core\GlobalConfiguration\GlobalConfiguration;
use Drupal\dmf_core\GlobalConfiguration\Schema\CoreGlobalConfigurationSchema;

class CoreRegistryUpdateEventSubscriber extends AbstractCoreRegistryUpdateEventSubscriber
{
    public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected LoggerFactoryInterface $loggerFactory,
        protected HashServiceInterface $hashService,
        protected ResourceServiceInterface $moduleResourceService,
        protected FileStorageInterface $fileStorage,
        protected EndPointStorageInterface $endPointStorage,
        protected TestCaseStorageInterface $testCaseStorage,
        protected EntityFormBuilderInterface $entityFormBuilder,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected RendererInterface $renderer,
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

        // Set hash service
        $registry->setHashService($this->hashService);

        // Set FileStorage (Drupal implementation)
        $registry->setFileStorage($this->fileStorage);

        // Set API Endpoint storage (Drupal implementation)
        $registry->setEndPointStorage($this->endPointStorage);

        // Set Test Case storage (Drupal implementation)
        $registry->setTestCaseStorage($this->testCaseStorage);

        // Set ConfigurationDocumentStorage (user-created documents)
        $registry->setConfigurationDocumentStorage(
            $registry->createObject(YamlFileConfigurationDocumentStorage::class)
        );

        // Set ConfigurationDocumentParser
        $registry->setConfigurationDocumentParser(
            $registry->createObject(YamlConfigurationDocumentParser::class)
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

        // Set backend asset URI builder
        $registry->setBackendAssetUriBuilder(new AssetUriBuilder($registry));

        parent::initServices($registry);
    }

    protected function initPlugins(RegistryInterface $registry): void
    {
        parent::initPlugins($registry);

        // Register Drupal URI route resolvers
        $registry->registerBackendUriRouteResolver(DrupalDefaultUriRouteResolver::class);

        // Register Drupal-specific backend section controllers with Drupal services
        $registry->registerBackendSectionController(
            ApiEditSectionController::class,
            [
                $this->entityFormBuilder,
                $this->entityTypeManager,
                $this->renderer,
            ]
        );
    }
}
