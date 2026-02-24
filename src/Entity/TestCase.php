<?php

namespace Drupal\dmf_core\Entity;

use DigitalMarketingFramework\Core\Model\TestCase\TestCaseInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use JsonException;

/**
 * Defines the Test Case entity.
 *
 * @ConfigEntityType(
 *   id = "dmf_test_case",
 *   label = @Translation("Test Case"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Config\Entity\ConfigEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dmf_core\Form\TestCaseForm",
 *       "edit" = "Drupal\dmf_core\Form\TestCaseForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "dmf_test_case",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/dmf/test-cases",
 *     "add-form" = "/admin/dmf/test-case/add",
 *     "edit-form" = "/admin/dmf/test-case/{dmf_test_case}/edit",
 *     "delete-form" = "/admin/dmf/test-case/{dmf_test_case}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "name",
 *     "description",
 *     "type",
 *     "hash",
 *     "serialized_input",
 *     "serialized_expected_output",
 *   }
 * )
 */
class TestCase extends ConfigEntityBase implements TestCaseInterface
{
    /**
     * The test case ID.
     */
    protected string $id;

    /**
     * The test case label.
     */
    protected string $label = '';

    /**
     * The test case name.
     */
    protected string $name = '';

    /**
     * The test case description.
     */
    protected string $description = '';

    /**
     * The test processor type.
     */
    protected string $type = '';

    /**
     * Hash for tracking changes.
     */
    protected string $hash = '';

    /**
     * JSON-encoded input data.
     */
    protected string $serialized_input = '';

    /**
     * JSON-encoded expected output data.
     */
    protected string $serialized_expected_output = '';

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
    public function setLabel(string $label): void
    {
        $this->label = $label;
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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * {@inheritdoc}
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function getInput(): array
    {
        if ($this->serialized_input === '') {
            return [];
        }

        try {
            return json_decode($this->serialized_input, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setInput(array $input): void
    {
        try {
            $this->serialized_input = json_encode($input, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            try {
                $this->serialized_input = json_encode($input, flags: JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            } catch (JsonException) {
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedOutput(): array
    {
        if ($this->serialized_expected_output === '') {
            return [];
        }

        try {
            return json_decode($this->serialized_expected_output, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setExpectedOutput(array $expectedOutput): void
    {
        try {
            $this->serialized_expected_output = json_encode($expectedOutput, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            try {
                $this->serialized_expected_output = json_encode($expectedOutput, flags: JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            } catch (JsonException) {
            }
        }
    }
}
