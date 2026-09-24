<?php

$applicationFile = dirname(__DIR__) . '/sourceFiles/src/Application.php';

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
$updated = false;

$imports = [
    "use Authentication\\AuthenticationService;",
    "use Authentication\\AuthenticationServiceInterface;",
    "use Authentication\\AuthenticationServiceProviderInterface;",
    "use Psr\\Http\\Message\\ServerRequestInterface;",
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
    $updated = true;
}

if (strpos($contents, 'implements AuthenticationServiceProviderInterface') === false) {
    $contents = preg_replace(
        '/class Application extends BaseApplication(\s*{)/',
        'class Application extends BaseApplication implements AuthenticationServiceProviderInterface$1',
        $contents,
        1,
        $count
    );
    if ($count !== 1) {
        echo "ERROR - class declaration anchor not found";
        exit(1);
    }
    $updated = true;
}

if (strpos($contents, '->add(new AuthenticationMiddleware($this->getAuthenticationService()))') !== false) {
    $contents = str_replace(
        '->add(new AuthenticationMiddleware($this->getAuthenticationService()))',
        '->add(new AuthenticationMiddleware($this))',
        $contents,
        $count
    );
    if ($count > 0) {
        $updated = true;
    }
}

$newMethodBlock = <<<'PHP'

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $loginUrl = $this->getAuthenticationService_loginUrl($request);
        $authenticationService = new AuthenticationService([
            'unauthenticatedRedirect' => $loginUrl,
            'queryParam' => 'redirect',
        ]);
        $fields = [
            'username' => 'email',
            'password' => 'password',
        ];
        $authenticationService->loadIdentifier('Authentication.Password', [
            'fields' => $fields,
        ]);
        $authenticationService->loadAuthenticator('Authentication.Session');
        $authenticationService->loadAuthenticator('Authentication.Form', [
            'fields' => $fields,
            'loginUrl' => $loginUrl,
        ]);
        $authenticationService->loadAuthenticator('Authentication.Cookie', [
            'fields' => $fields,
            'loginUrl' => $loginUrl,
            'cookie' => [
                'name' => 'remember_me_cookie',
                'expires' => strtotime('+30 days'),
                'httponly' => true,
            ],
        ]);
        return $authenticationService;
    }

    private function getAuthenticationService_loginUrl(ServerRequestInterface $request): string
    {
        $allowedLanguages = Configure::read('allowedLanguages');
        if (!is_array($allowedLanguages) || empty($allowedLanguages)) {
            $allowedLanguages = ['en'];
        }
        $allowedLanguages = array_values(array_unique(array_map(
            static fn($lang): string => strtolower((string)$lang),
            $allowedLanguages
        )));
        $defaultLang = in_array('en', $allowedLanguages, true) ? 'en' : $allowedLanguages[0];
        $basePath = rtrim((string)$request->getAttribute('base'), '/');

        return $basePath . '/' . $defaultLang . '/login';
    }

PHP;

if (strpos($contents, 'public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface') === false) {
    if (strpos($contents, 'protected function getAuthenticationService() : AuthenticationService') !== false) {
        $contents = preg_replace(
            '/\n\s*protected function getAuthenticationService\(\)\s*:\s*AuthenticationService\s*\{.*?\n\s*\}\n/s',
            $newMethodBlock,
            $contents,
            1,
            $count
        );
        if ($count !== 1) {
            echo "ERROR - old getAuthenticationService method replacement failed";
            exit(1);
        }
    } else {
        $needle = "        return \$middlewareQueue;\n    }\n";
        $contents = str_replace($needle, $needle . $newMethodBlock, $contents, $count);
        if ($count !== 1) {
            echo "ERROR - middleware anchor not found";
            exit(1);
        }
    }
    $updated = true;
}

$newLoginUrlHelperBlock = <<<'PHP'
    private function getAuthenticationService_loginUrl(ServerRequestInterface $request): string
    {
        $allowedLanguages = Configure::read('allowedLanguages');
        if (!is_array($allowedLanguages) || empty($allowedLanguages)) {
            $allowedLanguages = ['en'];
        }
        $allowedLanguages = array_values(array_unique(array_map(
            static fn($lang): string => strtolower((string)$lang),
            $allowedLanguages
        )));
        $defaultLang = in_array('en', $allowedLanguages, true) ? 'en' : $allowedLanguages[0];
        $basePath = rtrim((string)$request->getAttribute('base'), '/');

        return $basePath . '/' . $defaultLang . '/login';
    }
PHP;

if (strpos($contents, "return \$basePath . '/login';") !== false) {
    $contents = preg_replace(
        '/\n\s*private function getAuthenticationService_loginUrl\(ServerRequestInterface \$request\): string\s*\{\s*\$basePath = rtrim\(\(string\)\$request->getAttribute\(\'base\'\), \'\/\'\);\s*return \$basePath \. \'\/login\';\s*\}\n/s',
        "\n\n" . $newLoginUrlHelperBlock . "\n",
        $contents,
        1,
        $count
    );
    if ($count > 0) {
        $updated = true;
    }
}

if (strpos($contents, 'use Cake\\Routing\\Router;') !== false && strpos($contents, 'Router::') === false) {
    $contents = str_replace("use Cake\\Routing\\Router;\n", '', $contents, $count);
    if ($count > 0) {
        $updated = true;
    }
}

if (!$updated) {
    echo "Application authentication setup already exists — skipping<br/>";
    return;
}

file_put_contents($applicationFile, $contents);

echo "Application authentication setup added successfully<br/><br/>";
