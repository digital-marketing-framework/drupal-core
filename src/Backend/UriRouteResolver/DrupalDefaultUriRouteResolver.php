<?php

namespace Drupal\dmf_core\Backend\UriRouteResolver;

use DigitalMarketingFramework\Core\Backend\UriRouteResolver\UriRouteResolver;
use Drupal\Core\Url;

class DrupalDefaultUriRouteResolver extends UriRouteResolver
{
    /**
     * @var int
     */
    public const WEIGHT = 100;

    protected function doResolve(string $route, array $arguments = []): ?string
    {
        $parameters = [
            'dmf' => [
                'route' => $route,
            ],
        ];
        if ($arguments !== []) {
            $parameters['dmf']['arguments'] = $arguments;
        }

        if (str_starts_with($route, 'page')) {
            $url = Url::fromRoute('dmf_core.backend', $parameters);
        } else {
            $url = Url::fromRoute('dmf_core.backend.ajax', $parameters);
        }

        return $url->toString();
    }
}
