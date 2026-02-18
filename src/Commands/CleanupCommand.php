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
     * Constructs a CleanupCommand object.
     *
     * @param RegistryCollection $registryCollection
     *   The registry collection
     */
    public function __construct(/**
                                 * The registry collection.
                                 */
        protected RegistryCollection $registryCollection,
    ) {
        parent::__construct();
    }

    /**
     * Execute all Anyrel cleanup tasks.
     *
     * @command anyrel:cleanup
     *
     * @aliases anyrel-cleanup
     *
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
