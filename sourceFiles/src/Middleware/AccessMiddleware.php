<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Access middleware
 */
class AccessMiddleware implements MiddlewareInterface
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
        //starting out we give access
        $request = $request->withAttribute('access_granted', true);

        //redirect
        //pass auth middle - inject attribute authorization - result authorizd / forbidden

        //No restrictions, theres actions can ALWAYS access any prefix
        if (in_array($request->getAttributes()['params']['action'], ['logout'])) {
            return $handler->handle($request);
        };

        //depending on the current RBAC - limit the users access to different parts of the software
        $params = $request->getAttribute('params');

        if (isset($params['prefix'])) {
            $prefix = $params['prefix'];
            $loggedUser = $request->getAttribute('identity');
            //dd($loggedUser);
            if (is_null($loggedUser)) {
                //user is NOT logged in
                //throw new ForbiddenException('User is NOT logged in and is trying to access: '.$prefix);
                //php 8.1 enum ? create result ok result failre
                //https://stitcher.io/blog/php-enums

                $request = $request->withAttribute('access_granted', false);
                $request = $request->withAttribute('access_msg', 'User must be logged');
            } else {
                $loggedUserAccess = $request->getAttribute('access');
                if (empty($loggedUserAccess)) {
                    $request = $request->withAttribute('access_granted', false);
                    $request = $request->withAttribute('access_msg', 'No access has been granted');
                    //throw new ForbiddenException('User has not been granted any access');
                } else {
                    if (isset($loggedUserAccess[ $prefix ])) {
                        //Prefix is allowed
                        $request = $request->withAttribute('access_granted', true);
                    } else {
                        //not allowed for this prefix
                        $request = $request->withAttribute('access_granted', false);
                        $request = $request->withAttribute('access_msg', 'Requires '.$prefix.' access');
                    }
                }
            }
        } else {
            //no prefix, so not need to force a login
        }

        return $handler->handle($request);
    }
}
