<?php

namespace Drupal\dmf_core\Backend;

use DigitalMarketingFramework\Core\Backend\UriBuilderInterface;
use Drupal\Core\Url;

class UriBuilder implements UriBuilderInterface
{
    public function build(string $route, array $arguments = []): string
    {
        $parameters = [
            'dmf' => [
                'route' => $route,
            ],
        ];
        if ($arguments !== []) {
            $parameters['dmf']['arguments'] = $arguments;
        }

        // Routes starting with "page.*" use the main backend route
        // Routes starting with "ajax.*" use the AJAX route
        if (str_starts_with($route, 'page')) {
            $url = Url::fromRoute('dmf_core.backend', $parameters);
        } else {
            $url = Url::fromRoute('dmf_core.backend.ajax', $parameters);
        }

        return $url->toString();
    }
}
