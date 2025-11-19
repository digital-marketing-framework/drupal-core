<?php

namespace Drupal\dmf_core\GlobalConfiguration\Schema;

use DigitalMarketingFramework\Core\GlobalConfiguration\Schema\CoreGlobalConfigurationSchema as OriginalCoreGlobalConfigurationSchema;

class CoreGlobalConfigurationSchema extends OriginalCoreGlobalConfigurationSchema
{
    public function __construct()
    {
        parent::__construct();

        // TODO: Add Drupal-specific global configuration settings here if needed
        // Example: Storage locations, caching settings, etc.
    }
}