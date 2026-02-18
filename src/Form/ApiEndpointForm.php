<?php

namespace Drupal\dmf_core\Form;

use DigitalMarketingFramework\Core\ConfigurationEditor\MetaData;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dmf_core\Entity\ApiEndpoint;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for API Endpoint add and edit forms.
 */
class ApiEndpointForm extends EntityForm
{
    /**
     * Anyrel registry for accessing services.
     */
    protected RegistryInterface $registry;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->registry = $container->get('dmf_core.registry_collection')->getRegistry();

        return $instance;
    }

    /**
     * @param array<mixed> $form
     *
     * @return array<string,mixed>
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        /** @var ApiEndpoint $endpoint */
        $endpoint = $this->entity;

        // Add vertical tabs container
        $form['tabs'] = [
            '#type' => 'vertical_tabs',
            '#weight' => 99,
        ];

        // General tab
        $form['general'] = [
            '#type' => 'details',
            '#title' => $this->t('General'),
            '#group' => 'tabs',
            '#weight' => 0,
            '#open' => true,
        ];

        $form['general']['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#maxlength' => 255,
            '#default_value' => $endpoint->getName(),
            '#description' => $this->t('The human-readable name of the API endpoint.'),
            '#required' => true,
        ];

        $form['general']['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $endpoint->id(),
            '#machine_name' => [
                'exists' => $this->exist(...),
                'source' => ['general', 'label'],
            ],
            '#disabled' => !$endpoint->isNew(),
        ];

        $form['general']['enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enabled'),
            '#default_value' => $endpoint->getEnabled(),
            '#description' => $this->t('Master enabled flag for this endpoint.'),
        ];

        $form['general']['expose_to_frontend'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Expose to Frontend'),
            '#default_value' => $endpoint->getExposeToFrontend(),
            '#description' => $this->t('Make this endpoint available to the frontend.'),
        ];

        // Get configuration editor data attributes from RenderingService
        $renderingService = $this->registry->getBackendRenderingService();

        // Build context identifier for this endpoint (api:endpoint_id)
        $endpointId = $endpoint->id() ?? 'new';
        $contextIdentifier = 'api:' . $endpointId;
        $uid = 'configuration-document:' . $endpointId;

        $dataAttributes = $renderingService->getTextAreaDataAttributes(
            ready: true,
            mode: 'modal',
            readonly: false,
            globalDocument: false, // API endpoints use embedded documents, not global
            documentType: MetaData::DEFAULT_DOCUMENT_TYPE,
            includes: true, // API endpoints support document inheritance
            parameters: [],
            contextIdentifier: $contextIdentifier,
            uid: $uid
        );

        // Convert data attributes to Drupal's attribute format
        $attributes = ['class' => ['dmf-configuration-document']];
        foreach ($dataAttributes as $key => $value) {
            $attributes['data-' . $key] = $value;
        }

        $form['general']['configuration_document'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Configuration Document'),
            '#default_value' => $endpoint->getConfigurationDocument(),
            '#description' => $this->t('YAML configuration for this endpoint.'),
            '#rows' => 10,
            '#attributes' => $attributes,
        ];

        // Push tab
        $form['push'] = [
            '#type' => 'details',
            '#title' => $this->t('Push'),
            '#group' => 'tabs',
            '#weight' => 1,
        ];

        $form['push']['push_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Push Enabled'),
            '#default_value' => $endpoint->getPushEnabled(),
            '#description' => $this->t('Enable push operations for this endpoint.'),
        ];

        $form['push']['disable_context'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Disable Context Processing'),
            '#default_value' => $endpoint->getDisableContext(),
            '#description' => $this->t('Disable context processing for this endpoint.'),
        ];

        $form['push']['allow_context_override'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Allow Context Override'),
            '#default_value' => $endpoint->getAllowContextOverride(),
            '#description' => $this->t('Allow context to be overridden.'),
        ];

        // Pull tab
        $form['pull'] = [
            '#type' => 'details',
            '#title' => $this->t('Pull'),
            '#group' => 'tabs',
            '#weight' => 2,
        ];

        $form['pull']['pull_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Pull Enabled'),
            '#default_value' => $endpoint->getPullEnabled(),
            '#description' => $this->t('Enable pull operations for this endpoint.'),
        ];

        return $form;
    }

    /**
     * @param array<mixed> $form
     *
     * @return array<string,mixed>
     */
    protected function actions(array $form, FormStateInterface $form_state): array
    {
        $actions = parent::actions($form, $form_state);

        // Get the return URL from controller (passed via form state)
        $returnUrl = $this->getReturnUrl($form_state);

        // Update delete button to redirect to our return URL after deletion
        if (isset($actions['delete'])) {
            // Add destination query parameter to delete link
            $actions['delete']['#url']->setOption('query', ['destination' => $returnUrl]);
        }

        // Add "Cancel" button
        $actions['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => Url::fromUserInput($returnUrl),
            '#attributes' => [
                'class' => ['button'],
            ],
            '#weight' => 15,
        ];

        // Add "Save and continue editing" button
        $actions['save_continue'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save and continue editing'),
            '#submit' => ['::submitForm', '::save', '::saveAndContinue'],
            '#weight' => 10,
        ];

        // Adjust weight of default Save button
        $actions['submit']['#weight'] = 5;

        return $actions;
    }

    /**
     * Get the return URL passed from controller.
     */
    protected function getReturnUrl(FormStateInterface $form_state): string
    {
        // Get from form_state storage (passed by ApiEditSectionController with 'dmf_' prefix)
        return $form_state->get('dmf_returnUrl') ?? '/admin/dmf';
    }

    /**
     * Get the edit URL passed from controller.
     */
    protected function getEditUrl(FormStateInterface $form_state): string
    {
        // Get from form_state storage (passed by ApiEditSectionController with 'dmf_' prefix)
        return $form_state->get('dmf_editUrl') ?? '';
    }

    /**
     * Form submission handler for "Save and continue editing".
     *
     * @param array<mixed> $form
     */
    public function saveAndContinue(array $form, FormStateInterface $form_state): void
    {
        // Get edit URL from build info (passed by controller)
        $editUrl = $this->getEditUrl($form_state);
        if ($editUrl !== '') {
            $form_state->setRedirectUrl(Url::fromUserInput($editUrl));
        }
    }

    /**
     * @param array<mixed> $form
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        /** @var ApiEndpoint $endpoint */
        $endpoint = $this->entity;

        // Set the name from the label field
        $endpoint->setName($form_state->getValue('label'));

        $status = $endpoint->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Created the %label API endpoint.', [
                '%label' => $endpoint->getName(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('Saved the %label API endpoint.', [
                '%label' => $endpoint->getName(),
            ]));
        }

        // Get return URL from form state (passed by controller)
        $returnUrl = $this->getReturnUrl($form_state);
        $form_state->setRedirectUrl(Url::fromUserInput($returnUrl));

        return $status;
    }

    /**
     * Helper function to check whether an API endpoint configuration entity exists.
     */
    public function exist(string $id): bool
    {
        $entity = $this->entityTypeManager
          ->getStorage('dmf_api_endpoint')
          ->getQuery()
          ->condition('id', $id)
          ->accessCheck(false)
          ->execute();

        return (bool)$entity;
    }
}
