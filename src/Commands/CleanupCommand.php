<?php

namespace Drupal\dmf_core\Commands;

use Drupal\dmf_core\Registry\RegistryCollection;
use Drush\Commands\DrushCommands;

/**
 * Drush command for Anyrel cleanup tasks.
 */
class CleanupCommand extends DrushCommands
{
    /**
     * The registry collection.
     *
     * @var \Drupal\dmf_core\Registry\RegistryCollection
     */
    protected RegistryCollection $registryCollection;

    /**
     * Constructs a CleanupCommand object.
     *
     * @param \Drupal\dmf_core\Registry\RegistryCollection $registryCollection
     *   The registry collection.
     */
    public function __construct(RegistryCollection $registryCollection)
    {
        parent::__construct();
        $this->registryCollection = $registryCollection;
    }

    /**
     * Execute all Anyrel cleanup tasks.
     *
     * @command anyrel:cleanup
     * @aliases anyrel-cleanup
     * @usage anyrel:cleanup
     *   Execute all Anyrel cleanup tasks.
     */
    public function cleanup(): void
    {
        $this->output()->writeln('Executing Anyrel cleanup tasks...');

        $cleanupManager = $this->registryCollection->getRegistry()->getCleanupManager();

        $cleanupManager->cleanup();

        $this->output()->writeln('Anyrel cleanup tasks executed successfully.');
    }
}
