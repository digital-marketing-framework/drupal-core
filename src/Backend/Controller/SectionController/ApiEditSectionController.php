<?php

namespace Drupal\dmf_core\Backend\Controller\SectionController;

use DigitalMarketingFramework\Core\Registry\RegistryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * API Endpoint edit controller for Drupal.
 *
 * Handles the 'edit' action for API endpoints using Drupal's EntityForm.
 */
class ApiEditSectionController extends EditSectionController
{
    /**
     * Constructor.
     */
    public function __construct(
        string $keyword,
        RegistryInterface $registry,
        EntityFormBuilderInterface $entityFormBuilder,
        EntityTypeManagerInterface $entityTypeManager,
        RendererInterface $renderer,
    ) {
        parent::__construct(
            $keyword,
            $registry,
            'api',
            $entityFormBuilder,
            $entityTypeManager,
            $renderer
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityTypeId(): string
    {
        return 'dmf_api_endpoint';
    }

    /**
     * {@inheritdoc}
     */
    protected function getListRoute(): string
    {
        return 'page.api.list';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditRoute(): string
    {
        return 'page.api.edit';
    }

    /**
     * {@inheritdoc}
     */
    protected function addEditAssets(): void
    {
        $this->addConfigurationEditorAssets();
    }
}
