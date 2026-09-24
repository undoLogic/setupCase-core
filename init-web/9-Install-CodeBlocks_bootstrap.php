<?php

$CONFIG = dirname(__DIR__) . '/sourceFiles/config/';
$bootstrapFile = $CONFIG . 'bootstrap.php';

if (!file_exists($bootstrapFile)) {
    echo "ERROR - bootstrap.php not found";
    exit(1);
}

$contents = file_get_contents($bootstrapFile);

if ($contents === false) {
    echo "ERROR - Could not read bootstrap.php";
    exit(1);
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);

if (strpos($contents, "if (file_exists(CONFIG . 'bootstrap-setupCase.php')) {") !== false) {
    echo "bootstrap-setupCase hook already exists — skipping<br/>";
    return;
}

$legacyBlock = "    if (file_exists(\$this->configDir . 'bootstrap-setupCase.php')) {\n        require_once \$this->configDir . 'bootstrap-setupCase.php';\n    }\n";
$newBlock = "if (file_exists(CONFIG . 'bootstrap-setupCase.php')) {\n    require_once CONFIG . 'bootstrap-setupCase.php';\n}\n";

if (strpos($contents, $legacyBlock) !== false) {
    $contents = str_replace($legacyBlock, $newBlock, $contents, $count);
    if ($count !== 1) {
        echo "ERROR - Could not replace legacy bootstrap-setupCase hook";
        exit(1);
    }
    file_put_contents($bootstrapFile, $contents);
    echo "bootstrap-setupCase hook repaired successfully<br/><br/>";
    return;
}

$anchor = "if (file_exists(CONFIG . 'app_local.php')) {\n    Configure::load('app_local', 'default');\n}\n";
$contents = str_replace($anchor, $anchor . $newBlock, $contents, $count);

if ($count !== 1) {
    echo "ERROR - app_local anchor not found";
    exit(1);
}

file_put_contents($bootstrapFile, $contents);

echo "bootstrap-setupCase hook added successfully<br/><br/>";
