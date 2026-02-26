<?php

namespace Drupal\dmf_core\Entity;

use DigitalMarketingFramework\Core\Model\TestCase\TestCaseInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use JsonException;

/**
 * Defines the Test Case entity.
 *
 * @ContentEntityType(
 *   id = "dmf_test_case",
 *   label = @Translation("Test Case"),
 *   base_table = "dmf_test_case",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "form" = {
 *       "add" = "Drupal\dmf_core\Form\TestCaseForm",
 *       "edit" = "Drupal\dmf_core\Form\TestCaseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer site configuration",
 * )
 */
class TestCase extends ContentEntityBase implements TestCaseInterface
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['label'] = BaseFieldDefinition::create('string')
          ->setLabel(t('Label'))
          ->setDescription(t('The human-readable label of the test case'))
          ->setDefaultValue('')
          ->setSettings([
              'max_length' => 255,
          ]);

        $fields['name'] = BaseFieldDefinition::create('string')
          ->setLabel(t('Name'))
          ->setDescription(t('The machine name of the test case'))
          ->setDefaultValue('')
          ->setSettings([
              'max_length' => 255,
          ]);

        $fields['description'] = BaseFieldDefinition::create('string_long')
          ->setLabel(t('Description'))
          ->setDescription(t('A description of the test case'))
          ->setDefaultValue('');

        $fields['type'] = BaseFieldDefinition::create('string')
          ->setLabel(t('Type'))
          ->setDescription(t('The test processor type'))
          ->setDefaultValue('')
          ->setSettings([
              'max_length' => 255,
          ]);

        $fields['hash'] = BaseFieldDefinition::create('string')
          ->setLabel(t('Hash'))
          ->setDescription(t('Hash for tracking changes'))
          ->setDefaultValue('')
          ->setSettings([
              'max_length' => 255,
          ]);

        $fields['serialized_input'] = BaseFieldDefinition::create('string_long')
          ->setLabel(t('Serialized Input'))
          ->setDescription(t('JSON-encoded input data'))
          ->setDefaultValue('');

        $fields['serialized_expected_output'] = BaseFieldDefinition::create('string_long')
          ->setLabel(t('Serialized Expected Output'))
          ->setDescription(t('JSON-encoded expected output data'))
          ->setDefaultValue('');

        $fields['created'] = BaseFieldDefinition::create('created')
          ->setLabel(t('Created'))
          ->setDescription(t('The time that the test case was created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
          ->setLabel(t('Changed'))
          ->setDescription(t('The time that the test case was last changed'));

        return $fields;
    }

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
        $label = $this->get('label')->getString();

        return $label !== '' ? $label : $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel(string $label): void
    {
        $this->set('label', $label);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->get('name')->getString();
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->set('name', $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->get('description')->getString();
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(string $description): void
    {
        $this->set('description', $description);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->get('type')->getString();
    }

    /**
     * {@inheritdoc}
     */
    public function setType(string $type): void
    {
        $this->set('type', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getHash(): string
    {
        return $this->get('hash')->getString();
    }

    /**
     * {@inheritdoc}
     */
    public function setHash(string $hash): void
    {
        $this->set('hash', $hash);
    }

    /**
     * {@inheritdoc}
     */
    public function getInput(): array
    {
        $data = $this->get('serialized_input')->getString();
        if ($data === '') {
            return [];
        }

        try {
            return json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);
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
            $this->set('serialized_input', json_encode($input, flags: JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            try {
                $this->set('serialized_input', json_encode($input, flags: JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
            } catch (JsonException) {
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedOutput(): array
    {
        $data = $this->get('serialized_expected_output')->getString();
        if ($data === '') {
            return [];
        }

        try {
            return json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);
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
            $this->set('serialized_expected_output', json_encode($expectedOutput, flags: JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            try {
                $this->set('serialized_expected_output', json_encode($expectedOutput, flags: JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
            } catch (JsonException) {
            }
        }
    }
}
