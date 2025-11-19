<?php

namespace Drupal\dmf_core\Registry;

use DigitalMarketingFramework\Core\Registry\RegistryCollection as OriginalRegistryCollection;
use Drupal\dmf_core\Context\DrupalRequestContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistryCollection extends OriginalRegistryCollection
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        DrupalRequestContext $context,
    ) {
        parent::__construct();
        $this->setContext($context);
    }

    protected function fetchRegistries(): void
    {
        $this->eventDispatcher->dispatch($this);
    }
}