<?php

namespace Drupal\dmf_core\Backend;

use DigitalMarketingFramework\Core\Backend\AssetUriBuilderInterface;
use DigitalMarketingFramework\Core\Registry\RegistryInterface;

/**
 * Builds URIs for Anyrel assets in Drupal.
 */
class AssetUriBuilder implements AssetUriBuilderInterface
{
    /**
     * Constructor.
     *
     * @param \DigitalMarketingFramework\Core\Registry\RegistryInterface $registry
     *   The Anyrel registry.
     */
    public function __construct(
        protected RegistryInterface $registry,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $path): string
    {
        return '/' . $this->registry->getAssetService()->makeAssetPublic($path);
    }
}