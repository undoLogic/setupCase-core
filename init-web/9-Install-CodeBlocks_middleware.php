<?php
/*

8.1.3. Now add to the same file Application.php add ABOVE the CSRF (sourceFiles\src\Application.php): NOTE: You will need to right click and import these classes after you paste


```php
//Added by SetupCase-core
->add(new EncryptedCookieMiddleware(
    ['CookieAuth'],
    'CHANGEMEWITHSECURE'
))
->add(new AuthenticationMiddleware($this))
->add(new LangMiddleware())
->add(new RbacMiddleware())
->add(new AccessMiddleware())
```

Ensure you import the required classes at the top of the file

```php
    use App\Authenticator\FormLoginAttemptsAuthenticator;
    use Cake\Http\Middleware\EncryptedCookieMiddleware;
```
```

*/


$applicationFile = dirname(__DIR__) . '/sourceFiles/src/Application.php';

if (!file_exists($applicationFile)) {
    echo "ERROR - Application.php not found";
    exit;
}

$contents = file_get_contents($applicationFile);

if ($contents === false) {
    echo "ERROR - Could not read Application.php";
    exit;
}

$contents = str_replace(["\r\n", "\r"], "\n", $contents);
$updated = false;

$imports = [
    "use App\\Middleware\\AccessMiddleware;",
    "use App\\Middleware\\LangMiddleware;",
    "use App\\Middleware\\RbacMiddleware;",
    "use Authentication\\Middleware\\AuthenticationMiddleware;",
    "use Cake\\Http\\Middleware\\EncryptedCookieMiddleware;",
];

if (preg_match('/namespace App;\n\n((?:use [^\n]+;\n)+)/', $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
    echo "ERROR - use import block not found";
    exit;
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

if (strpos($contents, '->add(new EncryptedCookieMiddleware(') === false) {
    $insert = <<<'PHP'
            //Added by SetupCase-core
            ->add(new EncryptedCookieMiddleware(
                ['CookieAuth'],
                'CHANGEMEWITHSECURE'
            ))
            ->add(new AuthenticationMiddleware($this))
            ->add(new LangMiddleware())
            ->add(new RbacMiddleware())
            ->add(new AccessMiddleware())

PHP;

    $needle = "            // Cross Site Request Forgery (CSRF) Protection Middleware\n";
    $contents = str_replace($needle, $insert . $needle, $contents, $count);

    if ($count !== 1) {
        echo "ERROR - CSRF anchor not found";
        exit;
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

if (!$updated) {
    echo "Application middleware setup already exists — skipping<br/>";
    exit;
}

file_put_contents($applicationFile, $contents);

echo "Application middleware setup added successfully<br/><br/>";
