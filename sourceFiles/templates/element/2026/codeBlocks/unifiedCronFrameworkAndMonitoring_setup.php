<?php
$snippets = [
    [
        'title' => '1. Project config: sourceFiles/config/cron.php',
        'note' => 'Each project registers its own jobs here. Hosting cadence stays outside the MVP framework.',
        'code' => <<<'PHP'
<?php

return [
    'status_path' => env('CRON_STATUS_PATH') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'setupcase-cron',
    'security' => [
        'allowed_ips' => [
            '127.0.0.1',
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
PHP,
    ],
    [
        'title' => '2. Table method: sourceFiles/src/Model/Table/EmailQueuesTable.php',
        'note' => 'Put the business logic in the Table method. Admin and cron should both call this method.',
        'code' => <<<'PHP'
public function processCronQueue(): array
{
    /*
     * Put project-specific queue processing here.
     * Keep the public method short and move detail into private helpers.
     */

    return [
        'STATUS' => 200,
        'MSG' => 'Processed email queue',
    ];
}
PHP,
    ],
    [
        'title' => '3. Admin manual run action',
        'note' => 'Use an admin-prefix action or button to prove the Table method works before adding it to cron.',
        'code' => <<<'PHP'
public function processCronQueue()
{
    $response = $this->EmailQueues->processCronQueue();

    if (($response['STATUS'] ?? 500) === 200) {
        $this->Flash->success($response['MSG'] ?? 'Cron job completed.');
    } else {
        $this->Flash->error($response['MSG'] ?? 'Cron job failed.');
    }

    return $this->redirect($this->referer());
}
PHP,
    ],
    [
        'title' => '4. Admin button',
        'note' => 'Point the button at the admin action. The admin action calls the same Table method that cron will call.',
        'code' => <<<'PHP'
<?php echo $this->Form->postLink(
    'Run Email Queue',
    [
        'prefix' => 'Admin',
        'controller' => 'EmailQueues',
        'action' => 'processCronQueue',
    ],
    [
        'class' => 'btn btn-primary',
        'confirm' => 'Run this cron job now?',
    ]
); ?>
PHP,
    ],
    [
        'title' => '5. Hosting cron URL',
        'note' => 'Hosting calls one permanent physical endpoint. IP restriction protects execution for the MVP.',
        'code' => <<<'TEXT'
https://example.com/cron.php?action=run_all
TEXT,
    ],
    [
        'title' => '6. Manual verification URLs',
        'note' => 'Use these during setup from an allowed IP.',
        'code' => <<<'TEXT'
https://example.com/cron.php?action=status
https://example.com/cron.php?action=run&job=email_queue
https://example.com/cron.php?action=run_all
TEXT,
    ],
];
?>

<style>
    .cron-guide pre {
        max-height: 32rem;
        overflow: auto;
    }
</style>

<div class="cron-guide">
    <div class="alert alert-info">
        <strong>MVP rule:</strong>
        cron jobs point to public Table methods. Build and test the method through an admin-prefix action,
        then register the same <code>model</code> and <code>action</code> in <code>config/cron.php</code>.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <h2 class="h5">Cron target</h2>
                <p class="mb-0">One physical <code>webroot/cron.php</code> endpoint per project.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <h2 class="h5">Business logic</h2>
                <p class="mb-0">Public Table methods return <code>STATUS</code> and <code>MSG</code>.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <h2 class="h5">Security</h2>
                <p class="mb-0">Execution is IP-restricted for MVP. No token is required.</p>
            </div>
        </div>
    </div>

    <h2 class="h4">Core Files</h2>
    <table class="table table-sm mb-4">
        <thead>
            <tr>
                <th>File</th>
                <th>Purpose</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>sourceFiles/webroot/cron.php</code></td>
                <td>Permanent physical hosting endpoint.</td>
            </tr>
            <tr>
                <td><code>sourceFiles/src/Service/CronService.php</code></td>
                <td>Loads config, authorizes IPs, runs model actions, and writes heartbeats.</td>
            </tr>
            <tr>
                <td><code>sourceFiles/config/cron.php</code></td>
                <td>Project-specific job registry.</td>
            </tr>
        </tbody>
    </table>

    <h2 class="h4">Setup Flow</h2>
    <ol class="mb-4">
        <li>Create or update the project Table method.</li>
        <li>Wire an admin-prefix action or button to that Table method.</li>
        <li>Run the admin action manually and confirm the result.</li>
        <li>Add the job entry to <code>sourceFiles/config/cron.php</code>.</li>
        <li>Confirm <code>cron.php?action=run&amp;job=email_queue</code> works from an allowed IP.</li>
        <li>Point hosting at <code>cron.php?action=run_all</code>.</li>
    </ol>

    <?php foreach ($snippets as $snippet): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5"><?php echo h($snippet['title']); ?></h2>
                <p><?php echo h($snippet['note']); ?></p>
                <pre class="mb-0"><code><?php echo h($snippet['code']); ?></code></pre>
            </div>
        </div>
    <?php endforeach; ?>
</div>
