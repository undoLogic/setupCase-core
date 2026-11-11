<?php
declare(strict_types=1);

/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Prefix access gate (runs after Authentication, Lang and Rbac middleware).
 *
 * A prefixed request (Staff, Manager, Admin) is only passed on when the
 * logged-in user's role holds that prefix in config 'rbac'. Otherwise this
 * returns a redirect itself, so no controller code runs.
 * Unprefixed requests always pass.
 */
class AccessMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = $request->getAttribute('params') ?? [];
        $prefix = $params['prefix'] ?? null;

        // No prefix: no login needed. Logout is always allowed.
        if (empty($prefix) || ($params['action'] ?? null) === 'logout') {
            return $handler->handle($request->withAttribute('access_granted', true));
        }

        if ($request->getAttribute('identity') === null) {
            return $this->process_deny($request, 'Please log in to continue', $this->process_loginUrl($request, true));
        }

        $access = $request->getAttribute('access');
        if (empty($access) || !isset($access[$prefix])) {
            return $this->process_deny($request, 'Requires ' . $prefix . ' access', $this->process_homeUrl($request));
        }

        return $handler->handle($request->withAttribute('access_granted', true));
    }

    private function process_deny(ServerRequestInterface $request, string $msg, string $location): ResponseInterface
    {
        if ($request instanceof ServerRequest) {
            $request->getFlash()->error($msg);
        }

        return (new Response())
            ->withStatus(302)
            ->withHeader('Location', $location);
    }

    private function process_loginUrl(ServerRequestInterface $request, bool $withRedirect): string
    {
        $url = $this->process_homeUrl($request) . '/login';
        if ($withRedirect) {
            // Path without the base folder: UsersController::login() redirects through
            // the Router, which adds the base again
            $base = rtrim((string)$request->getAttribute('base'), '/');
            $path = $request->getUri()->getPath();
            if ($base !== '' && str_starts_with($path, $base . '/')) {
                $path = substr($path, strlen($base));
            }
            $url .= '?redirect=' . rawurlencode($path);
        }

        return $url;
    }

    private function process_homeUrl(ServerRequestInterface $request): string
    {
        $base = rtrim((string)$request->getAttribute('base'), '/');
        $lang = (string)($request->getAttribute('lang') ?: 'en');

        return $base . '/' . $lang;
    }
}
