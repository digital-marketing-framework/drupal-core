<?php

namespace Drupal\dmf_core\Controller;

use DigitalMarketingFramework\Core\Backend\Request;
use DigitalMarketingFramework\Core\Backend\Response\JsonResponse;
use DigitalMarketingFramework\Core\Backend\Response\RedirectResponse;
use DigitalMarketingFramework\Core\Backend\Response\Response;
use Drupal\Core\Render\Markup;
use Drupal\dmf_core\Registry\RegistryCollection;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
     * Get Anyrel response for a request.
     *
     * @return Response
     */
    protected function getAnyrelResponse(SymfonyRequest $request): Response
    {
        $params = $request->query->all('dmf') ?? [];
        $route = $params['route'] ?? '';
        $arguments = $params['arguments'] ?? [];
        $body = $this->getBodyData($request);
        $method = $request->getMethod();

        $req = new Request($route, $arguments, $body, $method);
        return $this->registryCollection->getRegistry()->getBackendManager()->getResponse($req);
    }

    /**
     * Handle backend page requests.
     *
     * @return array<string,mixed>|SymfonyResponse
     *   Render array for HTML responses (integrates with Drupal admin theme),
     *   or Symfony Response object for redirects/JSON responses.
     */
    public function handleRequest(SymfonyRequest $request): array|SymfonyResponse
    {
        $result = $this->getAnyrelResponse($request);

        if ($result instanceof RedirectResponse) {
            return new SymfonyRedirectResponse($result->getRedirectLocation());
        } elseif ($result instanceof JsonResponse) {
            return new SymfonyJsonResponse($result->getData());
        }

        // For HTML responses, return a render array to integrate with Drupal's admin theme
        $html = $result->getContent();

        // Extract and attach JavaScript and CSS files
        $attachments = $this->extractAssets($html);

        return [
            '#markup' => Markup::create($html),
            '#attached' => $attachments,
        ];
    }

    /**
     * Extract JavaScript and CSS assets from HTML content.
     *
     * @param string $html
     *   The rendered HTML content.
     *
     * @return array<string,mixed>
     *   Drupal #attached array with 'js' and 'css' keys.
     */
    protected function extractAssets(string &$html): array
    {
        $attachments = [
            'html_head' => [],
        ];

        // Extract <link> tags for CSS
        if (preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $html, $cssMatches)) {
            foreach ($cssMatches[0] as $linkTag) {
                if (preg_match('/href=["\']([^"\']+)["\']/i', $linkTag, $hrefMatch)) {
                    $cssUrl = $hrefMatch[1];
                    $attachments['html_head'][] = [
                        [
                            '#tag' => 'link',
                            '#attributes' => [
                                'rel' => 'stylesheet',
                                'href' => $cssUrl,
                                'media' => 'all',
                            ],
                        ],
                        'dmf_backend_css_' . md5($cssUrl),
                    ];
                    // Remove the link tag from HTML
                    $html = str_replace($linkTag, '', $html);
                }
            }
        }

        // Extract <script> tags
        if (preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*><\/script>/i', $html, $scriptMatches, PREG_SET_ORDER)) {
            foreach ($scriptMatches as $match) {
                $scriptTag = $match[0];
                $scriptUrl = $match[1];

                $attributes = ['src' => $scriptUrl];

                // Check for type="module"
                if (preg_match('/type=["\']module["\']/i', $scriptTag)) {
                    $attributes['type'] = 'module';
                }

                // Check for defer
                if (preg_match('/defer(?:=["\']defer["\'])?/i', $scriptTag)) {
                    $attributes['defer'] = 'defer';
                }

                $attachments['html_head'][] = [
                    [
                        '#tag' => 'script',
                        '#attributes' => $attributes,
                    ],
                    'dmf_backend_js_' . md5($scriptUrl),
                ];

                // Remove the script tag from HTML
                $html = str_replace($scriptTag, '', $html);
            }
        }

        return $attachments;
    }

    /**
     * Handle backend AJAX requests.
     *
     * AJAX requests always return Symfony Response objects (JSON or Redirect),
     * never render arrays.
     */
    public function handleAjaxRequest(SymfonyRequest $request): SymfonyResponse
    {
        $result = $this->getAnyrelResponse($request);

        if ($result instanceof RedirectResponse) {
            return new SymfonyRedirectResponse($result->getRedirectLocation());
        } elseif ($result instanceof JsonResponse) {
            return new SymfonyJsonResponse($result->getData());
        }

        // AJAX requests should not return HTML, but if they do, wrap in Symfony Response
        return new SymfonyResponse($result->getContent());
    }
}
