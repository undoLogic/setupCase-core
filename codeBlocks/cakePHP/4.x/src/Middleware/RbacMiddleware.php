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
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;
use PhpParser\Node\Expr\Throw_;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rbac middleware
 */
class RbacMiddleware implements MiddlewareInterface
{
    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //simply get the current RBAC access if the user is logged in
        $loggedUser = $request->getAttribute('identity');
        if (!$loggedUser) {
            return $handler->handle($request);
        }
        //dd($loggedUser);

        //the user is logged in - add that access to the request
        if (!is_null($loggedUser)) {
            $user_type = $loggedUser->get('user_type');
            $rbac = Configure::read('rbac');
            $request = $request->withAttribute('access', $rbac[$user_type]);
            //pr ($rbac);
            //pr ($user_type);
            //dd($request);

        }

        return $handler->handle($request);
    }
}
