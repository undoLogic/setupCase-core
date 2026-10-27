# SetupCase Core Block: Status Endpoint

## Purpose

Give every SetupCase app a small, token-protected endpoint that reports **how many errors were logged in the last 24 hours**. A separate central app (out of scope here) polls each site once a day and produces one report: "any errors on any site?"

- We only need to know **whether** there was a problem, not what it was. No log contents are ever exposed.
- This is the baseline monitoring for undoLogic managed plans.
- It is off (the endpoint returns 404) on any install where no token is configured. That is how Customer Controlled installs opt out.

This work has two deliverables:

1. The working endpoint in SetupCase core (the reference copy).
2. A **Status Endpoint** page in the Blocks menu that explains both sides: the site that serves `/en/status`, and how the central system gets a token and polls it daily. See `## CodeBlocks Page`.

## MVP Status

This is MVP work (AGENTS.md "MVP Work"):

- No automated tests are created or scaffolded.
- The `## Testing` section lists expected behaviour to be checked manually (curl); nothing is marked covered until it has actually been run.
- Lightweight checks only (PHP lint, endpoint responds, CodeBlocks page renders).

## Context

- Framework: CakePHP 4.6 (core is on 4.6.5). CakePHP 5 from November: avoid APIs removed in 5.
- `vendor/` is committed and Composer never runs on production. **No new Composer dependencies.**
- Apps run on managed hosting. Keep it fast and dependency-free: no database, no session, no cache.
- Logging in this repo (`config/app.php`):
  - `logs/error.log`: `warning`, `error`, `critical`, `alert`, `emergency`.
  - `logs/debug.log`: `notice`, `info`, `debug`.
  - Console commands write to `logs/cli-error.log` / `logs/cli-debug.log` instead (`config/bootstrap_cli.php`).
- Actual line format (checked in `logs/error.log` and `DefaultFormatter`): `2026-10-26 15:00:22 error: [Exception] message`. The level is **lowercase**. The date is written with PHP's default timezone, which is `App.defaultTimezone` (`UTC` unless overridden).
- Rotation (`FileLog` defaults): at 10 MB the file is renamed to `error.log.<unix time>`; at most 10 rotated files are kept.

## Decisions

These differ from (or add to) the original brief. Decisions 1-6 were built as proposed (2026-10-27).

1. **Ignore list in the controller, not `Error.skipLog` (decided 2026-10-27).** `Error.skipLog` is empty, so every missing page (bots probing `/wp-admin`, `DebugKit`, etc.) writes an `error:` line to `error.log`. Without a filter, `errors` would almost never be 0 on a live site, and anyone could inflate it (including by calling `/en/status` with a wrong token).
   - A `skipLog` change in `config/app.php` was built first and reverted: the block must stay self-contained (no host config edits), and the log should keep everything.
   - Instead, `StatusController::IGNORE` lists strings. A log entry whose first line contains one of them is counted as `ignored`, not as an error or warning. Plain, case-sensitive substring match.
   - Default list: only `[Cake\Http\Exception\NotFoundException]` (covers wrong tokens on this endpoint). Everything else starts counted; the developer adds known noise over time, preferring narrow entries (e.g. `Controller class DebugKit`) over whole exception classes.
   - The list lives in the controller to keep the block compartmentalized; once installed, a project may move it closer to its own config.
2. **Controller, not middleware (decided 2026-10-27).** A middleware version was built first and then replaced: it needed an edit to the host's `Application.php`, which works against "drop-in", and the MVP stays inside normal CakePHP conventions. `StatusController` extends Cake's base `Controller`, **not** `AppController`, so it skips the testing-site password gate (`SetupCase::requirePasswordExcept()`), the Authentication component, the menu and the database. It still runs through the app's middleware stack. Accepted consequences:
    - The URL has a language, `/en/status`, like every other page (`LangMiddleware` redirects `/status` there).
    - `LangMiddleware` writes the language to the session, so responses set a `PHPSESSID` and a `csrfToken` cookie. Harmless for curl/cron; a browser tab reuses its cookie.
    - A POST is rejected by CSRF with **403** (like every POST without a token on the site), not 404. That reveals nothing specific to this endpoint.
    - If a browser sends an expired session plus a remember-me cookie, the Cookie authenticator queries the database first; with the database down that request may fail. curl and cron send no cookies and are not affected.
3. **Count `cli-error.log` too.** Cron jobs that run as console commands log there, not in `error.log`. Failing cron jobs are exactly what this should catch. **Proposal:** count both, add them into the same totals.
4. **App name.** There is no `App.name` in config, and the folder fallback would be `sourceFiles` on every project. **Proposal:** `STATUS_APP_NAME` (ENV Vars pattern), falling back to the request host (e.g. `www.example.com`).
5. **Token config uses the ENV Vars pattern**, not `.env`: `get_cfg_var('STATUS_TOKEN') ?: env('STATUS_TOKEN') ?: ''`. Same as `DOCUMENTATION_REGENERATE_CODE` and `TRUSTED_PROXIES`.
6. **Overlap with the cron status.** `/cron.php?action=status` (Unified Cron Framework) already reports job heartbeats and is public. The two stay separate: `/en/status` answers "were errors logged", cron status answers "did the jobs run". The central poller may read both later; merging them is out of scope.
7. **Token in the URL is allowed (decided 2026-10-27).** `GET /en/status?token=<token>` works, so each site can be checked from a browser bookmark without building the central poller first. The `X-Status-Token` header also works, for automation later. Accepted trade-off: the token lands in access logs, browser history and bookmarks. The response only holds counts and versions, so a leaked token exposes little; rotate it if in doubt.
8. **Browser monitor page (decided 2026-10-27).** A browser opening `/en/status?token=...` gets a small HTML page instead of raw JSON. It re-checks every hour on its own and turns the tab's favicon red when errors are found, so the tab can stay open all day. Everything else (curl, cron, `?format=json`) still gets JSON. See `## Browser Monitor Page`.

## Endpoint

- **Path:** `GET /en/status` (relative to the app base, e.g. `https://example.com/en/status`; locally `http://localhost/sourceFiles/en/status`). Reached by the default fallback routes (`/{language}/{controller}` → `Status::index`); no route of its own. `/status` redirects there (302).
- **Auth:** shared secret, sent either way (decision 7):
    - Query string: `/en/status?token=<token>` (bookmarks, manual checks).
    - Header: `X-Status-Token: <token>` (automation). If both are sent, the header is used.
    - Compared with `hash_equals()`.
- **Token config:** `STATUS_TOKEN`, ENV Vars pattern (server `php.ini` value first, then Docker env). Each site gets its own token. Never committed.
- **Disabled state:** no token configured → 404, even if a token is sent. Off by default.
- **Wrong or missing token:** **404** with the same body as any other missing page (throw `NotFoundException` so the normal error page renders). A scanner must not be able to tell the endpoint exists. Never 401/403.
- **Method:** GET only. Other methods → 404 from the controller; in practice CSRF rejects a POST first with 403 (decision 2).
- **Bypasses** (by not extending `AppController`): the Authentication component, the testing-site password gate, the menu, and the database. Must respond while the database is down.

## Response format

- **HTML monitor page** when the request's `Accept` header includes `text/html` (a browser) and `?format=json` is not set.
- **JSON** otherwise: curl, cron jobs, `Accept: application/json`, or `?format=json`.
- Both send `Cache-Control: no-store`, `X-Robots-Tag: noindex` and `Referrer-Policy: no-referrer`. Both use the same token rules; a failed token check is always the plain 404.

## Response (200, JSON)

```json
{
  "app": "www.example.com",
  "generated_at": "2026-10-27T08:00:00+00:00",
  "window_hours": 24,
  "errors": 3,
  "warnings": 12,
  "ignored": 140,
  "php_version": "8.3.12",
  "cakephp_version": "4.6.5"
}
```

| Field | Value |
|---|---|
| `app` | `STATUS_APP_NAME`, else the request host |
| `generated_at` | now, ISO 8601 with offset |
| `window_hours` | `24` |
| `errors` | entries at level `error`, `critical`, `alert` or `emergency` in the window |
| `warnings` | entries at level `warning` in the window |
| `ignored` | error / warning entries in the window that matched the ignore list (decision 1) |
| `php_version` | `PHP_VERSION`, so a security advisory can be checked against every site at once |
| `cakephp_version` | `Configure::version()` |

- Headers: `Content-Type: application/json`, `Cache-Control: no-store`.
- New fields may be added later; existing fields never change meaning or type.

## Browser Monitor Page

Opened from a bookmark and left open in a tab. The tab's favicon and title show the state at a glance.

| State | When | Favicon | Tab title |
|---|---|---|---|
| OK | `errors` = 0 | green circle | `OK - <app>` |
| Errors | `errors` > 0 | red circle with `!` | `(3) ERRORS - <app>` |
| Unreachable | the check failed: timeout, network error, 5xx, or 404 (token changed / endpoint off) | grey circle with `?` | `UNREACHABLE - <app>` |

- Warnings alone keep the state OK; the warning count is shown on the page only.
- The page shows: app name, the state as a large coloured banner, `errors`, `warnings`, PHP and CakePHP versions, "Last checked" and "Next check" times, and a **Check now** button.
- **How it refreshes:** the page loads once, then JavaScript fetches the same URL as JSON (`?format=json`, token already in the URL) every 60 minutes and updates the page, favicon and title in place.
    - Not a full page reload (`<meta http-equiv="refresh">`): if the site is down, a reload leaves the browser's own error page in the tab and never tries again. Fetching keeps the page alive and shows **Unreachable** instead, then recovers on the next successful check.
    - When the tab becomes visible again (`visibilitychange`) and the last check is more than 60 minutes old, it checks immediately. Browsers slow down or unload background tabs, so the timer alone is not reliable.
- The favicon is an inline SVG data URI built by the JavaScript. No image files.
- No Bootstrap, no layout, no database: the page must render when the database is down. Plain HTML plus one CSS and one JS file.
- The token is never written into the page body or the JS; the script fetches its own `location.href` with `format=json` added.

## Counting rules

- Files: `error.log`, `cli-error.log`, and their rotated siblings (`error.log.<unix time>`, `cli-error.log.<unix time>`) in `LOGS`.
- Skip any rotated file whose modification time is older than the window.
- Count by the **timestamp on each entry**, not by file, so counts stay correct across rotation.
- Only lines that **start with a timestamp and a level** are counted: `^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) (warning|error|critical|alert|emergency): `. Stack traces and multi-line messages continue on following lines and are not counted.
- "Now minus 24 hours" is computed in `App.defaultTimezone`, the same timezone the lines are written in.
- **Performance:** never load a whole file into memory. Read backwards from the end in chunks and stop at the first counted line older than the window.
- Missing or empty files count as zero. An unreadable file is skipped (counted as zero), never a 500.

## Implementation

Everything lives in one controller, `App\Controller\StatusController` (decided 2026-10-27). A `LogStatsService` was built first and folded back in: the block is meant to be dropped into a project as a self-contained unit and only integrated more closely (e.g. counting moved to the model layer) afterwards. This is a deliberate exception to the "fat model, slim controller" rule in AGENTS.md; the public `index()` stays short and the counting lives in private helpers.

### `StatusController::index()`

1. Not GET, token not configured, no token sent (header or `?token=`), or `hash_equals()` fails: throw `NotFoundException` (normal 404 page).
2. Otherwise count the logs (`index_countLogs()`).
3. Browser request (decision 8): plain `View` class, no layout, template `templates/Status/index.php` holds the whole monitor page (kept as one file while the block is a drop-in; split into elements when integrated into a project). Kept under the 45-line pre-commit template limit. Otherwise return the JSON response.
4. The controller sets ready-to-use view vars (status data, state, title, check interval); the template does not compute them. CSS and JS are loaded from the template with `$this->Html->css()` / `script()`.
5. Private helpers: `index_tokenValid()`, `index_wantsHtml()`, `index_setMonitorVars()`.

### Counting helpers

- `index_countLogs()` sets `$this->counts` (`errors`, `warnings`, `ignored`); `index_logFiles()` lists current and rotated files; `index_countFile()` reads one file backwards in 64 KB chunks; `index_countLine()` parses one line.
- Tunables are class constants: `WINDOW_HOURS`, `LOG_FILES`, `LEVEL_FIELDS` (level → response field, so another level can be added without changing the response contract) and `IGNORE`.
- No group / user scoping: install-wide and read-only. Stated in the class comment.

## CodeBlocks Page

A **Status Endpoint** entry in the Blocks menu (`/en/code-blocks/status-endpoint`) is the full setup guide. It is written so a developer or an AI assistant on another project can build the feature from the page alone.

- Menu: after `Unified Cron Framework` in the Blocks group of `AppController::setupMenu()` and the `init-web/9-Install-CodeBlocks_appController.php` transform.
- `CodeBlocksController::statusEndpoint()`: title, subtitle, example URLs built in the controller (passive view), and `codeBlocks_renderFiles` showing the controller, service, template, monitor element, JS and CSS straight from the source.
- **Build it on a project** section: files to copy, "no host config / route / table / Composer package, do not extend `AppController`", how to tune the ignore list, and a short verify list.
- `templates/CodeBlocks/status_endpoint.php` renders only the element `2026/codeBlocks/status_endpoint`.
- Content, in two halves:
    - **On each site:** what `/en/status` returns, the 404 rules, how to set `STATUS_TOKEN` (and `STATUS_APP_NAME`), how to test it with curl.
    - **Daily check:** for now, one bookmark per site (`/en/status?token=...`) left open as a tab: it re-checks hourly and its favicon turns red on errors (favicon legend shown on the page). Later, a central cron job calls each site with the header. Both read the result the same way (200 + `errors > 0`, 404, timeout).
- The page shows no live counts and does not say whether a token is configured. It is public in core.

## Wiring

| Change | File | Kind |
|---|---|---|
| New controller | `src/Controller/StatusController.php` | sync |
| Monitor page template | `templates/Status/index.php` | sync |
| Monitor page CSS / JS | `webroot/css/status-endpoint.css`, `webroot/js/status-endpoint.js` | sync |
| CodeBlocks page + menu entry | `CodeBlocksController`, `AppController::setupMenu()`, templates | sync + `9-Install-CodeBlocks_appController.php` |
| README section | `README.md` | docs |

- No database table. The CSS and JS are loaded only by the monitor page, never by the global layout, and selectors are prefixed `.status-endpoint-*`.
- The transforms follow the existing pattern: marker check, known anchor, fail if the anchor is missing, no change on a second run.

## README

Add a short **Status endpoint** section:

- What it returns and why (daily monitoring baseline for managed plans).
- How to set `STATUS_TOKEN`, and that leaving it unset disables the endpoint.
- A bookmark example: `https://example.com/en/status?token=<token>`
- A curl example with the token in a header:
  `curl -H "X-Status-Token: $TOKEN" https://example.com/en/status`
- A link to the Status Endpoint CodeBlocks page for the full setup.

## Known limitations

- **Counts, not causes.** A non-zero count means "look at the logs on that site"; the endpoint never says what failed.
- **Only logged problems are seen.** A site that is down, or a PHP fatal that never reaches the Cake logger, shows up only as a failed poll (timeout / 5xx), not as a count.
- **Log retention bounds the window.** With 10 MB x 10 rotated files, a very noisy site could rotate away entries younger than 24 hours; the count is then a minimum.
- **Token in URLs.** With `?token=`, the token is stored in the site's access logs, browser history and bookmarks (decision 7). Anyone with access to those can read the counts and versions.
- **Red lasts up to 24 hours.** The window is 24 hours, so one error keeps the favicon red until it ages out, even after it is fixed.
- **Background tabs.** A browser may pause or unload an inactive tab. The page checks on return if it is overdue, but a red favicon can show up late while the tab is asleep.
- **Remember-me + database down.** A browser with an expired session and a remember-me cookie may get an error instead of the page while the database is down (decision 2).
- **Shared secret.** Anyone holding a site's token can read its counts and versions. Rotate by changing `STATUS_TOKEN` on the site and in the central system.

## Implementation Surfaces

- `sourceFiles/src/Controller/StatusController.php`
- `sourceFiles/templates/Status/index.php`
- `init-web/8-Save-CodeBlocks.php` (new synced files)
- `sourceFiles/src/Controller/AppController.php` (`setupMenu()`) and `init-web/9-Install-CodeBlocks_appController.php`
- `sourceFiles/src/Controller/CodeBlocksController.php` (`statusEndpoint()`)
- `sourceFiles/templates/CodeBlocks/status_endpoint.php`
- `sourceFiles/templates/element/2026/codeBlocks/status_endpoint.php`
- `sourceFiles/webroot/css/status-endpoint.css`
- `sourceFiles/webroot/js/status-endpoint.js`

## Build checklist

- [x] Decisions 1-6 built as proposed.
- [x] Add the `Status Endpoint` CodeBlocks page and Blocks menu entry.
- [x] Ignore list in `StatusController::IGNORE`, `ignored` count in the response (decision 1). The earlier `Error.skipLog` change and its transform were reverted.
- [x] Log counting (built as `LogStatsService`, then folded into `StatusController` as private helpers).
- [x] Create the browser monitor page (element, CSS, JS) and the HTML/JSON switch.
- [x] Create `StatusController` (replaced the first middleware version; `Application.php` and its transform are back to unchanged).
- [x] Add the new files to `8-Save-CodeBlocks.php` and to `codeBlocks_renderFiles` on the CodeBlocks page.
- [x] "Build it on a project" section on the CodeBlocks page.
- [ ] README section (no other block has one yet; the CodeBlocks page may be enough).
- [ ] Run the remaining manual scenarios in `## Testing` (browser-only ones).

## Out of scope (later)

- The central poller app, its cron schedule and the daily email report.
- Merging with `/cron.php?action=status`.
- Deprecation counts from `debug.log`.
- Any log message details in the response.
- Rate limiting beyond what the host provides.
- Desktop notifications or sounds from the monitor page (favicon and title only for now).
- One page showing several sites (one tab per site for now).

## Testing

Manual only (MVP). Automated tests intentionally skipped. Run with curl against local Docker.

**Verification notes (2026-10-27, local Docker):** a temporary `STATUS_TOKEN` was added to `dockerLinux/ini/php.ini` and removed afterwards. Counting was checked two ways: against the real `logs/error.log` (67 errors / 24 warnings, matching an independent `awk` count) and with fixture files run through the controller's private counting helpers (old entries, stack-trace lines, `notice`, a rotated file inside the window, a rotated file with an old mtime, `cli-error.log`, a missing directory). The database container was stopped for the "database down" scenarios and restarted. The ignore list was checked with a fixture (no list, default list, a narrow `DebugKit` entry, a whole exception class, an empty string) and live: three wrong-token requests showed as `ignored: 3`, matching an `awk` count of the same log. Endpoint scenarios were re-run after the switch from middleware to `StatusController`, and the counting checks again after the service was folded into the controller; results below are from that final version.

Not yet verified:
- Everything that needs a real browser and the JavaScript: favicon colours, hourly re-check, Check now, Unreachable state, visibility re-check. The server-rendered title and state were checked with curl.
- Wrong-token 404 body vs. another missing page: on a testing site, other missing pages hit the testing password gate (302) first, so they differ there. On a live site (no gate) both are the normal 404 page; not compared yet.
- Large file: checked with a 12 MB log (200,000 old lines), 0.0002 s and 2.7 MB peak memory; not with 100 MB+.

### Access

**Intent:** Only a caller with the right token can read the counts, and nobody else can tell the endpoint exists.

**Surfaces:**
- `GET /en/status`
- `StatusController::index()`

#### Scenarios

- [x] No token sent (no header, no `?token=`) returns 404.
- [ ] Wrong token returns 404 with the same body as a normal missing page. (404 checked; body comparison pending, see notes)
- [x] No `STATUS_TOKEN` configured returns 404 even when a token is sent.
- [x] Correct token as `?token=` returns 200 (bookmark works in a browser).
- [x] Correct token in the `X-Status-Token` header returns 200.
- [x] Wrong `?token=` returns 404.
- [x] POST with the correct token is refused (403 from CSRF, decision 2).
- [x] Correct token returns 200 with the JSON shape above and `Cache-Control: no-store`.
- [x] A wrong-token 404 is counted as `ignored`, not as an error (decision 1).
- [x] curl / cron requests need no cookies. (Responses do set `PHPSESSID` and `csrfToken`; decision 2)
- [x] The endpoint returns 200 while the database connection is unavailable.

### Counting

**Intent:** The counts reflect entries in the last 24 hours, across rotation, without counting continuation lines.

**Surfaces:**
- `StatusController::index_countLogs()` and its helpers
- `logs/error.log`, `logs/cli-error.log` and rotated siblings

#### Scenarios

- [x] Entries older than 24 hours are not counted.
- [x] Stack-trace and multi-line continuation lines are not counted.
- [x] `warning` lines go to `warnings`; `error`, `critical`, `alert`, `emergency` go to `errors`.
- [x] Entries in a rotated file inside the window are counted.
- [x] A rotated file modified before the window is not read.
- [x] Entries in `cli-error.log` are counted.
- [x] Missing or empty log files return zeros, not an error.
- [x] An entry whose first line matches the ignore list is counted as `ignored`, not as an error or warning.
- [x] A stack-trace line that contains an ignored string does not count as `ignored`.
- [x] A missing Table method (`BadMethodCallException`) is still counted as an error with the default list.
- [x] An empty string in the ignore list is skipped (does not ignore everything).
- [ ] A large log file (100 MB+) responds quickly without exhausting memory.

### Browser monitor page

**Intent:** A tab left open shows, from its favicon alone, whether the site has logged errors, and keeps checking every hour.

**Surfaces:**
- `/en/status?token=<token>` in a browser
- `templates/Status/index.php`
- `webroot/js/status-endpoint.js`

#### Scenarios

- [x] Opening the bookmark in a browser shows the monitor page, not raw JSON. (checked with a browser `Accept` header via curl)
- [x] curl without an `Accept: text/html` header, and `?format=json` in a browser, both return JSON.
- [ ] With 0 errors: green favicon, title `OK - <app>`.
- [ ] With errors: red favicon, title `(<n>) ERRORS - <app>`. (server-rendered title checked; favicon needs a browser)
- [ ] Warnings only: stays green; the warning count is shown on the page.
- [ ] The page re-checks after 60 minutes without a reload and updates the counts, favicon and "Last checked".
- [ ] **Check now** updates immediately.
- [ ] Site stopped (or token changed) while the tab is open: grey favicon, title `UNREACHABLE - <app>`; it recovers on the next successful check.
- [ ] Returning to a tab whose last check is over 60 minutes old triggers a check straight away.
- [x] The token does not appear in the page source.
- [x] The page renders while the database is down.

### CodeBlocks page

**Intent:** A developer can set up both the site and the central poller from the page alone.

**Surfaces:**
- `/en/code-blocks/status-endpoint`

#### Scenarios

- [x] The Blocks menu shows **Status Endpoint** and the page renders, including all five source files.
- [x] The page shows no live counts and does not reveal whether a token is configured.
