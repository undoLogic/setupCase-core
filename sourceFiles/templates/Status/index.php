<?php
/**
 * Status Endpoint monitor page (full HTML, no layout, no database). status-endpoint.js
 * re-checks every $statusCheckMinutes. The token is never printed; the JS re-fetches its own URL.
 * Spec: docs/features/MVP-Status-Endpoint.md
 *
 * @var array $status app, generated_at, window_hours, errors, warnings, ignored, php_version, cakephp_version
 * @var string $statusState ok|errors
 * @var string $statusTitle
 * @var int $statusCheckMinutes
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= h($statusTitle); ?></title>
    <link rel="icon" id="status-endpoint-favicon" href="data:,">
    <?= $this->Html->css('status-endpoint'); ?>
</head>
<body class="status-endpoint">
<main class="status-endpoint-card" data-state="<?= h($statusState); ?>"
      data-app="<?= h($status['app']); ?>" data-check-minutes="<?= h($statusCheckMinutes); ?>">
    <p class="status-endpoint-app"><?= h($status['app']); ?></p>
    <div class="status-endpoint-banner" data-field="banner">Checking...</div>
    <dl class="status-endpoint-counts">
        <div><dt>Errors</dt><dd data-field="errors"><?= h($status['errors']); ?></dd></div>
        <div><dt>Warnings</dt><dd data-field="warnings"><?= h($status['warnings']); ?></dd></div>
    </dl>
    <p class="status-endpoint-note">Last <span data-field="window_hours"><?= h($status['window_hours']); ?></span> hours, from <code>logs/error.log</code> and <code>logs/cli-error.log</code>.</p>
    <table class="status-endpoint-meta">
        <tr><th>Last checked</th><td data-field="checked"><?= h($status['generated_at']); ?></td></tr>
        <tr><th>Next check</th><td data-field="next">-</td></tr>
        <tr><th>Ignored</th><td data-field="ignored"><?= h($status['ignored']); ?></td></tr>
        <tr><th>PHP</th><td data-field="php_version"><?= h($status['php_version']); ?></td></tr>
        <tr><th>CakePHP</th><td data-field="cakephp_version"><?= h($status['cakephp_version']); ?></td></tr>
    </table>
    <button type="button" class="status-endpoint-button" data-action="check">Check now</button>
</main>
<?= $this->Html->script('status-endpoint'); ?>
</body>
</html>
