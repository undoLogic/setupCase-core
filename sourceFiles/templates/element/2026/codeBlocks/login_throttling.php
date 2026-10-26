<?php
/**
 * CodeBlocks guide for the Login Throttling block.
 * Spec: docs/features/MVP-login_throttling.md
 *
 * @var \App\View\AppView $this
 * @var int $throttleMaxFailures
 * @var int $throttleMaxFailuresPerIp
 * @var int $throttleWindowMinutes
 * @var string $throttleLoginUrl
 */
?>
<div class="alert alert-warning">
    <strong>Basic safeguard only.</strong> This slows down password guessing. It is not a replacement
    for a security review, and it does not protect against vulnerabilities in PHP, frameworks or libraries.
</div>

<h2 class="h4">Current settings</h2>
<table class="table table-sm mb-4 w-auto">
    <tbody>
        <tr><th>Failures before blocking</th><td><?= h($throttleMaxFailures); ?> per IP + username</td></tr>
        <tr><th>IP-wide cap</th><td><?= h($throttleMaxFailuresPerIp); ?> per IP, any username</td></tr>
        <tr><th>Window / cooldown</th><td><?= h($throttleWindowMinutes); ?> minutes</td></tr>
        <tr><th>Counted per</th><td>IP address + username (trimmed, lowercased)</td></tr>
        <tr><th>Stored in</th><td><code>login_attempts</code> table (failures only, username as SHA-256)</td></tr>
    </tbody>
</table>

<h2 class="h4">How it works</h2>
<ol class="mb-4">
    <li class="mb-2">
        A login POST reaches <code>ThrottledFormAuthenticator</code>, which replaces
        <code>Authentication.Form</code> in <code>Application.php</code>.
    </li>
    <li class="mb-2">
        Before the password is checked, it counts the failures for this IP + username in the last
        <?= h($throttleWindowMinutes); ?> minutes.
    </li>
    <li class="mb-2">
        <strong><?= h($throttleMaxFailures); ?> or more</strong> for this IP + username, or
        <strong><?= h($throttleMaxFailuresPerIp); ?> or more</strong> for this IP with any username: the login
        is refused without checking the password, even if it is correct. The response is HTTP 429.
        Nothing new is recorded while blocked.
    </li>
    <li class="mb-2">
        <strong>Otherwise</strong> the password is checked as usual. A failure is recorded; a success
        clears the failures for that IP + username.
    </li>
    <li class="mb-2">
        The block lifts on its own once the old failures are older than the window. Old rows are
        purged each time a new failure is recorded.
    </li>
</ol>

<h2 class="h4">Messages</h2>
<table class="table table-sm mb-4">
    <tbody>
        <tr><th>Wrong email or password</th><td><code>Email or password is incorrect.</code></td></tr>
        <tr><th>Blocked (HTTP 429)</th><td><code>Too many failed login attempts. Please try again in a few minutes.</code></td></tr>
    </tbody>
</table>
<p class="mb-4">
    Both are the same whether or not the account exists, and unknown usernames are blocked exactly
    like real ones, so the block cannot be used to find out which accounts are real.
</p>

<h2 class="h4">Try it</h2>
<ol class="mb-4">
    <li class="mb-2">
        Open the <a href="<?= h($throttleLoginUrl); ?>" target="_blank">login page</a> and enter any email
        with a wrong password <?= h($throttleMaxFailures); ?> times.
    </li>
    <li class="mb-2">The next attempt shows the "Too many failed login attempts" message, even with the right password.</li>
    <li class="mb-2">A different email from the same browser still gets the normal message.</li>
    <li class="mb-2">
        Watch the <code>login_attempts</code> table (e.g. in phpMyAdmin): one row per failure, no raw
        usernames. Wait <?= h($throttleWindowMinutes); ?> minutes, or delete the rows, to unblock.
    </li>
</ol>

<h2 class="h4">Behind a proxy or CDN</h2>
<p class="mb-2">
    By default the IP is <code>REMOTE_ADDR</code> and forwarded headers are ignored. If the app sits
    behind a proxy, set <code>TRUSTED_PROXIES</code> (comma-separated exact IPs) in the server
    <code>php.ini</code> or the Docker env. <code>X-Forwarded-For</code> is then used only when the
    request comes from one of those IPs.
</p>
<p class="mb-4">
    PHP cannot protect itself from a flood of requests: each one boots PHP before this check runs.
    Rate limiting belongs in front of the app (CDN, <code>mod_evasive</code>, or fail2ban watching the
    access log for <code>429</code> on the login URL).
</p>

<h2 class="h4">Setup on a project</h2>
<ol class="mb-4">
    <li class="mb-2">Run the schema SQL below on each environment (there is no automatic schema step yet).</li>
    <li class="mb-2">
        New projects get the files and the <code>Application.php</code> change from the CodeBlocks build.
        On an existing project, re-run <code>init-web/9-Install-CodeBlocks_application.php</code>.
    </li>
    <li class="mb-2">Set <code>TRUSTED_PROXIES</code> only if the app is behind a proxy or CDN.</li>
</ol>

<h2 class="h4">Known limitations</h2>
<ul class="mb-4">
    <li>Many IPs against one username (botnet) are only limited per IP.</li>
    <li>An office behind one IP is blocked for everyone if it reaches the IP-wide cap in one window.</li>
    <li>Remember-me cookie logins and the password reset form are not throttled.</li>
    <li><code>TRUSTED_PROXIES</code> takes exact IPs, not CIDR ranges.</li>
</ul>
