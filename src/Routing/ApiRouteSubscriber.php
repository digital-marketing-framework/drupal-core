<?php

namespace Drupal\dmf_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\dmf_core\Utility\ApiUtility;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically generates API routes based on Anyrel's available routes.
 *
 * This route subscriber bootstraps Anyrel during cache rebuild and queries
 * the EntryRouteResolver for all available API routes. Each route is then
 * registered as a separate Drupal route, avoiding the need for catch-all
 * patterns or path processors.
 */
class ApiRouteSubscriber extends RouteSubscriberBase
{
    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection): void
    {
        // Check if API is enabled without bootstrapping Anyrel
        if (!ApiUtility::enabled()) {
            return;
        }

        // Get configured base path
        $basePath = ApiUtility::getBasePath();

        // Bootstrap Anyrel to get available routes
        // Note: This only happens during cache rebuild, not on every request
        $registryCollection = \Drupal::service('dmf_core.registry_collection');
        $entryRouteResolver = $registryCollection->getApiEntryRouteResolver();

        // Get all available API routes from Anyrel
        $apiRoutes = $entryRouteResolver->getAllResourceRoutes();

        // Generate a Drupal route for each Anyrel API route
        foreach ($apiRoutes as $index => $simpleRoute) {
            // Get the route path (starts with /, no base path, no version)
            $routePath = $simpleRoute->getPath();
            if ($routePath !== '') {
                $routePath = '/' . $routePath;
            }

            // Build full Drupal path with dynamic version parameter
            // Example: /digital-marketing-framework/api/{version}/permissions
            $fullPath = '/' . $basePath . '/{version}' . $routePath;

            // The api_route parameter is just the route path without version
            // Controller will prepend the version from the route parameter
            $apiRouteParam = ltrim($routePath, '/');

            // Create Drupal route
            $route = new Route(
                $fullPath,
                [
                    '_controller' => '\Drupal\dmf_core\Controller\ApiController::handle',
                    '_title' => 'Anyrel API',
                    'api_route' => $apiRouteParam,
                ],
                [
                    '_permission' => 'access content',
                    'version' => 'v\d+', // Matches v1, v2, v3, etc.
                ]
            );

            // Allow all HTTP methods
            $route->setMethods(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);

            // Add route to collection with unique name
            $collection->add('dmf_core.api.' . $index, $route);
        }
    }
}
