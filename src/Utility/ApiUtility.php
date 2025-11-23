<?php

namespace Drupal\dmf_core\Utility;

use DigitalMarketingFramework\Core\GlobalConfiguration\Schema\CoreGlobalConfigurationSchema;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Lightweight utility for reading API configuration without bootstrapping Anyrel.
 *
 * This class provides static methods to check API status and configuration
 * without triggering the full Anyrel bootstrap process. This is critical for
 * performance when generating routes or checking API availability.
 */
class ApiUtility
{
    /**
     * Check if the Anyrel API is enabled.
     *
     * Reads directly from Drupal's config system without bootstrapping Anyrel.
     *
     * @return bool
     *   TRUE if API is enabled, FALSE otherwise.
     */
    public static function enabled(): bool
    {
        $config = static::getConfigFactory()->get('dmf_core.global_settings');
        $data = $config->getRawData();

        return (bool) ($data['api']['enabled'] ?? CoreGlobalConfigurationSchema::DEFAULT_API_ENABLED);
    }

    /**
     * Get the configured API base path.
     *
     * Reads directly from Drupal's config system without bootstrapping Anyrel.
     *
     * @return string
     *   The configured base path (e.g., 'digital-marketing-framework/api').
     */
    public static function getBasePath(): string
    {
        $config = static::getConfigFactory()->get('dmf_core.global_settings');
        $data = $config->getRawData();

        return (string) ($data['api']['basePath'] ?? CoreGlobalConfigurationSchema::DEFAULT_API_BASE_PATH);
    }

    /**
     * Get Drupal's config factory.
     *
     * @return \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected static function getConfigFactory(): ConfigFactoryInterface
    {
        return \Drupal::configFactory();
    }
}