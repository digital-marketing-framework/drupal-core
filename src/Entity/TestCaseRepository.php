<?php

namespace Drupal\dmf_core\Entity;

use DigitalMarketingFramework\Core\Model\TestCase\TestCase as CoreTestCase;
use DigitalMarketingFramework\Core\Model\TestCase\TestCaseInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use DigitalMarketingFramework\Core\TestCase\TestCaseSchema;
use DigitalMarketingFramework\Core\TestCase\TestCaseStorageInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Repository for Test Case entities.
 *
 * Implements Anyrel's TestCaseStorageInterface using Drupal's entity system.
 */
class TestCaseRepository implements TestCaseStorageInterface
{
    /**
     * Drupal's entity storage for dmf_test_case entities.
     */
    protected EntityStorageInterface $storage;

    /**
     * Constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     *   The entity type manager
     *
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->storage = $entityTypeManager->getStorage('dmf_test_case');
    }

    /**
     * Converts a Drupal TestCase entity to a core TestCase object.
     *
     * @param TestCase $entity
     *   The Drupal TestCase entity
     *
     * @return CoreTestCase
     *   The core TestCase object
     */
    protected function entityToTestCase(TestCase $entity): CoreTestCase
    {
        $testCase = new CoreTestCase(
            label: $entity->getLabel(),
            name: $entity->getName(),
            description: $entity->getDescription(),
            type: $entity->getType(),
            hash: $entity->getHash(),
            serializedInput: $entity->get('serialized_input') ?? '',
            serializedExpectedOutput: $entity->get('serialized_expected_output') ?? '',
        );
        $testCase->setId($entity->id());

        return $testCase;
    }

    /**
     * Updates a Drupal TestCase entity from a core TestCase object.
     *
     * @param TestCase $entity
     *   The Drupal TestCase entity to update
     * @param TestCaseInterface $testCase
     *   The core TestCase object with updated values
     */
    protected function updateEntityFromTestCase(TestCase $entity, TestCaseInterface $testCase): void
    {
        $entity->setLabel($testCase->getLabel());
        $entity->setName($testCase->getName());
        $entity->setDescription($testCase->getDescription());
        $entity->setType($testCase->getType());
        $entity->setHash($testCase->getHash());
        $entity->setInput($testCase->getInput());
        $entity->setExpectedOutput($testCase->getExpectedOutput());
    }

    /**
     * Converts an array of Drupal TestCase entities to core TestCase objects.
     *
     * @param array<EntityInterface> $entities
     *   Array of Drupal TestCase entities
     *
     * @return array<CoreTestCase>
     *   Array of core TestCase objects
     */
    protected function entitiesToTestCases(array $entities): array
    {
        /** @var array<TestCase> $entities */
        return array_map($this->entityToTestCase(...), $entities);
    }

    /**
     * {@inheritdoc}
     */
    public function create(?array $data = null)
    {
        $data ??= [];

        // Generate unique ID if not provided.
        if (!isset($data['id'])) {
            $name = $data['name'] ?? 'test_case';
            $data['id'] = $this->generateUniqueId($name);
        }

        // Set label from name if not provided.
        if (!isset($data['label']) && isset($data['name'])) {
            $data['label'] = $data['name'];
        }

        /** @var TestCase */
        return $this->storage->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function add($item): void
    {
        if ($item instanceof TestCase) {
            $item->save();

            return;
        }

        // Core TestCase model - create entity and copy data.
        $id = $item->getId();
        if ($id !== null) {
            $entity = $this->storage->load($id);
        }

        if (!isset($entity)) {
            $data = ['id' => $this->generateUniqueId($item->getName())];
            $entity = $this->storage->create($data);
        }

        /** @var TestCase $entity */
        $this->updateEntityFromTestCase($entity, $item);
        $entity->save();

        if ($item->getId() === null) {
            $item->setId($entity->id());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($item): void
    {
        if ($item instanceof TestCase) {
            $item->delete();

            return;
        }

        $id = $item->getId();
        if ($id !== null) {
            $entity = $this->storage->load($id);
            if ($entity !== null) {
                $entity->delete();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($item): void
    {
        if ($item instanceof TestCase) {
            $item->save();

            return;
        }

        // Core TestCase model - load entity and update.
        $id = $item->getId();
        if ($id === null) {
            $this->add($item);

            return;
        }

        $entity = $this->storage->load($id);
        if ($entity === null) {
            $this->add($item);

            return;
        }

        /** @var TestCase $entity */
        $this->updateEntityFromTestCase($entity, $item);
        $entity->save();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchById(int|string $id)
    {
        $entity = $this->storage->load($id);

        return $entity instanceof TestCase ? $this->entityToTestCase($entity) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function countAll(): int
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(false);

        return $query->count()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(?array $navigation = null): array
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(false);

        $this->applyNavigation($query, $navigation);

        $ids = $query->execute();
        if ($ids === []) {
            return [];
        }

        return $this->entitiesToTestCases($this->storage->loadMultiple($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function countFiltered(array $filters): int
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(false);

        $this->applyFilters($query, $filters);

        return $query->count()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFiltered(array $filters, ?array $navigation = null): array
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(false);

        $this->applyFilters($query, $filters);
        $this->applyNavigation($query, $navigation);

        $ids = $query->execute();
        if ($ids === []) {
            return [];
        }

        return $this->entitiesToTestCases($this->storage->loadMultiple($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOneFiltered(array $filters, ?array $navigation = null)
    {
        $results = $this->fetchFiltered(
            $filters,
            $navigation !== null ? array_merge($navigation, ['limit' => 1]) : ['limit' => 1]
        );

        return $results[0] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchByIdList(array $ids): array
    {
        return $this->entitiesToTestCases($this->storage->loadMultiple($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchByType(string $type): array
    {
        return $this->fetchFiltered(['type' => $type]);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchByName(string $name): array
    {
        return $this->fetchFiltered(['name' => $name]);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAllTypes(): array
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(false);

        $ids = $query->execute();
        if ($ids === []) {
            return [];
        }

        $types = [];
        /** @var TestCase $entity */
        foreach ($this->storage->loadMultiple($ids) as $entity) {
            $type = $entity->getType();
            if ($type !== '' && !in_array($type, $types, true)) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): ContainerSchema
    {
        return new TestCaseSchema();
    }

    /**
     * Apply filters to entity query.
     *
     * @param array<string, mixed> $filters
     *   Array of filters (field => value)
     */
    protected function applyFilters(QueryInterface $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->condition($field, $value, 'IN');
            } else {
                $query->condition($field, $value);
            }
        }
    }

    /**
     * Apply navigation (pagination/sorting) to entity query.
     *
     * @param array<string, mixed>|null $navigation
     *   Navigation parameters (page/itemsPerPage or limit/offset, plus sorting)
     */
    protected function applyNavigation(QueryInterface $query, ?array $navigation): void
    {
        if ($navigation === null) {
            return;
        }

        // Handle pagination.
        if (isset($navigation['page']) && isset($navigation['itemsPerPage'])) {
            $offset = $navigation['page'] * $navigation['itemsPerPage'];
            $limit = $navigation['itemsPerPage'];
            $query->range($offset, $limit);
        } elseif (isset($navigation['limit'])) {
            $offset = $navigation['offset'] ?? 0;
            $query->range($offset, $navigation['limit']);
        }

        // Handle sorting.
        if (isset($navigation['sorting']) && is_array($navigation['sorting'])) {
            foreach ($navigation['sorting'] as $field => $direction) {
                $query->sort($field, $direction);
            }
        }
    }

    /**
     * Generate a unique machine name ID from a human-readable name.
     *
     * @param string $name
     *   The human-readable name
     *
     * @return string
     *   A unique machine name ID
     */
    protected function generateUniqueId(string $name): string
    {
        // Convert to lowercase and replace non-alphanumeric chars with underscore.
        $id = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $id = trim($id, '_');

        // Ensure we have something.
        if ($id === '') {
            $id = 'test_case';
        }

        // Check for uniqueness and append counter if needed.
        $baseId = $id;
        $counter = 1;
        while ($this->storage->load($id)) {
            $id = $baseId . '_' . $counter++;
        }

        return $id;
    }
}
