# Compatibility checks

What was tested, how, and how to repeat it before a release. The plugin headers
(`Tested up to`, `WC tested up to`) must only claim what was actually run.

## Last full run: 2026-10-10 (plugin 1.4.0 plus changes up to PR #104)

| Component | Version | Result |
|---|---|---|
| WordPress | 7.1.3 (latest) | all checks, log, admin pages |
| WooCommerce | 11.2.1 (latest) | all checks, log, HPOS, admin pages |
| PHP | 8.5 | no notices or deprecations from the plugin |

Method: a **throwaway site** (own table prefix, own folder, deleted afterwards), WordPress 7.1.3
with WooCommerce 11.2.1 and the plugin built from `develop`, driven with WP-CLI:

- `wp ehc check`: all 9 checks run (including the WooCommerce ones).
- HPOS: `FeaturesUtil::get_compatible_plugins_for_feature()` lists the plugin as compatible for
  `custom_order_tables` and `cart_checkout_blocks`; then `wp wc hpos enable`, 8 orders created
  (6 pending, 2 processing, dated 3 hours back): the Unpaid Orders check counted 6 against 2.
- WooCommerce emails: New order and Processing order emails triggered; the log recorded them as
  source **WooCommerce** with the right email type (the sends failed only because the CLI has no
  sendmail, which also proved the failed-status path).
- `AdminPage::render()` for the report shell, the synchronous report (`ecehc_sync=1`) and the Email Log tab
  rendered with no PHP notices or deprecations.

Not covered: the real wp-admin screens in a browser on WooCommerce 11 (only rendered through PHP), HPOS
turned off with live orders, multisite, older WordPress/WooCommerce versions (the declared minimums,
WordPress 5.0 and `WC requires at least: 8.0`, are conservative claims that were never run).

## How to repeat it (about 10 minutes)

1. Make a scratch site that does not touch your real one: a separate database, or a separate table
   prefix (`wp config create --dbprefix=ecehctmp_ ...`) in a development database, in a temporary folder.
2. `wp core install`, `wp plugin install woocommerce --activate`, copy the plugin
   (`git archive develop | tar -x -C wp-content/plugins/ecom-email-health-check`), activate it.
3. Run the steps above. Also run Plugin Check on a copy built with `.distignore` (see the release skill).
4. Delete the scratch site and drop every table with its prefix.
5. Update the headers and the table in this file.

## WordPress 7.2 and later

Re-run this on each WordPress beta/RC and on every WooCommerce major release, then bump the headers.
