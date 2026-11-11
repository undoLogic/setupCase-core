<?php

// CakePHP 5.x routes: language routes, login/logout, and the Staff/Admin/Manager
// prefix routes. Prefix access is enforced by AccessMiddleware (config 'rbac').

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
        // Login and Logout
        $builder->connect('/{language}/login', ['controller' => 'Users', 'action' => 'login'], ['language' => 'en|fr|es']);
        $builder->connect('/{language}/logout', ['controller' => 'Users', 'action' => 'logout'], ['language' => 'en|fr|es']);

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

$prefixBlock = <<<'PHP'

    // SetupCase prefix routes (access: config 'rbac' + AccessMiddleware)
    foreach (['Staff', 'Admin', 'Manager'] as $prefix) {
        $routes->prefix($prefix, function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);

            $routes->connect('/{language}/{controller}', ['action' => 'index'])
                ->setPatterns(['language' => 'en|fr|es']);
            $routes->connect('/{language}/{controller}/{action}/*')
                ->setPatterns(['language' => 'en|fr|es']);

            $routes->fallbacks(DashedRoute::class);
        });
    }

PHP;

$needle = "\n    /*\n     * If you need a different set of middleware or none at all,\n";
$contents = str_replace($needle, $prefixBlock . $needle, $contents, $count);
if ($count !== 1) {
    echo "ERROR - middleware comment anchor not found for prefix block";
    exit(1);
}

file_put_contents($routesFile, $contents);

echo "Routes setup added successfully<br/><br/>";
