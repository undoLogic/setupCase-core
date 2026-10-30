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

namespace App\Authenticator;

use Authentication\Authenticator\FormAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Identifier\IdentifierInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Login Throttling - FormAuthenticator that refuses a login POST while its
 * IP + username has too many recent failures. See docs/features/MVP-login_throttling.md
 *
 * The check runs here, before the password is verified. By the time a
 * controller runs, a correct password has already logged the user in.
 *
 * Proxies are only trusted when TRUSTED_PROXIES (comma-separated IPs) is set.
 */
class ThrottledFormAuthenticator extends FormAuthenticator
{
    use LocatorAwareTrait;

    public const FAILURE_THROTTLED = 'FAILURE_THROTTLED';

    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        // Not the login URL or no credentials posted: no password is checked, nothing to count
        if (!$this->_checkUrl($request) || $this->_getData($request) === null) {
            return parent::authenticate($request);
        }

        $ip = $this->clientIp($request);
        $username = (string)$this->_getData($request)[IdentifierInterface::CREDENTIAL_USERNAME];
        $attempts = $this->fetchTable('LoginAttempts');

        if ($attempts->isBlocked($ip, $username)['blocked'] ?? false) {
            return new Result(null, self::FAILURE_THROTTLED, ['Too many failed login attempts']);
        }

        $result = parent::authenticate($request);
        if ($result->isValid()) {
            $attempts->clearFailures($ip, $username);
        } else {
            $attempts->recordFailure($ip, $username);
        }

        return $result;
    }

    // Not ServerRequest::setTrustedProxies(): CakePHP then trusts X-Forwarded-For
    // without checking that the request really came from a listed proxy
    private function clientIp(ServerRequestInterface $request): string
    {
        $remote = trim((string)($request->getServerParams()['REMOTE_ADDR'] ?? ''));
        $proxies = (string)(get_cfg_var('TRUSTED_PROXIES') ?: env('TRUSTED_PROXIES') ?: '');
        $proxies = array_values(array_filter(array_map('trim', explode(',', $proxies))));
        if (!in_array($remote, $proxies, true)) {
            return $remote;
        }

        // Right to left: the first address that is not one of our proxies is the client
        $forwarded = array_map('trim', explode(',', $request->getHeaderLine('X-Forwarded-For')));
        foreach (array_reverse($forwarded) as $ip) {
            if ($ip !== '' && !in_array($ip, $proxies, true)) {
                return $ip;
            }
        }

        return $remote;
    }
}
