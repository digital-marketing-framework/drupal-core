<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use Drupal\dmf_core\Registry\Event\RegistryCollectionEvent;
use Drupal\dmf_core\Registry\Registry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistryCollectionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected Registry $registry,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RegistryCollectionEvent::class => 'onRegistryCollectionUpdate',
        ];
    }

    public function onRegistryCollectionUpdate(RegistryCollectionEvent $event): void
    {
        $event->getRegistryCollection()->addRegistry(RegistryDomain::CORE, $this->registry);
    }
}
