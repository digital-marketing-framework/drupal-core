<?php

namespace Drupal\dmf_core\Backend\Controller\SectionController;

use DigitalMarketingFramework\Core\Backend\Controller\SectionController\SectionController;
use DigitalMarketingFramework\Core\Backend\Response\Response;
use DigitalMarketingFramework\Core\Exception\DigitalMarketingFrameworkException;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Abstract base class for Drupal entity edit section controllers.
 *
 * Provides edit functionality by rendering Drupal's EntityForm within
 * Anyrel's backend template system. Each subclass only needs to specify
 * the entity type ID and route names.
 */
abstract class EditSectionController extends SectionController
{
    public const WEIGHT = 0;

    /**
     * Constructor.
     */
    public function __construct(
        string $keyword,
        RegistryInterface $registry,
        string $section,
        protected EntityFormBuilderInterface $entityFormBuilder,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected RendererInterface $renderer,
        array $routes = ['edit'],
    ) {
        parent::__construct($keyword, $registry, $section, $routes);
    }

    /**
     * Returns the Drupal entity type ID.
     *
     * @return string
     *   The entity type ID (e.g., 'dmf_distributor_job', 'dmf_api_endpoint').
     */
    abstract protected function getEntityTypeId(): string;

    /**
     * Returns the route name for the list page.
     *
     * Used for default returnUrl when none is provided.
     *
     * @return string
     *   The route name for the list page.
     */
    abstract protected function getListRoute(): string;

    /**
     * Returns the route name for the edit page.
     *
     * Used for "Save and continue editing" functionality.
     *
     * @return string
     *   The route name for the edit page.
     */
    abstract protected function getEditRoute(): string;

    /**
     * Returns additional form state options for the EntityForm.
     *
     * Override this method to pass additional options to the form.
     *
     * @param string $returnUrl
     *   The return URL.
     * @param string $editUrl
     *   The edit URL for "Save and continue editing".
     *
     * @return array
     *   Additional form state options.
     */
    protected function getFormOptions(string $returnUrl, string $editUrl): array
    {
        return [];
    }

    /**
     * Allows subclasses to add additional assets before rendering.
     *
     * Override this method to add CSS/JS assets.
     */
    protected function addEditAssets(): void
    {
        // Default: no additional assets
    }

    /**
     * {@inheritdoc}
     */
    protected function editAction(): Response
    {
        $id = $this->getParameters()['id'] ?? null;

        if ($id === null) {
            throw new DigitalMarketingFrameworkException('No entity ID provided for editing');
        }

        // Load the Drupal entity directly via EntityTypeManager
        $storage = $this->entityTypeManager->getStorage($this->getEntityTypeId());
        $entity = $storage->load($id);

        if (!$entity) {
            throw new DigitalMarketingFrameworkException(
                sprintf('%s "%s" not found', $this->getEntityTypeId(), $id)
            );
        }

        // Get returnUrl from parameters or default to list
        $returnUrl = $this->getReturnUrl($this->uriBuilder->build($this->getListRoute()));

        // Build edit URL for "Save and continue editing"
        $editUrl = $this->uriBuilder->build($this->getEditRoute(), [
            'id' => $id,
            'returnUrl' => $returnUrl,
        ]);

        // Add any additional assets
        $this->addEditAssets();

        // Build the entity form with standard + custom options
        $formOptions = array_merge([
            'dmf_returnUrl' => $returnUrl,
            'dmf_editUrl' => $editUrl,
        ], $this->getFormOptions($returnUrl, $editUrl));

        $form = $this->entityFormBuilder->getForm($entity, 'edit', $formOptions);

        // Convert form render array to HTML
        $formHtml = $this->renderer->renderRoot($form);

        // Add form HTML and entity to viewData for template rendering
        $this->assignCurrentRouteData();
        $this->viewData['formHtml'] = $formHtml;
        $this->viewData['entity'] = $entity;

        return $this->render();
    }

    /**
     * {@inheritdoc}
     *
     * Drupal's entity form handles submission automatically via form API.
     * This action just redirects back to the list.
     */
    protected function saveAction(): Response
    {
        return $this->redirect($this->getListRoute());
    }
}
