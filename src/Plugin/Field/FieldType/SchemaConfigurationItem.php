<?php

namespace Drupal\dmf_core\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Field type for storing Anyrel schema-based configurations.
 *
 * This field type extends StringLongItem to store configurations (YAML/JSON)
 * while providing a custom widget with the schema-based configuration editor
 * and a formatter for frontend output.
 *
 * Used for various Anyrel configuration types:
 * - Content modifier settings (page, element, form modifiers)
 * - Configuration documents (distributor/collector routes and settings)
 * - Other schema-based configurations
 */
#[FieldType(
    id: 'dmf_schema_configuration',
    label: new TranslatableMarkup('Anyrel Schema Configuration'),
    description: new TranslatableMarkup('Stores an Anyrel configuration with schema-based editor support.'),
    category: 'anyrel',
    default_widget: 'dmf_schema_configuration_editor',
    default_formatter: 'dmf_schema_configuration_hidden'
)]
class SchemaConfigurationItem extends StringLongItem
{
}
