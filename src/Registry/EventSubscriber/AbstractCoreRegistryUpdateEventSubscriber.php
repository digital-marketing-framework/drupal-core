<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use DigitalMarketingFramework\Core\InitializationInterface;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;
use Drupal\dmf_core\DrupalInitialization;
use Drupal\dmf_core\DrupalInitializationInterface;
use Drupal\dmf_core\Registry\Event\CoreRegistryUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractCoreRegistryUpdateEventSubscriber implements EventSubscriberInterface
{
    protected DrupalInitializationInterface $initialization;

    public function __construct(
        InitializationInterface $initialization,
    ) {
        if ($initialization instanceof DrupalInitializationInterface) {
            $this->initialization = $initialization;
        } else {
            $this->initialization = new DrupalInitialization(inner: $initialization);
        }
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
