<?php

namespace Drupal\dmf_core\Registry;

use DigitalMarketingFramework\Core\Registry\Registry as CoreRegistry;
use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;
use Drupal\dmf_core\Registry\Event\CoreRegistryUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Registry extends CoreRegistry
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function init(): void
    {
        $this->eventDispatcher->dispatch(
            new CoreRegistryUpdateEvent($this, RegistryUpdateType::GLOBAL_CONFIGURATION)
        );
        $this->eventDispatcher->dispatch(
            new CoreRegistryUpdateEvent($this, RegistryUpdateType::SERVICE)
        );
        $this->eventDispatcher->dispatch(
            new CoreRegistryUpdateEvent($this, RegistryUpdateType::PLUGIN)
        );
    }
}
