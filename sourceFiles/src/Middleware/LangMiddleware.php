<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Lang middleware
 *
 * Add to App.php
 * 'allowedLanguages' => [
 * 'en',
 * //'fr',
 * //'es'
 * ],
 *
 *
 */
class LangMiddleware implements MiddlewareInterface
{
    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = $request->getAttribute('params') ?? [];
        $urlLang = isset($params['language']) ? strtolower((string)$params['language']) : '';
        $sessionLang = strtolower((string)$request->getSession()->read('lang'));
        $browserLang = $this->process_extractBrowserLang($request->getHeaderLine('Accept-Language'));

        $allowedLanguages = Configure::read('allowedLanguages');
        if (!is_array($allowedLanguages) || empty($allowedLanguages)) {
            $allowedLanguages = ['en'];
        }
        $allowedLanguages = array_values(array_unique(array_map(
            static fn($lang): string => strtolower((string)$lang),
            $allowedLanguages
        )));

        $defaultLang = in_array('en', $allowedLanguages, true) ? 'en' : $allowedLanguages[0];

        if ($urlLang !== '' && !in_array($urlLang, $allowedLanguages, true)) {
            return (new Response())
                ->withStatus(302)
                ->withHeader('Location', $this->process_buildRedirectUrl($request, $defaultLang));
        }

        $missingUrlLang = true;

        if ($urlLang !== '') {
            $currentLang = $urlLang;
            $langFrom = 'url_lang';
            $missingUrlLang = false;
        } elseif ($sessionLang !== '' && in_array($sessionLang, $allowedLanguages, true)) {
            $currentLang = $sessionLang;
            $langFrom = 'session_lang';
        } elseif ($browserLang !== '' && in_array($browserLang, $allowedLanguages, true)) {
            $currentLang = $browserLang;
            $langFrom = 'browser_lang';
        } else {
            $currentLang = $defaultLang;
            $langFrom = 'default_lang';
        }

        //if address URL does NOT exist let's redireect to the URL with the latest language from any
        if ($missingUrlLang) {
            return (new Response())
                ->withStatus(302)
                ->withHeader('Location', $this->process_buildRedirectUrl($request, $currentLang));
        }

        $request = $request->withAttribute('lang', $currentLang);
        $request = $request->withAttribute('lang_from', $langFrom);
        $request->getSession()->write('lang', $currentLang);

        return $handler->handle($request);
    }

    private function process_extractBrowserLang(string $acceptLanguage): string
    {
        if ($acceptLanguage === '') {
            return '';
        }

        $first = strtolower(trim(explode(',', $acceptLanguage)[0]));
        if ($first === '') {
            return '';
        }

        return substr($first, 0, 2);
    }

    private function process_buildRedirectUrl(ServerRequestInterface $request, string $fallbackLanguage): string
    {
        $uri = $request->getUri();
        $fullPath = $uri->getPath();
        $basePath = rtrim((string)$request->getAttribute('base'), '/');
        $params = $request->getAttribute('params') ?? [];
        $urlLang = strtolower((string)($params['language'] ?? ''));
        $prefix = strtolower((string)($params['prefix'] ?? ''));

        $pathAfterBase = $fullPath;
        if ($basePath !== '' && strpos($fullPath, $basePath . '/') === 0) {
            $pathAfterBase = substr($fullPath, strlen($basePath));
        } elseif ($basePath !== '' && $fullPath === $basePath) {
            $pathAfterBase = '/';
        }

        $segments = explode('/', trim($pathAfterBase, '/'));
        if (count($segments) === 1 && $segments[0] === '') {
            $segments = [];
        }

        // Root routes: /{language}/...
        // Prefix routes: /{prefix}/{language}/...
        $languageIndex = 0;
        if ($prefix !== '' && isset($segments[0]) && strtolower($segments[0]) === $prefix) {
            $languageIndex = 1;
        }

        $replaced = false;
        if ($urlLang !== '') {
            if (isset($segments[$languageIndex]) && strtolower((string)$segments[$languageIndex]) === $urlLang) {
                $segments[$languageIndex] = $fallbackLanguage;
                $replaced = true;
            } else {
                foreach ($segments as $index => $segment) {
                    if (strtolower((string)$segment) === $urlLang) {
                        $segments[$index] = $fallbackLanguage;
                        $replaced = true;
                        break;
                    }
                }
            }
        }

        if (!$replaced) {
            array_splice($segments, $languageIndex, 0, [$fallbackLanguage]);
        }

        $pathAfterBase = '/' . implode('/', $segments);
        if ($pathAfterBase === '/') {
            $pathAfterBase = '/' . $fallbackLanguage;
        }

        if ($basePath !== '') {
            $path = $basePath . $pathAfterBase;
        } else {
            $path = $pathAfterBase;
        }

        if ($uri->getQuery() !== '') {
            $path .= '?' . $uri->getQuery();
        }

        return $path;
    }
}
