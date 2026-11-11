<?php

// CakePHP 5.x middleware. Step 1: LangMiddleware only, after routing (it reads
// the route params) and before CSRF.
// Step 2 adds EncryptedCookie, Authentication, Rbac and Access middleware here.

$applicationFile = dirname(__DIR__, 2) . '/sourceFiles/src/Application.php';

if (!file_exists($applicationFile)) {
    echo "ERROR - Application.php not found";
    exit(1);
}

$contents = file_get_contents($applicationFile);

if ($contents === false) {
    echo "ERROR - Could not read Application.php";
    exit(1);
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);

if (strpos($contents, '->add(new LangMiddleware())') !== false) {
    echo "Application middleware setup already exists — skipping<br/>";
    return;
}

$import = "use App\\Middleware\\LangMiddleware;";

if (preg_match('/namespace App;\n\n((?:use [^\n]+;\n)+)/', $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
    echo "ERROR - use import block not found";
    exit(1);
}

if (strpos($contents, $import) === false) {
    $importInsertPos = $matches[1][1] + strlen($matches[1][0]);
    $contents = substr($contents, 0, $importInsertPos) . $import . "\n" . substr($contents, $importInsertPos);
}

$insert = <<<'PHP'
            //Added by SetupCase-core
            ->add(new LangMiddleware())

PHP;

$needle = "            // Cross Site Request Forgery (CSRF) Protection Middleware\n";
$contents = str_replace($needle, $insert . $needle, $contents, $count);

if ($count !== 1) {
    echo "ERROR - CSRF anchor not found";
    exit(1);
}

file_put_contents($applicationFile, $contents);

echo "Application middleware setup added successfully<br/><br/>";
