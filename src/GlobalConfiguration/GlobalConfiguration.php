<?php

namespace Drupal\dmf_core\GlobalConfiguration;

use DigitalMarketingFramework\Core\GlobalConfiguration\DefaultGlobalConfiguration;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class GlobalConfiguration extends DefaultGlobalConfiguration
{
    public function __construct(
        RegistryInterface $registry,
        protected ConfigFactoryInterface $configFactory,
    ) {
        parent::__construct($registry);
    }

    public function get(string $key, mixed $default = null, bool $resolvePlaceholders = true): mixed
    {
        // Resolve alias: 'digital-marketing-framework/drupal-core' -> 'dmf_core'
        $key = $this->packageAliases->resolveAlias($key);

        // Route to package-specific config object
        $configName = $key . '.global_settings';
        $config = $this->configFactory->get($configName);

        // Get the entire settings array for this package
        $value = $config->getRawData();

        // If config object is empty, fall back to parent (schema defaults)
        if ($value === []) {
            $value = parent::get($key, $default, false);
        }

        // Resolve environment variables if requested
        if ($resolvePlaceholders) {
            $value = $this->registry->getEnvironmentService()->insertEnvironmentVariables($value);
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        // Resolve alias: 'digital-marketing-framework/drupal-core' -> 'dmf_core'
        $key = $this->packageAliases->resolveAlias($key);

        // Route to package-specific config object
        $configName = $key . '.global_settings';
        $config = $this->configFactory->getEditable($configName);

        // Replace entire config object with new value
        $config->setData($value)->save();
    }
}
