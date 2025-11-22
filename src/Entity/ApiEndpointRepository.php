<?php

namespace Drupal\dmf_core\Entity;

use DigitalMarketingFramework\Core\Api\EndPoint\EndPointSchema;
use DigitalMarketingFramework\Core\Api\EndPoint\EndPointStorageInterface;
use DigitalMarketingFramework\Core\Model\Api\EndPointInterface;
use DigitalMarketingFramework\Core\SchemaDocument\Schema\ContainerSchema;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Repository for API Endpoint entities.
 *
 * Implements Anyrel's EndPointStorageInterface using Drupal's entity system.
 */
class ApiEndpointRepository implements EndPointStorageInterface
{
    /**
     * Drupal's entity storage for dmf_api_endpoint entities.
     *
     * @var EntityStorageInterface
     */
    protected $storage;

    /**
     * Constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     *   The entity type manager.
     *
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->storage = $entityTypeManager->getStorage('dmf_api_endpoint');
    }

    /**
     * {@inheritdoc}
     */
    public function create(?array $data = null)
    {
        $data = $data ?? [];

        // Generate unique ID if not provided
        if (!isset($data['id'])) {
            $name = $data['name'] ?? 'endpoint';
            $data['id'] = $this->generateUniqueId($name);
        }

        // Set label from name if not provided
        if (!isset($data['label']) && isset($data['name'])) {
            $data['label'] = $data['name'];
        }

        return $this->storage->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function add($item): void
    {
        /** @var EntityInterface $item */
        $item->save();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($item): void
    {
        /** @var EntityInterface $item */
        $item->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function update($item): void
    {
        /** @var EntityInterface $item */
        $item->save();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchById(int|string $id)
    {
        return $this->storage->load($id);
    }

    /**
     * {@inheritdoc}
     */
    public function countAll(): int
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(FALSE);
        return $query->count()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(?array $navigation = null): array
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(FALSE);

        $this->applyNavigation($query, $navigation);

        $ids = $query->execute();
        if (empty($ids)) {
            return [];
        }

        return $this->storage->loadMultiple($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function countFiltered(array $filters): int
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(FALSE);

        $this->applyFilters($query, $filters);

        return $query->count()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFiltered(array $filters, ?array $navigation = null): array
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(FALSE);

        $this->applyFilters($query, $filters);
        $this->applyNavigation($query, $navigation);

        $ids = $query->execute();
        if (empty($ids)) {
            return [];
        }

        return $this->storage->loadMultiple($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOneFiltered(array $filters, ?array $navigation = null)
    {
        $query = $this->storage->getQuery();
        $query->accessCheck(FALSE);
        $query->range(0, 1);

        $this->applyFilters($query, $filters);
        $this->applyNavigation($query, $navigation);

        $ids = $query->execute();
        if (empty($ids)) {
            return null;
        }

        return $this->storage->load(reset($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function fetchByIdList(array $ids): array
    {
        return $this->storage->loadMultiple($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchByName(string $name): ?EndPointInterface
    {
        return $this->fetchOneFiltered(['name' => $name]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): ContainerSchema
    {
        return new EndPointSchema();
    }

    /**
     * Apply filters to entity query.
     *
     * @param \Drupal\Core\Entity\Query\QueryInterface $query
     *   The entity query.
     * @param array $filters
     *   Array of filters (field => value).
     */
    protected function applyFilters($query, array $filters): void
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
     * @param \Drupal\Core\Entity\Query\QueryInterface $query
     *   The entity query.
     * @param array|null $navigation
     *   Navigation parameters (page/itemsPerPage or limit/offset, plus sorting).
     */
    protected function applyNavigation($query, ?array $navigation): void
    {
        if ($navigation === null) {
            return;
        }

        // Handle pagination
        if (isset($navigation['page']) && isset($navigation['itemsPerPage'])) {
            $offset = $navigation['page'] * $navigation['itemsPerPage'];
            $limit = $navigation['itemsPerPage'];
            $query->range($offset, $limit);
        } elseif (isset($navigation['limit'])) {
            $offset = $navigation['offset'] ?? 0;
            $query->range($offset, $navigation['limit']);
        }

        // Handle sorting
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
     *   The human-readable name.
     *
     * @return string
     *   A unique machine name ID.
     */
    protected function generateUniqueId(string $name): string
    {
        // Convert to lowercase and replace non-alphanumeric chars with underscore
        $id = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $id = trim($id, '_');

        // Ensure we have something
        if ($id === '') {
            $id = 'endpoint';
        }

        // Check for uniqueness and append counter if needed
        $baseId = $id;
        $counter = 1;
        while ($this->storage->load($id)) {
            $id = $baseId . '_' . $counter++;
        }

        return $id;
    }
}
