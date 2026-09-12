<?php

/**
 * @file
 * Post update functions for the dmf_core module.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * The global settings this module ships.
 *
 * Read rather than restated, because config/install is the one place they are declared and a
 * second copy here would drift from it without anything failing.
 *
 * @return array<string,mixed>
 */
function _dmf_core_shipped_global_settings(): array
{
    $path = Drupal::service('extension.path.resolver')->getPath('module', 'dmf_core');
    $contents = file_get_contents($path . '/config/install/dmf_core.global_settings.yml');
    if ($contents === FALSE) {
        return [];
    }

    return Yaml::decode($contents) ?? [];
}

/**
 * Adds the field definition storage settings to existing installations.
 */
function dmf_core_post_update_field_definition_storage(): void
{
    // config/install applies at module install only, so a site that had this module before
    // these settings existed has no key at all — and an unconfigured field definition storage
    // offers no editing and says nothing about why.
    $config = Drupal::configFactory()->getEditable('dmf_core.global_settings');
    if ($config->get('fieldDefinitionStorage') !== NULL) {
        return;
    }

    $defaults = _dmf_core_shipped_global_settings();
    if (!isset($defaults['fieldDefinitionStorage'])) {
        return;
    }

    $config->set('fieldDefinitionStorage', $defaults['fieldDefinitionStorage'])->save();
}
