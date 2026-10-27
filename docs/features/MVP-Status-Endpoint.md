# Feature: Status Endpoint (SetupCase core)

## Goal

Give every SetupCase app a small, token-protected endpoint that reports **how many errors were logged in the last 24 hours**. A separate central app (out of scope here) polls each site every morning and produces a daily report: "any errors on any site?"

We only need to know **whether** there was a problem, not what it was. No log contents are ever exposed.

This is the baseline monitoring for undoLogic managed plans. It is turned off (endpoint returns 404) on any install where no token is configured, which is how Customer Controlled installs opt out.

## Context

- Framework: CakePHP 4.x (projects moving to 4.6 now, CakePHP 5 from November). Write code that works on 4.6 and avoid APIs removed in 5.
- Stock CakePHP logging: `logs/error.log` receives warning and above; `logs/debug.log` receives notice, info and debug.
- `vendor/` is committed to git and Composer never runs on production. **Do not add new Composer dependencies.**
- Apps run on managed hosting. Keep it fast and dependency-free: no database, no session, no cache required.

## Endpoint

- **Route:** `GET /status`
- **Auth:** shared secret sent in a header: `X-Status-Token: <token>`
    - Never accept the token in the query string (URLs end up in access logs).
    - Compare with `hash_equals()`.
- **Token config:** read from env var `STATUS_TOKEN`, loaded the same way other secrets are loaded in this app (`config/app_local.php` / `.env`). Each site gets its own token.
- **Disabled state:** if no token is configured, the endpoint returns 404, so it is off by default.
- **Wrong or missing token:** return a plain **404** with the same body as any other missing page. A scanner must not be able to tell the endpoint exists. Do not return 401/403.
- **Must bypass:** authentication/authorization middleware, CSRF, session start, and any login IP-blocking logic. It must still work when the app's database is down.
- **Method:** GET only; anything else returns 404.

## Response (200, JSON)

```json
{
  "app": "example-app",
  "generated_at": "2026-10-27T08:00:00-04:00",
  "window_hours": 24,
  "errors": 3,
  "warnings": 12,
  "php_version": "8.3.12",
  "cakephp_version": "4.6.1"
}
```

- `app`: the app name from config (`App.name` or similar); fall back to the folder name.
- `errors`: count of log entries at level **error, critical, alert or emergency** in the last 24 hours.
- `warnings`: count of entries at level **warning** in the last 24 hours.
- `php_version`: `PHP_VERSION`. Included so a security advisory can be checked against every site at once.
- `cakephp_version`: from `Configure::version()`.
- Send `Cache-Control: no-store`.

## Counting rules

- Count by **timestamp on each log entry**, not by file. This keeps counts correct across log rotation.
- Scan `error.log` **and its rotated siblings** (CakePHP's FileLog renames rotated files with a suffix). Skip any rotated file whose modification time is older than the window.
- Only count lines that **start with a timestamp**. Stack traces and multi-line messages continue on following lines and must not be counted.
- Default CakePHP 4 line format: `2026-10-27 08:12:33 Error: message...`. Parse the timestamp and level from that prefix and check the format against the app's actual log config.
- Use the same timezone the logs are written in when comparing against "now minus 24 hours".
- **Performance:** log files can be large. Don't load whole files into memory. Read in reverse from the end, or line by line, and stop once entries are older than the window.

## Implementation notes

- Put the logic in a small service class (e.g. `src/Service/LogStatsService.php`) that takes the log directory and a "now" timestamp, so it is easy to unit test.
- Keep the controller thin: check the token, call the service, return JSON.
- Structure it so other log files or levels (e.g. deprecations from `debug.log`) can be added later without changes to the response contract.

## Tests

- Missing token header returns 404
- Wrong token returns 404 (same body as a normal missing page)
- No token configured returns 404 even if a header is sent
- Correct token returns 200 with the JSON shape above
- Entries older than 24 hours are not counted
- Stack-trace lines are not counted
- Entries in a rotated file inside the window are counted
- Empty or missing log files return zeros, not an error
- Endpoint responds when the database connection is unavailable

## Docs

Add a short **Status endpoint** section to the README covering:

- What it returns and why (daily monitoring baseline for managed plans)
- How to set `STATUS_TOKEN`, and that leaving it unset disables the endpoint
- A curl example sending the token in a header:
  `curl -H "X-Status-Token: $TOKEN" https://example.com/status`

## Out of scope (later)

- The central poller app, the cron schedule and the daily email report
- Deprecation counts from `debug.log`
- Any log message details in the response
- Rate limiting beyond what the host provides