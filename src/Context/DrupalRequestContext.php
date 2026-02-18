<?php

namespace Drupal\dmf_core\Context;

use DigitalMarketingFramework\Core\Context\RequestContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DrupalRequestContext extends RequestContext
{
    public function __construct(
        protected RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    public function getCookies(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        return $request->cookies->all();
    }

    public function getIpAddress(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        // Symfony's getClientIp() already handles X-Forwarded-For and other proxy headers.
        return $request->getClientIp();
    }

    public function getHost(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        return $request->getHost();
    }

    public function getUri(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        return $request->getRequestUri();
    }

    public function getReferer(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        return $request->headers->get('Referer');
    }

    public function getRequestVariables(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        return $request->server->all();
    }

    public function getRequestVariable(string $name): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return '';
        }

        // Map common request variables to Symfony Request methods for consistency.
        return match ($name) {
            'REMOTE_ADDR' => $request->getClientIp() ?? '',
            'HTTP_USER_AGENT' => $request->headers->get('User-Agent', ''),
            'HTTP_REFERER' => $request->headers->get('Referer', ''),
            'REQUEST_URI' => $request->getRequestUri(),
            'HTTP_HOST' => $request->getHost(),
            'REQUEST_METHOD' => $request->getMethod(),
            default => $request->server->get($name, ''),
        };
    }

    public function getRequestArgument(string $name): mixed
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        return $request->query->get($name);
    }

    public function getRequestArguments(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [];
        }

        return $request->query->all();
    }
}
