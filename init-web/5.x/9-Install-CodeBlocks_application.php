<?php

// CakePHP 5.x Application.php: authentication service (cakephp/authentication 4.x).
// 4.x removed AuthenticationService::loadIdentifier(); each authenticator gets
// its own 'identifier' config instead.
// Session + Cookie (remember me) + Form. Login throttling is ported later.

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

if (strpos($contents, 'public function getAuthenticationService(') !== false) {
    echo "Application authentication setup already exists — skipping<br/>";
    return;
}

$imports = [
    "use Authentication\\AuthenticationService;",
    "use Authentication\\AuthenticationServiceInterface;",
    "use Authentication\\AuthenticationServiceProviderInterface;",
    "use Authentication\\Identifier\\PasswordIdentifier;",
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
}

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

$methodBlock = <<<'PHP'

    /**
     * Authentication service (cakephp/authentication 4.x).
     *
     * Users log in with email + password from the shared `users` table. Prefix
     * access is enforced by RbacMiddleware + AccessMiddleware, not here.
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $authenticationService = new AuthenticationService([
            'queryParam' => 'redirect',
        ]);

        $fields = [
            PasswordIdentifier::CREDENTIAL_USERNAME => 'email',
            PasswordIdentifier::CREDENTIAL_PASSWORD => 'password',
        ];
        $identifier = [
            'className' => 'Authentication.Password',
            'fields' => $fields,
        ];
        $loginUrls = $this->getAuthenticationService_loginUrls();

        $authenticationService->loadAuthenticator('Authentication.Session');
        $authenticationService->loadAuthenticator('Authentication.Cookie', [
            'identifier' => $identifier,
            'fields' => $fields,
            'urlChecker' => 'Authentication.Multi',
            'loginUrl' => $loginUrls,
            'cookie' => [
                'name' => 'remember_me_cookie',
                'expires' => strtotime('+30 days'),
                'httponly' => true,
            ],
        ]);
        $authenticationService->loadAuthenticator('Authentication.Form', [
            'identifier' => $identifier,
            'fields' => $fields,
            'urlChecker' => 'Authentication.Multi',
            'loginUrl' => $loginUrls,
        ]);

        return $authenticationService;
    }

    /**
     * One login URL per allowed language, without the base folder (the URL
     * checker adds it).
     *
     * @return array<string>
     */
    private function getAuthenticationService_loginUrls(): array
    {
        $allowedLanguages = Configure::read('allowedLanguages');
        if (!is_array($allowedLanguages) || empty($allowedLanguages)) {
            $allowedLanguages = ['en'];
        }

        $urls = [];
        foreach ($allowedLanguages as $lang) {
            $urls[] = '/' . strtolower((string)$lang) . '/login';
        }

        return array_values(array_unique($urls));
    }

PHP;

$pos = strrpos($contents, "\n}");
if ($pos === false) {
    echo "ERROR - class closing brace not found";
    exit(1);
}

$contents = substr($contents, 0, $pos) . $methodBlock . substr($contents, $pos);

file_put_contents($applicationFile, $contents);

echo "Application authentication setup added successfully<br/><br/>";
