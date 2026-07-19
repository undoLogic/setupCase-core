<?php
declare(strict_types=1);

return [
    'status_path' => env('CRON_STATUS_PATH') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'setupcase-cron',
    'security' => [
        'allowed_ips' => [
            '127.0.0.1',
            '::1',
        ],
    ],
    'jobs' => [
        'email_queue' => [
            'enabled' => true,
            'model' => 'EmailQueues',
            'action' => 'processCronQueue',
            'description' => 'Process queued emails.',
            'schedule' => 'Every time cron.php?action=run_all is called.',
            'max_age' => 300,
            'monitor' => true,
        ],
    ],
];
