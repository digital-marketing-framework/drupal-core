<?php

namespace Drupal\dmf_core\Frontend;

use DigitalMarketingFramework\Core\Frontend\FrontendUriBuilderInterface;
use Drupal\Core\Url;

class DrupalFrontendUriBuilder implements FrontendUriBuilderInterface
{
    public function build(string $uri): string
    {
        // A bare numeric value is shorthand for a node ID.
        if (ctype_digit($uri)) {
            $uri = 'entity:node/' . $uri;
        }

        return Url::fromUri($uri)->toString();
    }
}
