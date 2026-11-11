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
 * @var \App\View\AppView $this
 * @var string $message
 * @var array $params
 */
echo $this->element('flash/bootstrap_alert', ['message' => $message, 'params' => $params, 'alert' => 'secondary', 'type' => '']);
