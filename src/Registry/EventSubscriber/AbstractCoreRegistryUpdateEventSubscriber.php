<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use DigitalMarketingFramework\Core\InitializationInterface;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;
use Drupal\dmf_core\Registry\Event\CoreRegistryUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractCoreRegistryUpdateEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    protected const TEMPLATE_PATH_PATTERN = 'modules/contrib/%s/templates/frontend';

    /**
     * @var string
     */
    protected const BACKEND_TEMPLATE_PATH_PATTERN = 'modules/contrib/%s/templates/backend';

    /**
     * @var int
     */
    protected const TEMPLATE_PRIORITY = 200;

    /**
     * @var string
     */
    protected const PARTIAL_PATH_PATTERN = 'modules/contrib/%s/partials/frontend';

    /**
     * @var string
     */
    protected const BACKEND_PARTIAL_PATH_PATTERN = 'modules/contrib/%s/partials/backend';

    /**
     * @var int
     */
    protected const PARTIAL_PRIORITY = 200;

    /**
     * @var string
     */
    protected const CONFIGURATION_DOCUMENTS_PATH_PATTERN = 'modules/contrib/%s/config/documents';

    public function __construct(
        protected InitializationInterface $initialization,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreRegistryUpdateEvent::class => 'onRegistryUpdate',
        ];
    }

    protected function initGlobalConfiguration(RegistryInterface $registry): void
    {
        $this->initialization->initGlobalConfiguration(RegistryDomain::CORE, $registry);
    }

    protected function initServices(RegistryInterface $registry): void
    {
        $this->initialization->initServices(RegistryDomain::CORE, $registry);

        $moduleAlias = $this->initialization->getPackageAlias();
        if ($moduleAlias !== '') {
            $registry->getTemplateService()->addTemplateFolder(sprintf(static::TEMPLATE_PATH_PATTERN, $moduleAlias), static::TEMPLATE_PRIORITY);
            $registry->getTemplateService()->addPartialFolder(sprintf(static::PARTIAL_PATH_PATTERN, $moduleAlias), static::PARTIAL_PRIORITY);

            $registry->getBackendTemplateService()->addTemplateFolder(sprintf(static::BACKEND_TEMPLATE_PATH_PATTERN, $moduleAlias), static::TEMPLATE_PRIORITY);
            $registry->getBackendTemplateService()->addPartialFolder(sprintf(static::BACKEND_PARTIAL_PATH_PATTERN, $moduleAlias), static::PARTIAL_PRIORITY);

            $registry->addStaticConfigurationDocumentFolderIdentifier(sprintf(static::CONFIGURATION_DOCUMENTS_PATH_PATTERN, $moduleAlias));
        }
    }

    protected function initPlugins(RegistryInterface $registry): void
    {
        $this->initialization->initPlugins(RegistryDomain::CORE, $registry);
    }

    public function onRegistryUpdate(CoreRegistryUpdateEvent $event): void
    {
        $registry = $event->getRegistry();

        // always init meta data
        $this->initialization->initMetaData($registry);

        // init rest depending on update type
        $type = $event->getUpdateType();
        switch ($type) {
            case RegistryUpdateType::GLOBAL_CONFIGURATION:
                $this->initGlobalConfiguration($registry);
                break;
            case RegistryUpdateType::SERVICE:
                $this->initServices($registry);
                break;
            case RegistryUpdateType::PLUGIN:
                $this->initPlugins($registry);
                break;
        }
    }
}