<?php

namespace Drupal\dmf_core\Registry\EventSubscriber;

use Drupal\dmf_core\DrupalCoreInitialization;

class CoreRegistryUpdateEventSubscriber extends AbstractCoreRegistryUpdateEventSubscriber
{
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- narrowed type hint for runtime enforcement
    public function __construct(
        DrupalCoreInitialization $initialization,
    ) {
        parent::__construct($initialization);
    }
}
