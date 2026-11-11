# CodeBlocks CakePHP 5.x Change Log

## 2026-11-11 - Step 1: CakePHP 5 loads

Ported from `../4.x/`, built with `init-web/5.x/1-Install.php` (CakePHP 5.4.4, PHP 8.3).

- sync: CodeBlocks layout, sidebar and sub-menu elements, `render_file` / `render_var` elements
- sync: `CodeBlocksController` with `index` and `applicationFoundation` only
- sync: Staff/Manager `CodeBlocksController` and their templates, shown as source on the Application Foundation page. Not routable until Step 2 adds prefix routes and RBAC.
- sync: `LangMiddleware`, `LangHelper`, `HtmlHelper`, `SetupCase` util, `bootstrap-setupCase.php`, `app_DEV.php`
- changed vs 4.x: `LangHelper::$_defaultConfig` and `HtmlHelper::link()` use CakePHP 5 typed signatures
- changed vs 4.x: sub-menu has no login/logout link (returns with authentication in Step 2)
- changed vs 4.x: `LangMiddleware` lets plugin routes through without a language, so the DebugKit toolbar loads
- changed vs 4.x: log `'scopes' => null` instead of `false` (deprecated in CakePHP 5)
- changed vs 4.x: Application Foundation page drops the duplicate "CREATE template" entry (4.x pointed it at the index template)
- transform: `init-web/5.x/9-*` routes (language only), bootstrap, middleware (Lang only), AppController (no Authentication), helpers (Lang only), app.php (allowedLanguages only)
