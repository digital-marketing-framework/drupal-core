<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use DigitalMarketingFramework\Core\Registry\RegistryCollectionInterface;
use DigitalMarketingFramework\Core\Registry\RegistryDomain;
use Drupal\dmf_core\Registry\Registry;
use Drupal\dmf_core\Registry\RegistryCollection;
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
            RegistryCollection::class => 'onRegistryCollectionUpdate',
        ];
    }

    public function onRegistryCollectionUpdate(RegistryCollectionInterface $collection): void
    {
        $collection->addRegistry(RegistryDomain::CORE, $this->registry);
    }
}