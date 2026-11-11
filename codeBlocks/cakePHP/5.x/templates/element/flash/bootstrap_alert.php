<?php
/**
 * SetupCase by undoLogic
 * Copyright (c) 2021-2026 undoLogic Inc.
 *
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE and NOTICE files in the project root.
 * This notice must be retained in copies or substantial portions of this file.
 */

/**
 * Shared Bootstrap alert for all Flash messages (success, error, warning, info, default).
 * The "message <type>" classes keep the CakePHP skeleton style on pages without Bootstrap.
 *
 * @var \App\View\AppView $this
 * @var string $message
 * @var array $params
 * @var string $alert Bootstrap colour: success, danger, warning, info, secondary
 * @var string $type  Skeleton class: success, error, warning, info, ''
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-<?= h($alert) ?> alert-dismissible fade show message <?= h($type) ?>" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
