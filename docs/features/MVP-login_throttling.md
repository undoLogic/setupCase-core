# SetupCase Core Block: Login Throttling

## Purpose

SetupCase Core slows down password guessing by temporarily blocking repeated failed logins.

- **What is counted:** failed attempts per IP address plus the submitted username (lowercased and trimmed). The username counts whether or not the account exists, so attackers cannot use the block to find out which accounts are real.
- **Limit:** after a set number of failures (default 5) within a time window, further attempts are refused for a cooldown period (default 5 minutes). Both values are configurable.
- **Storage:** attempts are stored server-side (database), never in the session, so clearing cookies does not reset the count.
- **Real IP:** if the app sits behind a proxy or CDN, configure trusted proxies. Otherwise forwarded IP headers can be faked or every request may appear to come from the proxy.
- **Shared networks:** offices behind a single public IP share that IP. Because blocking also depends on the username, one person's mistakes do not lock out colleagues.
- **Scope:** this only defends against brute-force password guessing. It does not protect against vulnerabilities in PHP, frameworks or libraries.

## MVP Status

This is MVP work (AGENTS.md "MVP Work"):

- No automated tests are created or scaffolded.
- The `## Testing` section lists expected behaviour to be checked manually; nothing is marked covered until it has actually been run.
- Lightweight checks only (PHP lint, login page renders, manual login attempts).

## Current State (before this feature)

- Login uses the CakePHP Authentication plugin: `Authentication.Session`, `Authentication.Form` and `Authentication.Cookie` (remember me), loaded in `Application::getAuthenticationService()`. That file is framework-owned and wired by `init-web/9-Install-CodeBlocks_application.php`.
- `UsersController::login()` only reads the result. On failure it flashes `'Login error: ' . json_encode($errors)`, which shows internal result data to the user.
- An earlier, unfinished attempt exists and is **not wired in anywhere**:
  - `src/Middleware/FormLoginAttemptsAuthenticator.php` (namespace `App\Middleware`, contains `dd()` / `die()` debug calls, calls `tooManyFailures()` without the IP).
  - `src/Model/Table/FormAttemptsTable.php` (IP only, no username, hard-coded 5 / 5 minutes, no schema file for `form_attempts`).
  - Both are synced in CodeBlocks, so every new project receives this dead code.
  - `init-web/9-Install-CodeBlocks_middleware.php` mentions `use App\Authenticator\FormLoginAttemptsAuthenticator;` in its notes only.

**Decision to verify:** replace the unfinished attempt instead of repairing it. Remove both files from `sourceFiles/` and `codeBlocks/cakePHP/4.x/` (neither is listed in `8-Save-CodeBlocks.php`), and build the feature under the names below.

## Design

### Where the check happens: a custom Form authenticator

The throttle runs **inside the authenticator**, before the password is checked.

- `App\Authenticator\ThrottledFormAuthenticator` extends `Authentication\Authenticator\FormAuthenticator` (`src/Authenticator/` is the Authentication plugin's convention).
- It replaces `Authentication.Form` in `Application::getAuthenticationService()`, with the same `fields` and `loginUrl`.
- Why not in `UsersController::login()`: by the time the controller runs, the middleware has already checked the password and, on success, stored the identity in the session. Blocking there would mean undoing a login that already happened.

Flow for a login POST:

1. Not the login URL, or no credentials posted: behave exactly like `FormAuthenticator` (no counting).
2. Build the throttle key: client IP + normalized username (see Counting Key).
3. If the key is blocked: return a failure result with status `FAILURE_THROTTLED` **without checking the password**. No new attempt is recorded while blocked.
4. Otherwise run the normal `FormAuthenticator` check.
5. On failure: record one failed attempt for the key.
6. On success: clear the failed attempts for that key.

### Counting key

- **IP:** `$request->clientIp()`.
- **Username:** the submitted `email` field, `trim()` + `mb_strtolower()`.
- Stored as `username_hash` = SHA-256 of the normalized username, not the raw text. People sometimes type their password into the username field; it must never be written to the database.
- An empty username still counts (hash of the empty string), so blank submissions are throttled too.

### Limit and cooldown (rolling window)

- `maxFailures` (default `5`) and `windowMinutes` (default `5`).
- A key is blocked while it has `maxFailures` or more failed attempts inside the last `windowMinutes`.
- The block lifts on its own as older failures age out of the window. With the defaults, an attacker is blocked for at most 5 minutes after the 5th failure, which is the "cooldown" from the Purpose section.
- Configured as properties on the Table class (same "config in the model-layer class" shape as the Documentation block). No settings UI.

**Decision to verify:** one setting (`windowMinutes`) serves as both the counting window and the cooldown. A separate cooldown length can be added later if needed.

### Messages

- **Blocked:** `Too many failed login attempts. Please try again in a few minutes.`
- **Wrong email or password:** `Email or password is incorrect.` This replaces the current `json_encode($errors)` flash.
- Both messages are identical whether or not the account exists.
- The page is re-rendered as usual (HTTP 200). No exact unblock time is shown.

### Real IP behind a proxy / CDN

- Default: proxies are **not** trusted; `clientIp()` uses `REMOTE_ADDR`, which cannot be faked.
- To trust a proxy, set `TRUSTED_PROXIES` (comma-separated IPs) using the ENV Vars pattern: server value first, then Docker env: `get_cfg_var('TRUSTED_PROXIES') ?: env('TRUSTED_PROXIES') ?: ''`.
- When set, the authenticator calls `$request->setTrustedProxies([...])` before reading `clientIp()`. Only then are `X-Forwarded-For` headers from those proxies used.
- Never trust forwarded headers from everyone (`trustProxy = true` without a list).

### Data scoping (AGENTS.md exception)

- Login attempts are recorded **before** anyone is logged in, so there is no `group_id` / `user_id` to scope by. This is a deliberate exception and must be stated in a comment in the Table class.
- Deleting rows:
  - **Purge:** rows older than `windowMinutes` are deleted when a new failure is recorded. The precondition is the age of the row; nothing inside the window is ever purged.
  - **Clear on success:** deletes only rows matching the exact `ip` + `username_hash` of the successful login, in the same query.
  - No method deletes all rows.

### Out of scope (this version)

- Remember-me cookie logins (`Authentication.Cookie`) and the password reset form (`UsersController::beginReset()` / `reset()`) are not throttled.
- No admin screen to view or clear blocks.

## Database

New table, recorded in `sourceFiles/config/schema/2026-10-26.sql` (and its CodeBlocks copy):

```sql
# Login Throttling
# See docs/features/MVP-login_throttling.md for the full spec.

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL COMMENT 'IPv4 or IPv6',
  `username_hash` char(64) NOT NULL COMMENT 'SHA-256 of trimmed, lowercased username; raw username is never stored',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_username_created` (`ip`, `username_hash`, `created`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

- Only failed attempts are stored.
- The schema has no automatic init-web step yet; the SQL is run by hand on each environment.

## Model: `LoginAttemptsTable`

Public methods return at least `['STATUS' => ..., 'MSG' => ...]`; helpers are private and named `<publicFunction>_<helper>()`.

| Method | Purpose | Extra response data |
|---|---|---|
| `isBlocked($ip, $username)` | Count failures for the key inside the window | `blocked` (bool) |
| `recordFailure($ip, $username)` | Insert one failure, then purge rows older than the window | none |
| `clearFailures($ip, $username)` | Delete the rows for the key after a successful login | none |

- All three refuse (`STATUS` 400) when `$ip` is empty.
- The username is normalized and hashed inside the Table (one place), never in the authenticator.

## Wiring

| Change | File | Kind |
|---|---|---|
| New authenticator | `src/Authenticator/ThrottledFormAuthenticator.php` | sync |
| New Table | `src/Model/Table/LoginAttemptsTable.php` | sync |
| Schema | `config/schema/2026-10-26.sql` | schema |
| Replace `Authentication.Form` with `ThrottledFormAuthenticator` | `src/Application.php` | transform (`init-web/9-Install-CodeBlocks_application.php`) |
| Generic and throttled flash messages | `src/Controller/UsersController.php` | sync |
| Remove the old attempt | `src/Middleware/FormLoginAttemptsAuthenticator.php`, `src/Model/Table/FormAttemptsTable.php` | delete in `sourceFiles/` and CodeBlocks |
| Fix the stale note | `init-web/9-Install-CodeBlocks_middleware.php` (comment only) | transform |

- The transform follows the existing pattern: marker check, known anchor, fail if the anchor is missing. On projects already built, re-running it must swap `Authentication.Form` once and do nothing on later runs.
- No CSS or JS. No template changes: the login template already shows flash messages.

## Known limitations

- **One IP, many usernames:** an attacker trying one password across many usernames from one IP is only limited per username. An extra IP-wide cap can be added later.
- **Many IPs, one username:** a distributed attack (botnet) against one account is only limited per IP.
- **Shared IP + same username:** two people behind one office IP typing the same username share one counter.
- **Database growth:** bounded by the purge on each new failure. A quiet site keeps its last few rows until the next failure.
- **Not a security product:** see the README disclaimer.

## Implementation Surfaces

- `sourceFiles/src/Authenticator/ThrottledFormAuthenticator.php`
- `sourceFiles/src/Model/Table/LoginAttemptsTable.php`
- `sourceFiles/src/Application.php` (`getAuthenticationService()`)
- `sourceFiles/src/Controller/UsersController.php` (`login()`)
- `sourceFiles/config/schema/2026-10-26.sql`
- `init-web/9-Install-CodeBlocks_application.php`
- `init-web/8-Save-CodeBlocks.php` (new synced files, removed old ones)

## Build checklist

- [ ] Remove the unfinished `FormLoginAttemptsAuthenticator` / `FormAttemptsTable` (sourceFiles + CodeBlocks).
- [ ] Add `login_attempts` schema in `config/schema/2026-10-26.sql` and run it locally.
- [ ] Create `LoginAttemptsTable` with `isBlocked`, `recordFailure`, `clearFailures`, the config properties, and the no-group-scope comment.
- [ ] Create `ThrottledFormAuthenticator` (check before password, record on failure, clear on success, `TRUSTED_PROXIES`).
- [ ] Swap `Authentication.Form` in `Application.php` and in the `9-Install-CodeBlocks_application.php` transform.
- [ ] Replace the `json_encode($errors)` flash in `UsersController::login()` with the two fixed messages.
- [ ] Run the manual scenarios in `## Testing`.

## Testing

Manual only (MVP). Automated tests intentionally skipped.

### Blocking

**Intent:** Repeated failed logins for the same IP + username are refused for a while, without the password being checked.

**Surfaces:**
- `POST /en/login`
- `ThrottledFormAuthenticator`
- `LoginAttemptsTable::isBlocked()`, `recordFailure()`

#### Scenarios

- [ ] 4 wrong passwords, then the correct password: login succeeds.
- [ ] 5 wrong passwords: the next attempt shows the "Too many failed login attempts" message.
- [ ] While blocked, the correct password is also refused.
- [ ] While blocked, no new rows are added to `login_attempts`.
- [ ] After the window passes (5 minutes by default), the correct password works again.
- [ ] Username case and surrounding spaces do not matter: `User@Example.com ` and `user@example.com` share one counter.

### Account Privacy

**Intent:** The block and the messages never reveal whether an account exists.

**Surfaces:**
- `UsersController::login()`
- `login_attempts` table

#### Scenarios

- [ ] A non-existent username is blocked after 5 failures, exactly like a real one.
- [ ] Wrong password and unknown username show the same message.
- [ ] The flash no longer shows `json_encode` result data.
- [ ] `login_attempts` contains no raw usernames or passwords, only `username_hash`.

### Shared Networks

**Intent:** One person's mistakes do not lock out colleagues on the same IP.

**Surfaces:**
- `LoginAttemptsTable::isBlocked()`

#### Scenarios

- [ ] While user A is blocked, user B from the same IP can log in.
- [ ] A successful login clears only that IP + username's failures.

### Storage

**Intent:** The count lives on the server, survives cookie clearing, and stays small.

**Surfaces:**
- `login_attempts` table
- `LoginAttemptsTable::recordFailure()`

#### Scenarios

- [ ] Clearing cookies / using a private window does not reset a block.
- [ ] Rows older than the window are deleted when the next failure is recorded.
- [ ] No method deletes rows that are still inside the window, except clear-on-success for the exact key.

### Real IP

**Intent:** Forwarded headers are only trusted from configured proxies.

**Surfaces:**
- `TRUSTED_PROXIES`
- `ThrottledFormAuthenticator`

#### Scenarios

- [ ] With `TRUSTED_PROXIES` unset, a fake `X-Forwarded-For` header does not change the recorded IP.
- [ ] With `TRUSTED_PROXIES` set to the proxy IP, the client IP from `X-Forwarded-For` is recorded.

### Unaffected Logins

**Intent:** Logins that do not use the password form keep working as before.

**Surfaces:**
- `Authentication.Session`, `Authentication.Cookie`

#### Scenarios

- [ ] An already logged-in session is not affected by a block on its IP.
- [ ] Remember-me cookie login still works.

### Fresh Build

**Intent:** A new project gets the feature from CodeBlocks without manual steps (apart from the SQL).

**Surfaces:**
- `init-web/1-Install.php`
- `init-web/9-Install-CodeBlocks_application.php`

#### Scenarios

- [ ] A fresh build loads `ThrottledFormAuthenticator` instead of `Authentication.Form`.
- [ ] Running the transform a second time changes nothing.
- [ ] The old `FormLoginAttemptsAuthenticator` / `FormAttemptsTable` are no longer present in a fresh build.
