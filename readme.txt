=== eCommerce Email Health Check ===
Contributors: engahmeds3ed
Tags: e-commerce, email, woocommerce, smtp, deliverability
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free, simple tool to diagnose and test your eCommerce email delivery, ensuring orders and notifications reach customers.

== Description ==

Stop worrying about lost eCommerce emails! This free, simple plugin helps you diagnose your store's email delivery health in minutes. It runs a series of checks to identify common issues that cause emails to end up in spam or fail to send altogether.

**Key Features:**

* **Email Sending Test:** A one-click test to confirm if your site can send emails.
* **Sender Address Check:** Verifies that your "From" address is configured correctly to avoid being flagged as spam.
* **SPF Record Validation:** Checks that your domain has a single, valid SPF record, and warns if it doesn't include your SMTP service (WP Mail SMTP, Post SMTP and FluentSMTP are detected).
* **DKIM and DMARC Checks:** Looks for a DKIM key and a DMARC policy, which Gmail and Yahoo expect for authenticated email.
* **SMTP / Mail Service Check:** Warns when your site seems to send email through plain PHP mail, and names the plugin that handles your outgoing email when it finds one.
* **Fix Guidance:** Failing SPF, DKIM and DMARC checks show a "How to fix this" panel with a copy-paste record where one is documented, and a link to your mail provider's official setup guide.
* **Site Health Integration:** The same checks appear under Tools > Site Health, so problems show up without opening the plugin.
* **Easy-to-Read Health Report:** Get a clear, actionable report with a summary of your email delivery status.

== Installation ==

### Via WordPress Dashboard
1. Go to `Plugins > Add New` in your WordPress dashboard.
2. Search for "eCommerce Email Health Check".
3. Click "Install Now" and then "Activate".

### Manual Installation
1. Download the plugin from the [WordPress.org Plugin Repository](https://wordpress.org/plugins/ecom-email-health-check/).
2. Unzip the file and upload the `ecom-email-health-check` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==
= What problem does this plugin solve? =
Many hosting providers have poor email delivery configurations that can silently fail, leaving you and your customers in the dark. This plugin diagnoses those issues so you can address them.

= What does the plugin check? =
It checks for common issues like email sending failures, sender address misconfigurations, and missing or incomplete SPF, DKIM and DMARC records.

= How does the plugin detect my SMTP plugin? =
It briefly runs the mail-configuration hooks (`phpmailer_init`) that other plugins register, on a temporary object that never sends anything, to see which ones switch WordPress to SMTP. This stays on your site: no data is sent to any external service. The result is saved for up to 12 hours, and the "Re-check" button refreshes it.

== Upgrade Notice ==

= 1.1.1 =
Adds DKIM and DMARC checks and smarter SPF validation. Removes links to a discontinued website.

= 1.1.0 =
The health report no longer sends an email each time it loads. Send a test email to see the "Basic Email Functionality" result. Requires PHP 7.4+.

== Changelog ==

= 1.1.1 =
* New: DKIM check (tries common selectors; shows "Not checked" if your provider uses a custom one).
* New: DMARC check, with a note when the policy is "none".
* New: SPF check detects your SMTP service (WP Mail SMTP, Post SMTP, FluentSMTP) and warns if it is missing from your SPF record.
* New: "Warning" and "Not checked" statuses, so uncertain results are no longer reported as failures.
* Fix: DNS names that don't exist are treated as "no record" instead of "DNS unavailable".
* Removed: links and call-to-action boxes pointing to a discontinued website.

= 1.1.0 =
* Fix: the report no longer sends emails to test@example.com on every page view; "Basic Email Functionality" now reflects your last test email, with the send time and any mail error.
* Fix: "Sender Address Check" now uses the effective From address (WooCommerce setting and `wp_mail_from` filter) instead of the admin email.
* Fix: SPF check shows "Not checked" when DNS lookups are unavailable instead of a false failure; flags multiple SPF records and records that authorize no sender.
* Fix: only a leading "www." is stripped when detecting the site domain.
* Fix: the "Health Check" link on the Plugins page now opens the dashboard.
* Fix: activation redirect no longer interrupts bulk activation.
* Fix: escaped remaining admin output and moved inline styles to the stylesheet; stylesheet is versioned with the plugin version.
* Dev: restructured into namespaced classes with one class per check; added the `ecehc_checks` filter. Requires PHP 7.4+.

= 1.0.2 =
* UI Enhancements

= 1.0.1 =
* Test plugin deployment automation.

= 1.0.0 =
* Initial release.