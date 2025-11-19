<?php

namespace Drupal\dmf_core\Registry\Event;

use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use DigitalMarketingFramework\Core\Registry\RegistryUpdateType;
use Symfony\Contracts\EventDispatcher\Event;

class CoreRegistryUpdateEvent extends Event
{
    public function __construct(
        protected RegistryInterface $registry,
        protected RegistryUpdateType $type,
    ) {
    }

    public function getRegistry(): RegistryInterface
    {
        return $this->registry;
    }

    public function getUpdateType(): RegistryUpdateType
    {
        return $this->type;
    }
}