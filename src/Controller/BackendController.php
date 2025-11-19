<?php

namespace Drupal\dmf_core\Controller;

use DigitalMarketingFramework\Core\Backend\Request;
use DigitalMarketingFramework\Core\Backend\Response\JsonResponse;
use DigitalMarketingFramework\Core\Backend\Response\RedirectResponse;
use Drupal\dmf_core\Registry\RegistryCollection;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for Anyrel.
 *
 * Routes all backend requests to Anyrel's BackendManager.
 *
 * This controller is a thin wrapper - all backend logic lives in the
 * CMS-agnostic core packages. Routes are dynamically registered by
 * backend sections as packages are installed.
 *
 * Route pattern examples from dmf_core:
 * - page.core.index (Overview/Dashboard)
 * - page.configuration-document.list
 * - page.configuration-document.edit [id]
 * - page.global-settings.edit
 * - ajax.configuration-editor.schema [domain]
 * - ajax.configuration-editor.defaults [domain]
 * - ajax.configuration-editor.merge [domain,document,parent]
 *
 * Additional routes become available when other packages are installed
 * (e.g., dmf_distributor_core, dmf_collector_core, integration packages).
 */
class BackendController
{
    public function __construct(
        protected RegistryCollection $registryCollection,
    ) {
    }

    /**
     * Get body data from request.
     *
     * @return array<string,mixed>
     */
    protected function getBodyData(SymfonyRequest $request): array
    {
        // For POST form submissions
        $body = $request->request->all();

        if (empty($body)) {
            try {
                // For POST AJAX requests with a JSON body
                $content = $request->getContent();
                if (!empty($content)) {
                    $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $body = $decoded;
                    }
                }
            } catch (JsonException) {
                $body = [];
            }
        }

        return $body;
    }

    /**
     * Handle backend page requests.
     */
    public function handleRequest(SymfonyRequest $request): Response
    {
        $params = $request->query->all('dmf') ?? [];
        $route = $params['route'] ?? '';
        $arguments = $params['arguments'] ?? [];
        $body = $this->getBodyData($request);
        $method = $request->getMethod();

        $req = new Request($route, $arguments, $body, $method);
        $result = $this->registryCollection->getRegistry()->getBackendManager()->getResponse($req);

        if ($result instanceof RedirectResponse) {
            return new SymfonyRedirectResponse($result->getRedirectLocation());
        } elseif ($result instanceof JsonResponse) {
            return new SymfonyJsonResponse($result->getData());
        }

        return new Response($result->getContent());
    }

    /**
     * Handle backend AJAX requests.
     */
    public function handleAjaxRequest(SymfonyRequest $request): Response
    {
        // AJAX requests use the same handler
        return $this->handleRequest($request);
    }
}
