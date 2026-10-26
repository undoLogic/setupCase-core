<?php
declare(strict_types=1);

use Cake\Core\Configure;

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
