<?php

namespace Drupal\dmf_core\Backend\Controller\SectionController;

use DigitalMarketingFramework\Core\Api\EndPoint\EndPointStorageInterface;
use DigitalMarketingFramework\Core\Backend\Controller\SectionController\ApiSectionController;
use DigitalMarketingFramework\Core\Backend\Response\Response;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * API Endpoint edit controller for Drupal.
 *
 * Extends core ApiSectionController to add Drupal-specific edit/save actions.
 */
class ApiEditSectionController extends ApiSectionController
{
    public const WEIGHT = 0;

    /**
     * API endpoint storage repository.
     */
    protected EndPointStorageInterface $endPointStorage;

    /**
     * Constructor.
     */
    public function __construct(
        string $keyword,
        RegistryInterface $registry,
        protected EntityFormBuilderInterface $entityFormBuilder,
        protected RendererInterface $renderer
    ) {
        parent::__construct($keyword, $registry);
        $this->endPointStorage = $registry->getEndPointStorage();
    }

    /**
     * {@inheritdoc}
     */
    protected function editAction(): Response
    {
        $params = $this->getParameters();
        $id = $params['id'] ?? '';

        // Load the entity via repository (CMS-agnostic)
        $entity = $this->endPointStorage->fetchById($id);

        if (!$entity) {
            // Entity not found
            throw new DigitalMarketingFrameworkException(sprintf('API endpoint "%s" not found', $id));
        }

        // Get returnUrl from parameters or default to list
        $returnUrl = $params['returnUrl'] ?? $this->registry->getBackendUriBuilder()->build('page.api.list');

        // Build edit URL for "Save and continue editing" (include returnUrl so it persists)
        $editUrl = $this->registry->getBackendUriBuilder()->build('page.api.edit', [
            'id' => $id,
            'returnUrl' => $returnUrl,
        ]);

        $this->addConfigurationEditorAssets();

        // Build the entity form with URLs passed via form_state storage (Drupal-specific)
        // Use 'dmf_' prefix to avoid conflicts with other modules
        $form = $this->entityFormBuilder->getForm($entity, 'edit', [
            'dmf_returnUrl' => $returnUrl,
            'dmf_editUrl' => $editUrl,
        ]);

        // Convert form render array to HTML
        $formHtml = $this->renderer->renderRoot($form);

        // Add form HTML and entity to viewData for template rendering
        $this->viewData['formHtml'] = $formHtml;
        $this->viewData['endpoint'] = $entity;

        return $this->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function saveAction(): Response
    {
        // Drupal's entity form handles submission automatically via form API
        // The form in editAction() includes submit handlers that save the entity
        // So when a form is submitted, it goes through Drupal's form system,
        // not through this saveAction()
        //
        // For now, just redirect back to the list
        // In practice, the form's submit handler will save and redirect
        return $this->redirect('page.api.list');
    }
}
