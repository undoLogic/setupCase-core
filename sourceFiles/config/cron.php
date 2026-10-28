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
        // Files S3 Offload: push local files to S3 (docs/features/MVP-Files-S3-Offload.md)
        'files_push' => [
            'enabled' => true,
            'model' => 'Files',
            'action' => 'pushToS3',
            'description' => 'Push local files to S3.',
            'schedule' => 'Every time cron.php?action=run_all is called.',
            'max_age' => 600,
            'monitor' => true,
        ],
    ],
];
