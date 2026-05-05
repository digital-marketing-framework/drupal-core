<?php

namespace Drupal\dmf_core\Controller;

use DigitalMarketingFramework\Core\Api\RouteResolver\EntryRouteResolverInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\dmf_core\Registry\RegistryCollection;
use Exception;
use JsonException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for handling Anyrel API requests.
 *
 * This controller processes API requests by delegating to Anyrel's
 * EntryRouteResolver and returning JSON responses. It completely bypasses
 * Drupal's page rendering and theming system for optimal performance.
 */
class ApiController extends ControllerBase
{
    /**
     * Constructs an ApiController object.
     *
     * @param RegistryCollection $registryCollection
     *   The Anyrel registry collection service
     */
    public function __construct(
        protected RegistryCollection $registryCollection,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('dmf_core.registry_collection')
        );
    }

    /**
     * Handles API endpoint requests.
     *
     * This method:
     * 1. Extracts request data (method, query params, body)
     * 2. Delegates to Anyrel's EntryRouteResolver
     * 3. Converts Anyrel's ApiResponse to Symfony JsonResponse
     * 4. Returns JSON without any Drupal rendering.
     *
     * The version and route path are passed as route parameters.
     *
     * @param string $version
     *   The API version (e.g., 'v1', 'v2').
     * @param string $api_route
     *   The API route path (e.g., 'permissions', 'distributor/endpoint').
     * @param Request $request
     *   The current request object
     *
     * @return Response
     *   JSON response with API data, or a redirect response when the API
     *   response carries a redirect URL
     */
    public function handle(string $version, string $api_route, Request $request): Response
    {
        try {
            // Combine version and route path for Anyrel
            // Example: version='v1', api_route='permissions' -> 'v1/permissions'.
            $apiRoute = $version . '/' . $api_route;

            // Get Anyrel's route resolver (bootstraps Anyrel on first call)
            $routeResolver = $this->getRouteResolver();

            // Extract HTTP method.
            $method = $request->getMethod();

            // Extract query parameters.
            $query = $request->query->all();

            // Parse body based on Content-Type.
            $body = null;
            $contentType = (string)$request->headers->get('Content-Type', '');
            if ($contentType !== '' && (str_contains($contentType, 'application/x-www-form-urlencoded') || str_contains($contentType, 'multipart/form-data'))) {
                // Form-encoded body: treat parsed fields as the payload (no
                // wrapper). Context cannot be supplied this way; form clients
                // (Pardot/Web-to-Lead style) carry context naturally via
                // cookies and headers.
                $parsed = $request->request->all();
                if ($parsed !== []) {
                    $body = ['payload' => $parsed];
                }
            } else {
                // JSON body (default): {payload: ..., context: ...}
                $content = $request->getContent();
                if ($content !== '') {
                    try {
                        $body = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        // Invalid JSON - leave as null.
                        $body = null;
                    }
                }
            }

            // Build Anyrel API request.
            $apiRequest = $routeResolver->buildRequest($apiRoute, $method, $query, $body);

            // Resolve request through Anyrel.
            $apiResponse = $routeResolver->resolveRequest($apiRequest);

            $redirectUrl = $apiResponse->getRedirectUrl();
            if ($redirectUrl !== null && $redirectUrl !== '') {
                $redirect = new TrustedRedirectResponse($redirectUrl, $apiResponse->getStatusCode());
                $redirect->headers->set('Cache-Control', 'no-store, must-revalidate');

                return $redirect;
            }

            // Convert Anyrel's ApiResponse to Symfony JsonResponse.
            $responseData = json_decode($apiResponse->getContent(), true);
            $statusCode = $apiResponse->getStatusCode();

            $response = new JsonResponse($responseData, $statusCode);

            // Add cache control headers (no caching by default)
            $response->headers->set('Cache-Control', 'no-store, must-revalidate');

            return $response;
        } catch (Exception $e) {
            // Return error as JSON.
            return new JsonResponse(
                [
                    'error' => $e->getMessage(),
                    'status' => 'error',
                ],
                500
            );
        }
    }

    /**
     * Gets the Anyrel entry route resolver.
     *
     * This triggers the full Anyrel bootstrap on first call.
     *
     * @return EntryRouteResolverInterface
     *   The entry route resolver
     */
    protected function getRouteResolver(): EntryRouteResolverInterface
    {
        return $this->registryCollection->getApiEntryRouteResolver();
    }
}
