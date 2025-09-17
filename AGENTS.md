# Repository Guidelines

## Project Structure & Module Organization
- Entry points: `index.php` (front controller), `bootstrap.php` (env, autoload).
- Core MVC:
  - `app/Controllers` (request handlers), `app/Models` (DB access), `app/Services` (domain logic), `app/Views` (PHP templates), `app/Core` (Router, Controller, Database, Model base).
- Config & assets: `config/` (`routes.php`, `database.php`, `config.php`), `assets/` (css, js, images).
- Runtime: `storage/` (logs, sessions, uploads). Docs: `docs/`, `system-documentation/`.

## Build, Test, and Development Commands
- Setup env: `cp .env.example .env` then set `DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD`, `APP_ENV=development`.
- Database (example): `mysql -u root -p -e "CREATE DATABASE azteamerp"` then `mysql -u root -p azteamerp < azteamerp_production.sql` (or another dump).
- Run locally (Apache): enable `.htaccess` and point DocumentRoot to repo root.
- Run locally (PHP server): create a simple router if needed, then `php -S localhost:8000 router.php` (router should return false for existing files and include `index.php` otherwise).
- Quick lint: `find app -name "*.php" -print0 | xargs -0 -n1 php -l`.

## Coding Style & Naming Conventions
- PHP PSR-12 style, 4-space indentation, UTF-8. One class per file.
- Controllers end with `Controller` (e.g., `CustomerController`), actions use verbs (`index`, `show`, `store`, `update`).
- Models are singular (`Order`, `Customer`). Views live in `app/Views/<area>/...` and must escape output: `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`.
- Define routes in `config/routes.php` using `'/customers/{id}' => 'CustomerController@show'`.

## Testing Guidelines
- No formal test suite yet. Validate flows manually: login (`/login`), Customers CRUD, Orders (create/edit/items/status), Production views.
- Sanity checks: run the lint command and verify logs in `storage/logs` are clean.
- Prefer dependency-free PHP and parameterized queries via `App\Core\Database`.

## Commit & Pull Request Guidelines
- Commit messages: imperative and scoped, e.g., `customers: add inline search` or `orders: sanitize sort input`.
- PRs must include: summary, affected routes/screens, screenshots for UI changes, migration notes if DB/schema touched, and linked issue/plan.
- Keep diffs focused; update `system-documentation/` if you change routing, data flows, or architecture.

## Security & Configuration Tips
- Never commit secrets; use `.env`. Keep `APP_ENV=production` and secure cookies in prod.
- Use `$this->requireAuth()` for protected actions; include CSRF tokens in forms (`$this->csrf()` and `verifyCsrf`).
- Sanitize inputs (`$this->sanitize`) and escape outputs in views.

For deeper context, see `system-documentation/04-routing-system.md` and related docs.

