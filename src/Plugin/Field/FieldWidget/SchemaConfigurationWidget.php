<?php

namespace Drupal\dmf_core\Plugin\Field\FieldWidget;

use DigitalMarketingFramework\Core\Backend\RenderingServiceInterface;
use DigitalMarketingFramework\Core\ConfigurationEditor\MetaData;
use DigitalMarketingFramework\Core\Registry\RegistryCollectionInterface;
use DigitalMarketingFramework\Core\Registry\RegistryInterface as CoreRegistryInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget for editing Anyrel schema-based configurations.
 *
 * Renders a textarea with data attributes that enable the configuration editor
 * JavaScript application to enhance it with a modal-based UI.
 */
#[FieldWidget(
  id: 'dmf_schema_configuration_editor',
  label: new TranslatableMarkup('Schema Configuration Editor'),
  description: new TranslatableMarkup('Textarea with Anyrel schema-based configuration editor integration.'),
  field_types: ['dmf_schema_configuration'],
)]
class SchemaConfigurationWidget extends WidgetBase
{
    /**
     * The rendering service for generating textarea data attributes.
     */
    protected RenderingServiceInterface $renderingService;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        array $third_party_settings,
        RenderingServiceInterface $renderingService,
    ) {
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
        $this->renderingService = $renderingService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition,
    ): static {
        /** @var RegistryCollectionInterface $registryCollection */
        $registryCollection = $container->get('dmf_core.registry_collection');

        /** @var CoreRegistryInterface $coreRegistry */
        $coreRegistry = $registryCollection->getRegistryByClass(CoreRegistryInterface::class);
        $renderingService = $coreRegistry->getBackendRenderingService();

        return new static(
            $plugin_id,
            $plugin_definition,
            $configuration['field_definition'],
            $configuration['settings'],
            $configuration['third_party_settings'],
            $renderingService,
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings(): array
    {
        return [
            'document_type' => MetaData::DEFAULT_DOCUMENT_TYPE,
            'mode' => 'modal',
            'rows' => 10,
            'supports_includes' => true,
            'additional_parameters' => [],
        ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state): array
    {
        $elements = parent::settingsForm($form, $form_state);

        $elements['document_type'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Document Type'),
            '#description' => $this->t('The configuration document type (e.g., "default", "contentModifier").'),
            '#default_value' => $this->getSetting('document_type'),
            '#required' => true,
        ];

        $elements['mode'] = [
            '#type' => 'select',
            '#title' => $this->t('Editor Mode'),
            '#description' => $this->t('How the configuration editor is displayed.'),
            '#options' => [
                'modal' => $this->t('Modal dialog'),
                'embedded' => $this->t('Embedded inline'),
            ],
            '#default_value' => $this->getSetting('mode'),
        ];

        $elements['rows'] = [
            '#type' => 'number',
            '#title' => $this->t('Textarea Rows'),
            '#description' => $this->t('Number of rows for the textarea.'),
            '#default_value' => $this->getSetting('rows'),
            '#min' => 3,
            '#max' => 50,
        ];

        $elements['supports_includes'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Supports Includes'),
            '#description' => $this->t('Whether the document supports include references.'),
            '#default_value' => $this->getSetting('supports_includes'),
        ];

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary(): array
    {
        $summary = [];

        $summary[] = $this->t('Document type: @type', ['@type' => $this->getSetting('document_type')]);
        $summary[] = $this->t('Mode: @mode', ['@mode' => $this->getSetting('mode')]);
        $summary[] = $this->t('Rows: @rows', ['@rows' => $this->getSetting('rows')]);

        return $summary;
    }

    /**
     * {@inheritdoc}
     */
    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state,
    ): array {
        $value = $items[$delta]->value ?? '';

        // Build context identifier from entity information.
        $entity = $items->getEntity();
        $entityTypeId = $entity->getEntityTypeId();
        $bundle = $entity->bundle();
        $entityId = $entity->id() ?? 'new';
        $contextIdentifier = sprintf('%s:%s:%s', $entityTypeId, $bundle, $entityId);

        // Build unique identifier for this field instance.
        $fieldName = $this->fieldDefinition->getName();
        $uid = sprintf('%s:%s:%s:%s:%d', $entityTypeId, $bundle, $entityId, $fieldName, $delta);

        // Get additional parameters from settings.
        $additionalParameters = $this->getSetting('additional_parameters') ?? [];

        // Get configuration editor data attributes.
        $dataAttributes = $this->renderingService->getTextAreaDataAttributes(
            ready: true,
            mode: $this->getSetting('mode'),
            readonly: false,
            globalDocument: false,
            documentType: $this->getSetting('document_type'),
            includes: (bool) $this->getSetting('supports_includes'),
            parameters: $additionalParameters,
            contextIdentifier: $contextIdentifier,
            uid: $uid,
        );

        // Convert data attributes to Drupal's attribute format.
        $attributes = ['class' => ['dmf-configuration-document']];
        foreach ($dataAttributes as $key => $attrValue) {
            $attributes['data-' . $key] = $attrValue;
        }

        $element['value'] = $element + [
            '#type' => 'textarea',
            '#default_value' => $value,
            '#rows' => $this->getSetting('rows'),
            '#attributes' => $attributes,
        ];

        // Attach configuration editor library.
        $element['#attached']['library'][] = 'dmf_core/configuration-editor';

        return $element;
    }
}
