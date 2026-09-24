<?php

$routesFile = dirname(__DIR__) . '/sourceFiles/config/routes.php';

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
$updated = false;

$rootRoutes = <<<'PHP'
         $builder->connect('/', ['controller' => 'CodeBlocks', 'action' => 'index']);

        $builder->connect(
            '/{language}',
            ['controller' => 'CodeBlocks', 'action' => 'index'],
            ['language' => 'en|fr|es']
        );
        $builder->connect(
            '/{language}/csv',
            ['controller' => 'CodeBlocks', 'action' => 'download-csv'],
            ['language' => 'en|fr|es']
        );

        // Login and Logout
        $builder->connect('/{language}/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/{language}/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/{language}/beginReset', ['controller' => 'Users', 'action' => 'beginReset']);
        $builder->connect('/{language}/reset', ['controller' => 'Users', 'action' => 'reset']);

        // language
        $builder->connect('/{language}', ['controller' => 'Pages', 'action' => 'home'], ['language' => 'en|fr|es']);
        $builder->connect('/{language}/{controller}/{action}/*', [], ['language' => 'en|fr|es']);
        $builder->connect('/{language}/{controller}', ['action' => 'index'], ['language' => 'en|fr|es']);


PHP;

if (strpos($contents, "\$builder->connect('/{language}/login', ['controller' => 'Users', 'action' => 'login']);") === false) {
    $needle = "        \$builder->fallbacks();\n";
    $contents = str_replace($needle, $rootRoutes . $needle, $contents, $count);
    if ($count !== 1) {
        echo "ERROR - root scope fallback anchor not found";
        exit(1);
    }
    $updated = true;
}

// Remove individual Staff/Admin/Manager prefix blocks if they exist.
$pattern = <<<'REGEX'
/\n\s*\$routes->prefix\('(Staff|staff|Admin|admin|Manager|manager)'\s*,\s*function\s*\(RouteBuilder \$routes\)\s*\{.*?\n\s*\}\);\n/s
REGEX;
$contents = preg_replace($pattern, "\n", $contents, -1, $removedCount);
if ($removedCount > 0) {
    $updated = true;
}

$prefixBlock = <<<'PHP'

    foreach (['Staff', 'Admin', 'Manager'] as $prefix) {

        $routes->prefix($prefix, function (RouteBuilder $routes) {

            $routes->setRouteClass(DashedRoute::class);

            $routes->connect(
                '/{language}/{controller}',
                ['action' => 'index']
            )->setPatterns([
                'language' => 'en|fr|es'
            ]);

            $routes->connect(
                '/{language}/{controller}/{action}/*'
            )->setPatterns([
                'language' => 'en|fr|es'
            ]);

            $routes->fallbacks(DashedRoute::class);
        });
    }

PHP;

if (strpos($contents, "foreach (['Staff', 'Admin', 'Manager'] as \$prefix)") === false) {
    $needle = "\n\n    /*\n     * If you need a different set of middleware or none at all,\n";
    $contents = str_replace($needle, $prefixBlock . $needle, $contents, $count);
    if ($count !== 1) {
        echo "ERROR - API comment anchor not found for prefix block";
        exit(1);
    }
    $updated = true;
}

if (!$updated) {
    echo "Routes setup already exists — skipping<br/>";
    return;
}

file_put_contents($routesFile, $contents);

echo "Routes setup added successfully<br/><br/>";
