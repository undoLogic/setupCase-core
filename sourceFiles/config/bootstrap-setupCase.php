<?php
declare(strict_types=1);

use Cake\Core\Configure;

//@todo Change your domains for your project here
$liveDomains = [
    'test.devServer.com' => 'DEV',
    'pending.domain.com' => 'PENDING',
    'www.domain.com' => 'LIVE',
    'localhost' => 'DOCKER',
];

if (isset($_SERVER['HTTP_HOST'])) {

    $current_domain = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
    if (isset($liveDomains[$current_domain])) {
        $current_env_profile = $liveDomains[$current_domain];
    } else {
        $current_env_profile = false;
    }
} elseif ($_SERVER['DOCKER_LOCAL_TESTING']) {
    $current_env_profile = 'DOCKER';
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
    case 'DOCKER':
        Configure::load('app_DEV', 'default');
        break;
    default:
        // Unknown so we will use our dev env.
        Configure::load('app_DEV', 'default');
}

// Configure the environment setting.
Configure::write('App.current_env_profile', $current_env_profile);
