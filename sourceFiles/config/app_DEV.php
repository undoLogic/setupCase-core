<?php
/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */

use Cake\Log\Engine\FileLog;

return [


    //if browser language does not match will be redirected to default lang
    'allowedLanguages' => [
        'en',
        //'fr',
        //'es'
    ],

    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    //'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'debug' => true,

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', 'f8111c0276d3d40d666502b5ab91251a907383e709ecd854a8996a7b8ea05d9f'),
    ],

    //trying to ignore debug on LIVE
    'Log' => [
        'info' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'info',
            'url' => env('LOG_DEBUG_URL', null),
            'scopes' => false,
            'levels' => ['notice', 'info'],
        ],
//        'debug' => [
//            'className' => FileLog::class,
//            'path' => LOGS,
//            'file' => 'debug',
//            'url' => env('LOG_DEBUG_URL', null),
//            'scopes' => false,
//            'levels' => ['notice', 'debug'],
//        ],
        'error' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'error',
            'url' => env('LOG_ERROR_URL', null),
            'scopes' => false,
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
        // To enable this dedicated query log, you need set your datasource's log flag to true
        'queries' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'queries',
            'url' => env('LOG_QUERIES_URL', null),
            'scopes' => ['queriesLog'],
        ],
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        //@todo uncomment this if you want to use a database
        'default' => [
            'url' => env('DATABASE_DEFAULT_URL', get_cfg_var('DATABASE.DEFAULT.URL') ?: null),
        ],
        'test' => [
            'url' => env('DATABASE_TEST_URL', get_cfg_var('DATABASE.TEST.URL') ?: null),
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'host' => 'mail.hoster906.com',
            'port' => 587,
            'className' => 'Smtp',
            'username' => 'sendtest@undoweb.com',
            'password' => get_cfg_var('UNDOWEB.EmailTransport.default.password'),
            'client' => null,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],
];
