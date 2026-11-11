<?php

// CakePHP 5.x middleware, inserted after routing + body parsing and before CSRF:
//   Authentication -> Lang -> Rbac -> Access
// Authentication sets the identity, Rbac turns it into prefix access, and
// Access refuses prefixed requests the user may not reach (returns a redirect
// before any controller code runs).
// Not ported from 4.x: EncryptedCookieMiddleware.

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

if (strpos($contents, '->add(new AccessMiddleware())') !== false) {
    echo "Application middleware setup already exists — skipping<br/>";
    return;
}

$imports = [
    "use App\\Middleware\\AccessMiddleware;",
    "use App\\Middleware\\LangMiddleware;",
    "use App\\Middleware\\RbacMiddleware;",
    "use Authentication\\Middleware\\AuthenticationMiddleware;",
];

if (preg_match('/namespace App;\n\n((?:use [^\n]+;\n)+)/', $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
    echo "ERROR - use import block not found";
    exit(1);
}

$importInsertPos = $matches[1][1] + strlen($matches[1][0]);

foreach ($imports as $import) {
    if (strpos($contents, $import) !== false) {
        continue;
    }

    $contents = substr($contents, 0, $importInsertPos) . $import . "\n" . substr($contents, $importInsertPos);
    $importInsertPos += strlen($import) + 1;
}

$insert = <<<'PHP'
            //Added by SetupCase-core
            ->add(new AuthenticationMiddleware($this))
            ->add(new LangMiddleware())
            ->add(new RbacMiddleware())
            ->add(new AccessMiddleware())

PHP;

$needle = "            // Cross Site Request Forgery (CSRF) Protection Middleware\n";
$contents = str_replace($needle, $insert . $needle, $contents, $count);

if ($count !== 1) {
    echo "ERROR - CSRF anchor not found";
    exit(1);
}

file_put_contents($applicationFile, $contents);

echo "Application middleware setup added successfully<br/><br/>";
