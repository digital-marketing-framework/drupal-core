<?php

namespace Drupal\dmf_core\Entity;

use DigitalMarketingFramework\Core\Model\Api\EndPointInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the API Endpoint entity.
 *
 * @ConfigEntityType(
 *   id = "dmf_api_endpoint",
 *   label = @Translation("API Endpoint"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Config\Entity\ConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dmf_core\Form\ApiEndpointForm",
 *       "edit" = "Drupal\dmf_core\Form\ApiEndpointForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "dmf_api_endpoint",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/dmf/api-endpoints",
 *     "add-form" = "/admin/dmf/api-endpoint/add",
 *     "edit-form" = "/admin/dmf/api-endpoint/{dmf_api_endpoint}/edit",
 *     "delete-form" = "/admin/dmf/api-endpoint/{dmf_api_endpoint}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "enabled",
 *     "push_enabled",
 *     "pull_enabled",
 *     "disable_context",
 *     "allow_context_override",
 *     "expose_to_frontend",
 *     "configuration_document",
 *   }
 * )
 */
class ApiEndpoint extends ConfigEntityBase implements EndPointInterface
{
    /**
     * The endpoint ID.
     */
    protected string $id;

    /**
     * The endpoint name.
     */
    protected string $name = '';

    /**
     * Master enabled flag.
     */
    protected bool $enabled = false;

    /**
     * Push enabled flag.
     */
    protected bool $push_enabled = false;

    /**
     * Pull enabled flag.
     */
    protected bool $pull_enabled = false;

    /**
     * Disable context flag.
     */
    protected bool $disable_context = false;

    /**
     * Allow context override flag.
     */
    protected bool $allow_context_override = false;

    /**
     * Expose to frontend flag.
     */
    protected bool $expose_to_frontend = false;

    /**
     * Configuration document (YAML).
     */
    protected string $configuration_document = '';

    /**
     * {@inheritdoc}
     */
    public function getId(): int|string|null
    {
        return $this->id();
    }

    /**
     * {@inheritdoc}
     */
    public function setId(int|string $id): void
    {
        $this->set('id', (string)$id);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return $this->label() ?? $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getPushEnabled(): bool
    {
        return $this->push_enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setPushEnabled(bool $pushEnabled): void
    {
        $this->push_enabled = $pushEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getPullEnabled(): bool
    {
        return $this->pull_enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setPullEnabled(bool $pullEnabled): void
    {
        $this->pull_enabled = $pullEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisableContext(): bool
    {
        return $this->disable_context;
    }

    /**
     * {@inheritdoc}
     */
    public function setDisableContext(bool $disableContext): void
    {
        $this->disable_context = $disableContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowContextOverride(): bool
    {
        return $this->allow_context_override;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowContextOverride(bool $allowContextOverride): void
    {
        $this->allow_context_override = $allowContextOverride;
    }

    /**
     * {@inheritdoc}
     */
    public function getExposeToFrontend(): bool
    {
        return $this->expose_to_frontend;
    }

    /**
     * {@inheritdoc}
     */
    public function setExposeToFrontend(bool $exposeToFrontend): void
    {
        $this->expose_to_frontend = $exposeToFrontend;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationDocument(): string
    {
        return $this->configuration_document;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigurationDocument(string $configurationDocument): void
    {
        $this->configuration_document = $configurationDocument;
    }
}
