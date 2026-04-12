<?php

namespace Drupal\dmf_core;

use DigitalMarketingFramework\Core\Api\EndPoint\EndPointStorageInterface;
use DigitalMarketingFramework\Core\Backend\Controller\SectionController\SectionControllerInterface;
use DigitalMarketingFramework\Core\Backend\UriRouteResolver\UriRouteResolverInterface;
use DigitalMarketingFramework\Core\ConfigurationDocument\Parser\YamlConfigurationDocumentParser;
use DigitalMarketingFramework\Core\ConfigurationDocument\Storage\YamlFileConfigurationDocumentStorage;
use DigitalMarketingFramework\Core\CoreInitialization;
use DigitalMarketingFramework\Core\Crypto\HashServiceInterface;
use DigitalMarketingFramework\Core\FileStorage\FileStorageInterface;
use DigitalMarketingFramework\Core\Log\LoggerFactoryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
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

class DrupalCoreInitialization extends DrupalInitialization
{
    protected const PLUGINS = [
        RegistryDomain::CORE => [
            UriRouteResolverInterface::class => [
                DrupalDefaultUriRouteResolver::class,
            ],
            SectionControllerInterface::class => [
                ApiEditSectionController::class,
            ],
        ],
    ];

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
        parent::__construct(
            inner: new CoreInitialization('dmf_core'),
            packageName: 'drupal-core',
            packageAlias: 'dmf_core',
            globalConfigurationSchema: new CoreGlobalConfigurationSchema(),
        );
    }

    public function initGlobalConfiguration(string $domain, RegistryInterface $registry): void
    {
        parent::initGlobalConfiguration($domain, $registry);

        $globalConfiguration = new GlobalConfiguration($registry, $this->configFactory);
        $registry->setGlobalConfiguration($globalConfiguration);
    }

    public function initServices(string $domain, RegistryInterface $registry): void
    {
        $registry->setLoggerFactory($this->loggerFactory);

        $registry->setHashService($this->hashService);

        $registry->setFileStorage($this->fileStorage);

        $registry->setEndPointStorage($this->endPointStorage);

        $registry->setTestCaseStorage($this->testCaseStorage);

        $registry->setConfigurationDocumentStorage(
            $registry->createObject(YamlFileConfigurationDocumentStorage::class)
        );

        $registry->setConfigurationDocumentParser(
            $registry->createObject(YamlConfigurationDocumentParser::class)
        );

        $vendorResourceService = $registry->getVendorResourceService();
        $vendorResourceService->setVendorPath(DRUPAL_ROOT . '/../vendor');

        $registry->registerResourceService($this->moduleResourceService);

        $assetService = $registry->getAssetService();
        $publicFilePath = Settings::get('file_public_path', 'sites/default/files');
        $assetService->setAssetConfig([
            'tempBasePath' => DRUPAL_ROOT . '/' . $publicFilePath,
            'publicTempBasePath' => $publicFilePath,
            'salt' => Settings::get('hash_salt', ''),
        ]);

        $registry->setBackendAssetUriBuilder(new AssetUriBuilder($registry));

        parent::initServices($domain, $registry);
    }

    protected function getAdditionalPluginArguments(string $interface, string $pluginClass, RegistryInterface $registry): array
    {
        if ($pluginClass === ApiEditSectionController::class) {
            return [
                $this->entityFormBuilder,
                $this->entityTypeManager,
                $this->renderer,
            ];
        }

        return parent::getAdditionalPluginArguments($interface, $pluginClass, $registry);
    }
}
