# eCommerce Email Health Check

Tiny WordPress plugin (WP.org slug `ecom-email-health-check`, prefix `ecehc`, text domain `ecom-email-health-check`). Adds an admin page that runs email-deliverability checks and a "send test email" form. (The former MailSir SaaS and its codesir.co domain no longer exist — do not link to them.)

## Layout
Namespace `CodeSir\EmailHealthCheck`, PSR-4 autoloaded from `includes/` (autoloader in the main file). Requires PHP 7.4+.
- `ecom-email-health-check.php` — header, constants (`ECEHC_VERSION/PLUGIN_FILE/PATH/URL/BASE`), autoloader, bootstrap only.
- `includes/Plugin.php` — singleton, wires hooks, plugin-list links. `includes/Activator.php` — activation redirect.
- `includes/Checks/` — `CheckInterface`, `Result`, one class per check, `CheckRunner` (runs each once; `ecehc_checks` filter).
- `includes/Admin/AdminPage.php` (menu `ecom-dashboard`, assets), `includes/Admin/TestEmailHandler.php` (nonce `ecehc_send_test_email`).
- `includes/Support/Domain.php` — domain helpers.
- `views/admin-page.php`, `assets/css/admin.css`, `uninstall.php`, `phpcs.xml`.
- `readme.txt` (WP.org) and `README.md` (GitHub) — keep in sync. `.wordpress-org/` = banners/icons. `.distignore` controls release zip.
- Deploy: pushing any git tag triggers `.github/workflows/deploy-with-tag.yml` (10up SVN deploy).

## Git workflow
- `develop` is the default branch. Every feature/fix branch (`fix/<n>-slug`, `feature/slug`) is branched from `develop` and PR'd into `develop`. Merged branches are auto-deleted by the repo setting.
- `trunk` is the released state (WordPress.org). Releases happen only by opening a PR `develop` → `trunk`, then tagging the merge commit on `trunk` (tag push triggers the deploy workflow).
- Version bump, changelog and `Tested up to` go in a release-prep PR into `develop` before the `develop` → `trunk` PR.
- Never push directly to `trunk`; never tag or push tags without explicit user approval.

## Conventions
- WP coding standards: tabs, Yoda-ish spacing `( $x )`, `array()` syntax, namespaced PSR-4 classes (`ecehc_` prefix for globals/hooks/options).
- Every PHP file starts with the `ABSPATH` guard. Escape all output (`esc_html`, `esc_url`), i18n all strings with the text domain.
- Admin actions: check `current_user_can( 'manage_options' )` + nonce.
- No external promotional links until a new domain exists; add UTM params to any future ones.
- No build step, no composer/npm. Test manually in the Local site (wp-admin → Email Health Check).

## Version bump touches 3 places
Plugin header `Version`, the `ECEHC_VERSION` constant, and `readme.txt` `Stable tag` (+ changelog).

## Known issues
Tracked as GitHub issues (`gh issue list`). The refactor was behaviour-preserving, so these bugs still exist until fixed individually.
