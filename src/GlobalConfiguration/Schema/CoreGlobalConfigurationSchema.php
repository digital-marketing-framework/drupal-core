<?php

namespace Drupal\dmf_core\GlobalConfiguration\Schema;

use DigitalMarketingFramework\Core\GlobalConfiguration\Schema\CoreGlobalConfigurationSchema as OriginalCoreGlobalConfigurationSchema;
use Drupal\dmf_core\Cleanup\GlobalConfiguration\Schema\CleanupSchema;

class CoreGlobalConfigurationSchema extends OriginalCoreGlobalConfigurationSchema
{
    public const KEY_CLEANUP = 'cleanup';

    public function __construct()
    {
        parent::__construct();

        $this->addProperty(static::KEY_CLEANUP, new CleanupSchema());
    }
}