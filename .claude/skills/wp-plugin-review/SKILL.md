---
name: wp-plugin-review
description: Review changes to this plugin against WordPress.org plugin-directory rules and WP security/coding standards. Use before committing or releasing, or when asked to review/audit.
---

# WP plugin review checklist

Run `git diff` (or review whole plugin when asked) and check:

**Security**
- Output escaped late (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); no raw `_e()`/`echo` of dynamic data.
- Input: `wp_unslash` + sanitize `$_POST/$_GET`. State changes require `current_user_can` AND nonce.
- No direct file access (`ABSPATH` guard), no `eval`, no remote code.

**WP.org guidelines**
- GPL-compatible, no obfuscation, no tracking/phoning-home without opt-in, no hijacked admin (upsell notices must be dismissible/limited). SaaS CTAs must be non-intrusive and clearly labelled.
- Text domain matches slug; all strings translatable.
- `readme.txt` valid (Stable tag = plugin version, tested-up-to, Requires PHP).

**Behavior**
- Checks have no side effects (no real emails to third parties) and are not run repeatedly per request.
- Redirect/menu slugs consistent (see Known issues in CLAUDE.md).
- `php -l` passes; works with `WP_DEBUG` on, PHP 7.4–8.3.

Report findings by severity with file:line, and don't change code unless asked.
