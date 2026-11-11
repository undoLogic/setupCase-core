<?php

// CakePHP 5.x routes. Step 1: language routes to CodeBlocks only.
// Step 2 adds the login/logout routes and the Staff/Admin/Manager prefix routes.
// Until then prefixed controllers are not reachable by URL.

$routesFile = dirname(__DIR__, 2) . '/sourceFiles/config/routes.php';

if (!file_exists($routesFile)) {
    echo "ERROR - routes.php not found";
    exit(1);
}

$contents = file_get_contents($routesFile);

if ($contents === false) {
    echo "ERROR - Could not read routes.php";
    exit(1);
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);

$marker = "// SetupCase language routes";

if (strpos($contents, $marker) !== false) {
    echo "Routes setup already exists — skipping<br/>";
    return;
}

$rootRoutes = <<<'PHP'
        // SetupCase language routes
        $builder->connect('/', ['controller' => 'CodeBlocks', 'action' => 'index']);
        $builder->connect(
            '/{language}',
            ['controller' => 'CodeBlocks', 'action' => 'index'],
            ['language' => 'en|fr|es']
        );
        $builder->connect('/{language}/{controller}/{action}/*', [], ['language' => 'en|fr|es']);
        $builder->connect('/{language}/{controller}', ['action' => 'index'], ['language' => 'en|fr|es']);


PHP;

// The skeleton's home route points at Pages::display, which SetupCase does not use
$skeletonHome = "        \$builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);\n";
$contents = str_replace($skeletonHome, '', $contents, $count);
if ($count !== 1) {
    echo "ERROR - skeleton home route not found";
    exit(1);
}

$needle = "        \$builder->fallbacks();\n";
$contents = str_replace($needle, $rootRoutes . $needle, $contents, $count);
if ($count !== 1) {
    echo "ERROR - root scope fallback anchor not found";
    exit(1);
}

file_put_contents($routesFile, $contents);

echo "Routes setup added successfully<br/><br/>";
