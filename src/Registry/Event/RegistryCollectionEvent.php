<?php

namespace Drupal\dmf_core\Registry\Event;

use DigitalMarketingFramework\Core\Registry\RegistryCollectionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when RegistryCollection needs to fetch registries.
 *
 * Event subscribers should add their Registry instances to the collection
 * by calling $event->getRegistryCollection()->addRegistry().
 */
class RegistryCollectionEvent extends Event
{
    public function __construct(
        protected RegistryCollectionInterface $registryCollection,
    ) {
    }

    public function getRegistryCollection(): RegistryCollectionInterface
    {
        return $this->registryCollection;
    }
}