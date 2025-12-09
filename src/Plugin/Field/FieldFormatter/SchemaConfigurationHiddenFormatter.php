<?php

namespace Drupal\dmf_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formatter that hides the schema configuration field from display.
 *
 * Use this formatter when the schema configuration should not be rendered
 * on the frontend (e.g., for backend-only configuration fields).
 */
#[FieldFormatter(
  id: 'dmf_schema_configuration_hidden',
  label: new TranslatableMarkup('Hidden'),
  description: new TranslatableMarkup('Do not display the configuration.'),
  field_types: ['dmf_schema_configuration'],
)]
class SchemaConfigurationHiddenFormatter extends FormatterBase
{
    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode): array
    {
        // Return empty array to hide the field completely.
        return [];
    }
}
