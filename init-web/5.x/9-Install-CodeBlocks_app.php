<?php

// CakePHP 5.x config/app.php. Step 1: allowedLanguages only.
// Step 2 adds the 'rbac' role => prefix map.

$appConfigFile = dirname(__DIR__, 2) . '/sourceFiles/config/app.php';

if (!file_exists($appConfigFile)) {
    echo "ERROR - app.php not found";
    exit(1);
}

$contents = file_get_contents($appConfigFile);

if ($contents === false) {
    echo "ERROR - Could not read app.php";
    exit(1);
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);

if (strpos($contents, "'allowedLanguages' => [") !== false) {
    echo "app.php language config already exists — skipping<br/>";
    return;
}

$insert = <<<'PHP'

    'allowedLanguages' => [
        'en',
        //'fr',
        //'es'
    ],

PHP;

$anchor = "    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),\n";
$contents = str_replace($anchor, $anchor . $insert, $contents, $count);

if ($count !== 1) {
    echo "ERROR - debug anchor not found in app.php";
    exit(1);
}

file_put_contents($appConfigFile, $contents);

echo "app.php language config added successfully<br/><br/>";
