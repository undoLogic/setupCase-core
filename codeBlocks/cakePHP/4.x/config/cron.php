<?php
declare(strict_types=1);

return [
    'status_path' => '/absolute/path/to/status/dir/',
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
