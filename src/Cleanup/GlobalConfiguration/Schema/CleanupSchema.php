<?php

namespace Drupal\dmf_core\Cleanup\GlobalConfiguration\Schema;

use DigitalMarketingFramework\Core\SchemaDocument\Schema\BooleanSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\IntegerSchema;

/**
 * Drupal-specific cleanup schema with cron integration settings.
 */
class CleanupSchema extends ContainerSchema
{
    public const KEY_CRON_ENABLED = 'cronEnabled';

    public const DEFAULT_CRON_ENABLED = false;

    public const KEY_CRON_MIN_INTERVAL = 'cronMinInterval';

    public const DEFAULT_CRON_MIN_INTERVAL = 3600;

    public function __construct()
    {
        parent::__construct();

        $this->getRenderingDefinition()->setLabel('Cleanup');

        $cronEnabledSchema = new BooleanSchema(static::DEFAULT_CRON_ENABLED);
        $cronEnabledSchema->getRenderingDefinition()->setLabel('Enable cron task');
        $this->addProperty(static::KEY_CRON_ENABLED, $cronEnabledSchema);

        $cronMinIntervalSchema = new IntegerSchema(static::DEFAULT_CRON_MIN_INTERVAL);
        $cronMinIntervalSchema->getRenderingDefinition()->setLabel('Minimum cron interval (in seconds)');
        $this->addProperty(static::KEY_CRON_MIN_INTERVAL, $cronMinIntervalSchema);
    }
}