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

use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds the logged-in user's prefix access (config 'rbac' for their user_type)
 * to the request as the 'access' attribute. AccessMiddleware enforces it.
 * An unknown user_type gets no access.
 */
class RbacMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $loggedUser = $request->getAttribute('identity');
        if ($loggedUser === null) {
            return $handler->handle($request);
        }

        $userType = (string)$loggedUser->get('user_type');
        $rbac = Configure::read('rbac');
        $access = is_array($rbac) && isset($rbac[$userType]) ? $rbac[$userType] : [];

        return $handler->handle($request->withAttribute('access', $access));
    }
}
