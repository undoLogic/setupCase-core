# SetupCase Core
# Unified Cron Framework & Monitoring

**Status:** Planning

---

# Vision

Transform cron jobs from a hosting concern into a first-class SetupCase Core feature.

Instead of the hosting provider defining scheduled operations, the application itself becomes the **single source of truth**.

The hosting provider simply invokes the framework.

The framework decides:

- what jobs exist
- what jobs are enabled
- how to execute them
- how to monitor them
- when they last executed successfully

This creates deterministic infrastructure that is portable across every SetupCase project.

---

# Current Problems

Today:

- Cron jobs exist outside the application.
- Hosting configuration becomes the source of truth.
- Developers must remember every cron job manually.
- No centralized monitoring.
- Deployments can accidentally remove cron files.
- Difficult to determine expected scheduled operations.

---

# Goals

- One permanent cron endpoint.
- One configuration file.
- Self-documenting scheduled operations.
- Deployment-safe monitoring.
- Centralized monitoring support.
- Zero hosting changes when new jobs are added.
- Future-proof enterprise architecture.

---

# High Level Architecture

```
Hosting Provider
        │
        ▼
webroot/cron.php
        │
        ▼
CronService
        │
        ▼
config/cron.php
        │
        ▼
Registered Jobs
        │
        ▼
Heartbeat (.status files)
```

---

# Physical Entry Point

Every SetupCase installation includes one permanent file:

```
/webroot/cron.php
```

This exists because many shared hosting providers require a **physical PHP file** as the cron target.

The hosting provider always executes this file.

Example:

```
https://example.com/cron.php?action=run_all
```

This file becomes part of SetupCase Core.

It should rarely (if ever) require modification.

---

# Why Everything Uses cron.php

Even monitoring.

The monitoring server should verify the complete execution chain.

```
External Monitoring

        │

HTTP Request

        │

webroot/cron.php exists

        │

CakePHP bootstraps

        │

CronService loads

        │

config/cron.php loads

        │

Heartbeat files readable

        │

Status returned
```

If monitoring used another endpoint, the physical cron infrastructure could silently break without detection.

Using the same endpoint guarantees the deployed cron infrastructure itself is healthy.

---

# Endpoint Actions

```
?action=status
```

Return monitoring information.

---

```
?action=run_all
```

Execute every enabled scheduled operation.

---

```
?action=run&job=email_queue
```

Execute one scheduled operation.

---

No action:

```
/cron.php
```

Returns:

```
404
```

No body.

No implementation details.

---

Unknown actions:

Return:

```
404
```

No body.

---

# Configuration

Every SetupCase project edits one file:

```
config/cron.php
```

Example:

```php
return [

    /*
     * Global location for heartbeat files.
     *
     * If omitted, SetupCase automatically uses:
     *
     * sys_get_temp_dir()
     */

    'status_path' => '/home/projectName/private/cronjobs',

    /*
     * Execution security.
     */

    'security' => [

        'allowed_ips' => [

            '127.0.0.1',

        ],

        'execution_token' => env('CRON_EXECUTION_TOKEN'),

    ],

    /*
     * Registered scheduled operations.
     */

    'jobs' => [

        'email_queue' => [

            'enabled' => true,

            'method' => 'processEmailQueue',

            'description' => 'Process queued emails.',

            'schedule' => 'every minute',

            'timeout' => 300,

            'monitor' => true,

        ],

        'cleanup' => [

            'enabled' => true,

            'method' => 'cleanup',

            'description' => 'Cleanup temporary files.',

            'schedule' => 'hourly',

            'timeout' => 120,

            'monitor' => true,

        ],

    ],

];
```

---

# Source of Truth

The configuration file defines:

- available jobs
- descriptions
- execution methods
- timeout expectations
- monitoring
- intended schedule

Everything operational originates from this file.

No duplicated configuration.

---

# Heartbeat Files

Every successful job overwrites one heartbeat file.

Example:

```
/home/projectName/private/cronjobs/email_queue.status
```

Contents:

```
2026-07-19T11:42:08-04:00
```

Nothing else.

No append.

No parsing.

No rotation.

Simply overwrite.

---

# Why Status Files?

Heartbeat files survive deployments.

Unlike files inside the project directory, the private hosting directory remains untouched.

Example:

```
/home/projectName/private/

    cronjobs/

        email_queue.status

        cleanup.status

        reports.status
```

Deploying a new version does not reset monitoring history.

---

# Default Behaviour

If:

```
status_path
```

is omitted,

SetupCase automatically uses:

```
sys_get_temp_dir()
```

Typically:

```
/tmp
```

This allows every SetupCase project to function immediately with zero configuration.

---

# Generated Filenames

The cron key automatically becomes the filename.

```
email_queue
```

becomes

```
email_queue.status
```

No filename configuration required.

Deterministic.

Consistent.

Simple.

---

# CronService Responsibilities

CronService should:

- Load config/cron.php
- Execute one job
- Execute all jobs
- Generate monitoring status
- Write heartbeat files
- Return execution results
- Eventually expose execution history

---

# run_all

```
?action=run_all
```

Loops through every enabled registered job.

Adding a new scheduled operation never requires hosting changes.

Only:

- register the job
- implement the method

---

# Run One Job

```
?action=run&job=email_queue
```

Useful for:

- debugging
- manual execution
- testing
- future administration UI

---

# Status Endpoint

```
?action=status
```

Returns monitoring information.

Example:

```json
{
    "generated_at": "2026-07-19T11:42:08-04:00",

    "healthy": true,

    "total_jobs": 4,

    "enabled_jobs": 3,

    "healthy_jobs": 3,

    "failed_jobs": 0,

    "jobs": {

        "email_queue": {

            "enabled": true,

            "description": "Process queued emails.",

            "schedule": "every minute",

            "timeout": 300,

            "monitor": true,

            "last_success": "2026-07-19T11:41:00-04:00"

        }

    }

}
```

---

# System Health

The top-level status object exposes the overall health of the installation.

```
healthy
```

This allows monitoring software to determine whether the entire cron system is healthy without iterating through every job.

Internally:

```
healthy = (failed_jobs == 0)
```

This definition can evolve in the future to include:

- stale heartbeat detection
- timeout violations
- lock file detection
- execution failures

without changing the external API.

---

# Security

Execution requires:

- IP whitelist
- execution token

Status can remain public.

Reason:

External monitoring services need to verify deployment health without authentication.

Execution remains protected.

---

# Silent Failures

```
/cron.php
```

Returns:

```
404
```

No body.

Unknown actions:

```
404
```

No body.

Avoid exposing framework internals.

---

# Deployment Benefits

After initial installation:

Hosting never changes.

The hosting provider always executes:

```
cron.php?action=run_all
```

Developers only modify:

```
config/cron.php
```

Adding a new scheduled operation requires:

- one configuration entry
- one service method

Nothing else.

---

# Future Monitoring Server

The centralized monitoring server simply requests:

```
https://client.com/cron.php?action=status
```

This verifies:

- webroot/cron.php exists
- CakePHP boots
- CronService loads
- configuration loads
- heartbeat files exist
- overall health
- individual job health

No SSH.

No VPN.

No database access.

---

# Future Administration UI

```
Administration

Cron Jobs

---------------------------------

Email Queue

Run

Last Success

Healthy

---------------------------------

Cleanup

Run

Last Success

Healthy
```

Everything comes directly from:

- config/cron.php
- heartbeat files

---

# Future Enhancements

- execution history
- retry failed jobs
- stale heartbeat detection
- timeout monitoring
- execution duration
- lock files (prevent overlapping execution)
- notification rules
- centralized dashboard
- distributed monitoring
- scheduled health reports
- future internal scheduler
- email alerts
- Slack / Teams notifications

---

# Guiding Principle

**The hosting provider should not define application behaviour.**

The hosting provider has one responsibility:

> Execute `/webroot/cron.php`.

The application is responsible for:

- declaring scheduled operations
- executing them
- tracking them
- monitoring them
- reporting them

The project declares its operational intent through:

```
config/cron.php
```

The framework provides the execution engine through:

```
CronService
```

The infrastructure remains permanently stable through:

```
webroot/cron.php
```

This creates a deterministic, self-documenting, deployment-safe architecture that scales from shared hosting to enterprise environments while requiring almost no maintenance over the lifetime of a SetupCase project.