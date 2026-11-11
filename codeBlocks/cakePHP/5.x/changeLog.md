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

## 2026-11-11 - Step 2: Login, RBAC and Access

Built with `init-web/5.x/1-Install.php` (CakePHP 5.4.4, cakephp/authentication 4.2.1).

- sync: `UsersController` (login, logout only), `UsersTable`, `User` entity, `templates/Users/login.php`
- sync: `RbacMiddleware`, `AccessMiddleware`, `AuthHelper`
- changed vs 4.x: `AccessMiddleware` refuses prefixed requests itself (redirect response), so no controller code runs; the Authentication component uses `requireIdentity => false`
- changed vs 4.x: `RbacMiddleware` gives an unknown `user_type` no access instead of an undefined index
- changed vs 4.x: `AuthHelper::isLoggedIn()` checks the identity, not the RBAC access attribute
- changed vs 4.x: login redirects through `getLoginRedirect()` (local URLs only)
- changed vs 4.x: `User` entity in `App\Model\Entity`, password and reset token hidden, only `email` mass-assignable
- changed vs 4.x: login template has no "Add User" link (signup not ported)
- transform: `init-web/5.x/9-Install-CodeBlocks_application.php` (new; identifier passed to Form and Cookie authenticators, one login URL per language with the Multi URL checker)
- transform: middleware Authentication -> Lang -> Rbac -> Access (no EncryptedCookieMiddleware), AppController Authentication component + `getUserId()`, Auth helper, `rbac` in app.php, login/logout + Staff/Admin/Manager prefix routes

## 2026-11-11 - Application Foundation with database

- sync: `CodeBlocksTable`, `CodeBlockTypesTable`, `CodeBlock` entity (only `code_block_type_id`, `name`, `description` mass-assignable)
- sync: Staff `CodeBlocksController` (index, create, view, duplicate) and Manager `CodeBlocksController` (edit, delete); menu "Blocks with DB"
- changed vs 4.x: `delete` and `duplicate` are POST only; `duplicate` copies only the editable fields
- changed vs 4.x: edit's `cameFrom` redirect only follows local paths
- changed vs 4.x: CakePHP 5 API (`orderBy`, `get($id)` without options); `create()` no longer reads an undefined `$cameFrom` after a failed save
- changed vs 4.x: `bootstrap-setupCase.php` adds a router URL filter that keeps the current language on array URLs, so postLink/form actions and redirects need no LangMiddleware redirect (a redirected POST arrived as GET)
- not ported: `AuditLog` behavior, CSV/PDF download actions

## 2026-11-11 - Users are created from the console only

- new: `bin/cake users add <email> <ADMIN|MANAGER|STAFF>` (`src/Command/UsersAddCommand.php`). The password is asked for without echo, or generated and shown once if left empty; never taken from the command line
- new: `UsersTable::addUser()` (STATUS/MSG array), `validationAddUser()` (valid email, role from config `rbac`, password min 12), `buildRules()` (unique email)
- changed vs 4.x: no public `UsersController::add()` / `addUser()`; there is no signup URL in 5.x

## 2026-11-11 - Bootstrap flash messages

- new: `templates/element/flash/{success,error,warning,info,default}.php` render one shared `flash/bootstrap_alert.php` (Bootstrap dismissible alert). The skeleton `message <type>` classes are kept so pages without Bootstrap (login uses the skeleton layout) still look right.
