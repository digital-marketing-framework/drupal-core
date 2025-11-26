<?php

namespace Drupal\dmf_core\Cleanup\GlobalConfiguration\Settings;

use DigitalMarketingFramework\Core\GlobalConfiguration\Settings\GlobalSettings;
use Drupal\dmf_core\Cleanup\GlobalConfiguration\Schema\CleanupSchema;

/**
 * Drupal-specific cleanup settings with cron integration options.
 */
class CleanupSettings extends GlobalSettings
{
    public const PACKAGE_NAME = 'core';

    public const COMPONENT_NAME = 'cleanup';

    public function __construct()
    {
        parent::__construct(static::PACKAGE_NAME, static::COMPONENT_NAME);
    }

    /**
     * Whether the cron task is enabled.
     */
    public function isCronEnabled(): bool
    {
        return $this->get(CleanupSchema::KEY_CRON_ENABLED, CleanupSchema::DEFAULT_CRON_ENABLED);
    }

    /**
     * Get the minimum interval between cron runs (in seconds).
     */
    public function getCronMinInterval(): int
    {
        return $this->get(CleanupSchema::KEY_CRON_MIN_INTERVAL, CleanupSchema::DEFAULT_CRON_MIN_INTERVAL);
    }
}