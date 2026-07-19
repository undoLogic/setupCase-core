# Feature: Unified Cron Framework And Monitoring

## Status

MVP implemented. The first pass includes `config/cron.php`, `App\Service\CronService`, and `webroot/cron.php`.

## Summary

Move cron definitions out of hosting control panels and into SetupCase Core. Each installation keeps one permanent physical cron entry point at `sourceFiles/webroot/cron.php`; hosting only calls that file. The application loads `sourceFiles/config/cron.php`, executes registered jobs, writes heartbeat status files outside the deployed source tree, and exposes a minimal status response for monitoring.

## Goals

- Use one permanent hosting cron target for every SetupCase project.
- Make `sourceFiles/config/cron.php` the source of truth for scheduled operations.
- Add deterministic job registration, execution, and monitoring contracts.
- Keep hosting changes unnecessary when a project adds or removes jobs.
- Store heartbeat files somewhere deployment-safe.
- Let external monitoring verify that the physical cron file, CakePHP bootstrap, config loading, and heartbeat reads all work.
- Keep the first implementation simple enough to ship without a database migration.

## Non-Goals

- No visual administration UI in the first pass.
- No database-backed execution history in the first pass.
- No application-level interval scheduler in the first pass.
- No timeout enforcement in the first pass.
- No retry queue, notifications, Slack/Teams alerts, or distributed dashboard in the first pass.
- No attempt to edit hosting-provider cron settings from the application.

## Existing Context

- The app is CakePHP 4.5 and currently uses `sourceFiles/webroot/index.php` as the normal front controller.
- There is no existing cron framework in `sourceFiles/src`.
- `sourceFiles/webroot/modules` is treated as vendor/original assets and must not be edited for this feature.
- New source should live under `sourceFiles/`.
- Public `Table` methods must return response arrays with at least `STATUS` and `MSG`. Cron jobs should call those Table/model methods directly for the MVP.

## Architecture

```text
Hosting provider
    -> sourceFiles/webroot/cron.php
        -> CakePHP bootstrap
            -> App\Service\CronService
                -> sourceFiles/config/cron.php
                    -> registered model actions
                        -> heartbeat status files
```

`cron.php` is deliberately physical because many shared hosting providers need a real PHP file as the scheduled target. Monitoring must use the same file so a broken physical endpoint, bootstrap failure, or missing config is visible.

## Files To Add

- `sourceFiles/webroot/cron.php`
- `sourceFiles/config/cron.php`
- `sourceFiles/src/Service/CronService.php`

## Hosting Contract

Hosting calls one URL:

```text
https://example.com/cron.php?action=run_all
```

Adding a new scheduled operation requires code/config changes only:

1. Add a job entry to `sourceFiles/config/cron.php`.
2. Implement the public Table/model method.
3. Deploy.

No new hosting cron entry should be required.

## Endpoint Contract

All endpoint actions are passed through query parameters because the physical file is not a Cake route.

| Request | Access | Behavior |
| --- | --- | --- |
| `/cron.php?action=status` | Public by default | Return JSON monitoring status. |
| `/cron.php?action=run_all` | Protected | Execute every enabled job. |
| `/cron.php?action=run&job=<job_key>` | Protected | Execute one enabled job immediately. |
| `/cron.php` | Public | Return `404` with no body. |
| Unknown action | Public | Return `404` with no body. |
| Invalid IP for execution | Protected | Return `404` with no body. |
| Unknown job key | Protected | Return `404` with no body. |
| Disabled job requested directly | Protected | Return `404` with no body. |

The endpoint must not echo stack traces, config paths, or job method names.

## HTTP Response Rules

- `status` returns `200` when the framework can inspect configured jobs, even if one or more jobs are unhealthy.
- `status` returns JSON with `Content-Type: application/json`.
- `run_all` returns `200` JSON when the request is authorized and the framework completed the execution loop.
- `run` returns `200` JSON when the request is authorized and the requested job was executed.
- Unauthorized, unknown, malformed, or empty requests return `404` and an empty body.
- Job failures inside an authorized `run` or `run_all` response are reported in JSON, not by leaking a PHP fatal page.

## MVP Scheduling Scope

Hosting controls the cadence for the MVP.

Rules:

- `run_all` executes every enabled job every time hosting calls it.
- `run&job=<job_key>` is a manual override and executes immediately when authorized.
- Each job should be safe to run at the hosting cadence chosen for the project.
- Different job cadences can be added later through an application-level scheduler if needed.

`schedule` remains a human-readable label for monitoring and administration screens. Code should not parse `schedule`.

## Configuration Contract

Path:

```text
sourceFiles/config/cron.php
```

Example:

```php
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
            'action' => 'processEmailQueue',
            'description' => 'Process queued emails.',
            'schedule' => 'every minute',
            'max_age' => 120,
            'monitor' => true,
        ],
    ],
];
```

### Required Global Keys

- `jobs`

### Optional Global Keys

- `status_path`: directory for heartbeat files. Default: `sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'setupcase-cron'`.
- `security.allowed_ips`: allowed execution request IPs. Empty or omitted must disable execution.

### Job Keys

Job keys must be lowercase slug values containing only letters, numbers, and underscores.

Valid:

```text
email_queue
cleanup_tmp
daily_reports
```

Invalid:

```text
EmailQueue
email-queue
../email_queue
email.queue
```

The job key becomes the heartbeat filename:

```text
email_queue -> email_queue.status
```

### Required Job Keys

- `enabled`: boolean.
- `model`: CakePHP Table alias, for example `EmailQueues`.
- `action`: public method to call on the Table instance.
- `description`: short human-readable description.
- `schedule`: human-readable expected hosting cadence.
- `max_age`: seconds before the last successful heartbeat is considered stale.
- `monitor`: boolean.

### Optional Job Keys

None for the MVP.

## Model Action Contract

Each job should point to a public method on a CakePHP Table/model class. In `sourceFiles/config/cron.php`, `action` means the Table method name, not a controller action.

Example config target:

```php
'model' => 'EmailQueues',
'action' => 'processEmailQueue',
```

Example Table method:

Preferred successful response:

```php
public function processEmailQueue(): array
{
    return [
        'STATUS' => 200,
        'MSG' => 'Processed email queue',
    ];
}
```

Preferred failed response:

```php
public function processEmailQueue(): array
{
    return [
        'STATUS' => 500,
        'MSG' => 'Email queue failed',
    ];
}
```

Rules:

- `STATUS` in the `200` range means success and writes a heartbeat.
- Any missing, non-array, or non-`200` range response means failure and does not write a success heartbeat.
- Exceptions are caught by `CronService`, returned as failed job results, and must not expose sensitive exception text through the public endpoint when `debug` is false.

## Admin Testing Flow

The intended project workflow is:

1. Add a public method to the relevant Table/model.
2. Connect an admin-prefix controller action or button to that same Table method.
3. Manually run it through the admin UI until the behavior is confirmed.
4. Register the same model/action pair in `sourceFiles/config/cron.php`.
5. Let `cron.php?action=run_all` call the Table method directly.

Cron must not call the admin URL. The admin route and cron framework should share the same Table method so business logic lives in one place.

## Heartbeat Files

Each successful job overwrites one heartbeat file:

```text
<status_path>/<job_key>.status
```

Contents:

```text
2026-07-19T11:42:08-04:00
```

Rules:

- The file contains only an ISO-8601 timestamp.
- The file is overwritten on success.
- Failure does not overwrite the previous success heartbeat.
- No append, rotation, or log parsing in the first pass.
- `CronService` creates `status_path` when possible.
- If `status_path` is not writable, job execution reports failure and status reports the path problem without exposing private server paths unless debug mode allows it.

## Status Health Rules

`status` inspects only jobs with `monitor = true`.

A monitored job is healthy when:

- It is enabled.
- Its heartbeat file exists.
- The heartbeat timestamp is parseable.
- `generated_at - last_success <= max_age`.

A monitored job is unhealthy when:

- It is enabled but has no heartbeat.
- Its heartbeat timestamp is invalid.
- Its heartbeat is older than `max_age`.
- Its status file cannot be read.

Disabled jobs appear in the response but do not count as failed.

Top-level health:

```text
healthy = failed_jobs == 0
```

## Status JSON Contract

Example:

```json
{
  "generated_at": "2026-07-19T11:42:08-04:00",
  "healthy": true,
  "total_jobs": 2,
  "enabled_jobs": 2,
  "monitored_jobs": 2,
  "healthy_jobs": 2,
  "failed_jobs": 0,
  "jobs": {
    "email_queue": {
      "enabled": true,
      "description": "Process queued emails.",
      "schedule": "every minute",
      "max_age": 120,
      "monitor": true,
      "healthy": true,
      "last_success": "2026-07-19T11:41:00-04:00",
      "age_seconds": 68
    }
  }
}
```

Do not include full private paths or raw exception traces.

## Execution JSON Contract

`run` response example:

```json
{
  "generated_at": "2026-07-19T11:42:08-04:00",
  "STATUS": 200,
  "MSG": "Executed job",
  "job": "email_queue",
  "result": {
    "STATUS": 200,
    "MSG": "Processed email queue"
  },
  "heartbeat_written": true
}
```

`run_all` response example:

```json
{
  "generated_at": "2026-07-19T11:42:08-04:00",
  "STATUS": 200,
  "MSG": "Execution loop completed",
  "total_jobs": 2,
  "executed_jobs": 2,
  "successful_jobs": 1,
  "failed_jobs": 1,
  "jobs": {
    "email_queue": {
      "STATUS": 200,
      "MSG": "Processed email queue",
      "heartbeat_written": true
    },
    "cleanup_tmp": {
      "STATUS": 500,
      "MSG": "Cleanup failed",
      "heartbeat_written": false
    }
  }
}
```

`run_all` should keep executing remaining enabled jobs after one job fails.

## `CronService` Responsibilities

`App\Service\CronService` should own the business logic:

- Load and validate `sourceFiles/config/cron.php`.
- Normalize defaults.
- Validate job keys.
- Resolve configured `model` aliases through CakePHP's Table locator.
- Validate configured `action` methods are callable on the resolved Table.
- Authorize execution requests.
- Execute one enabled job.
- Execute all enabled jobs.
- Catch job exceptions.
- Write heartbeat files after successful job runs.
- Generate monitoring status.
- Return structured response arrays suitable for JSON output.

Keep `sourceFiles/webroot/cron.php` small. It should bootstrap CakePHP, instantiate `CronService`, dispatch based on `$_GET['action']`, emit JSON, and centralize silent `404` responses.

## Security

Execution is protected by:

- IP allowlist.

Security rules:

- Status is public by default because external monitoring needs unauthenticated deployment checks.
- Execution must fail closed when `security.allowed_ips` is missing or empty.
- Check the request IP against `security.allowed_ips`.
- Use the server-provided remote address by default.
- Do not trust forwarded IP headers unless proxy handling is explicitly configured later.
- Return the same `404` empty response for unauthorized and unknown execution requests.

## MVP Verification

Do not add automated tests for the MVP.

Manual verification should cover:

- `cron.php?action=status` returns JSON.
- `cron.php?action=run_all` executes only from an allowed IP.
- `cron.php?action=run&job=<job_key>` executes only from an allowed IP.
- Requests from a disallowed IP return `404` with no body.
- Missing, unknown, or disabled jobs return `404` with no body.
- Successful execution writes or updates the heartbeat file.
- Failed execution does not overwrite an existing heartbeat.
- Stale heartbeat files are reported as unhealthy.

## Implementation Phases

### Phase 1: Core Contract

- Add `sourceFiles/config/cron.php` with no enabled project jobs by default.
- Add `App\Service\CronService`.
- Add `sourceFiles/webroot/cron.php`.
- Verify PHP syntax with `php -l`.
- Manually verify the MVP endpoint behavior.

### Phase 2: First Real Job

- Register the first real project cron job.
- Implement the Table/model method.
- Wire an admin-prefix action to the same Table/model method for manual testing.
- Confirm `run` and `run_all` write heartbeats.
- Confirm `status` reports health from the heartbeat.

### Phase 3: Monitoring Integration

- Point external monitoring at `https://client.com/cron.php?action=status`.
- Define alert thresholds around top-level `healthy`.
- Keep execution endpoints IP protected.

### Phase 4: Administration UI

- Build a read-only cron jobs admin page from `config/cron.php` and heartbeat files.
- Add manual "Run" buttons only after access control rules are explicitly defined.

## Future Enhancements

- Lock files to prevent overlapping execution.
- Execution duration tracking.
- Database-backed execution history.
- Retry policy.
- Timeout enforcement.
- Notification rules.
- Centralized dashboard.
- Scheduled health reports.
- Application-level cadence scheduler.
- Timeout tracking and enforcement.
- Optional non-Table service targets if a future job does not fit a model/table boundary.

## Guiding Principle

The hosting provider should not define application behavior. Hosting has one responsibility: execute `sourceFiles/webroot/cron.php`. SetupCase owns job declaration, execution, heartbeat tracking, and health reporting through `sourceFiles/config/cron.php` and `App\Service\CronService`.
