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

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;

//@todo Change your domains for your project here
$liveDomains = [
    'test.devServer.com' => 'DEV',
    'pending.domain.com' => 'PENDING',
    'www.domain.com' => 'LIVE',
];

$current_domain = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
if (isset($liveDomains[$current_domain])) {
    $current_env_profile = $liveDomains[$current_domain];
} else {
    $current_env_profile = false;
}

//@todo
switch ($current_env_profile) {
    case 'DEV':
        Configure::load('app_DEV', 'default');
        break;
    case 'PENDING':
        Configure::load('app_pending', 'default');
        break;
    case 'LIVE':
        Configure::load('app_LIVE', 'default');
        break;
    default:
        // Unknown so we will use our dev env.
        Configure::load('app_DEV', 'default');
}

// Configure the environment setting.
Configure::write('App.current_env_profile', $current_env_profile);

// Every array URL (links, postLink/form actions, controller redirects) keeps the
// current language, so it matches a /{language}/ route. Without this LangMiddleware
// has to redirect, and a redirected POST arrives as a GET (e.g. delete fails with 405).
Router::addUrlFilter(function (array $params, ?ServerRequest $request): array {
    if ($request === null || isset($params['language']) || !empty($params['plugin'])) {
        return $params;
    }

    $lang = $request->getAttribute('lang') ?? $request->getParam('language');
    if ($lang) {
        $params['language'] = $lang;
    }

    return $params;
});
