=== eCommerce Email Health Check ===
Contributors: engahmeds3ed
Tags: e-commerce, email, woocommerce, smtp, deliverability
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free, simple tool to diagnose and test your eCommerce email delivery, ensuring orders and notifications reach customers.

== Description ==

Stop worrying about lost eCommerce emails! This free, simple plugin helps you diagnose your store's email delivery health in minutes. It runs a series of checks to identify common issues that cause emails to end up in spam or fail to send altogether.

**Key Features:**

* **Email Sending Test:** A one-click test to confirm if your site can send emails.
* **Sender Address Check:** Verifies that your "From" address is configured correctly to avoid being flagged as spam, and runs the SPF, DKIM and DMARC checks for the domain you actually send from.
* **SPF Record Validation:** Checks that your domain has a single, valid SPF record, and warns if it doesn't include your SMTP service (WP Mail SMTP, Post SMTP and FluentSMTP are detected).
* **DKIM and DMARC Checks:** Looks for a DKIM key and a DMARC policy, which Gmail and Yahoo expect for authenticated email.
* **SMTP / Mail Service Check:** Warns when your site seems to send email through plain PHP mail, and names the plugin that handles your outgoing email when it finds one.
* **Fix Guidance:** Failing SPF, DKIM and DMARC checks show a "How to fix this" panel with a copy-paste record where one is documented, and a link to your mail provider's official setup guide.
* **Unpaid Orders Check (WooCommerce):** Warns when many recent orders are stuck in "Pending payment". WooCommerce sends no order emails for those, so the real problem may be your payment gateway. Only order counts are read.
* **Server IP Blacklist Check:** Looks up your web server's IP address on free public blacklists (DroneBL, PSBL) when your site sends mail directly instead of through an SMTP service.
* **Email Log and Statistics:** A new Email Log tab lists the latest emails your site sends (WooCommerce, other plugins and WordPress), filterable by source, status and WooCommerce email type, with accepted/failed counts for the last 24 hours and 7 days. Only a partly hidden recipient address is stored, never the message, and entries are deleted after 7 days. You can shorten the retention, turn logging off or clear the log from the same tab.
* **Failure Alerts in wp-admin:** A dashboard widget and a Site Health test warn you when many of your recent emails fail, without needing email to work.
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
It checks for common issues like email sending failures, mail sent through plain PHP mail instead of SMTP, sender address misconfigurations, missing or incomplete SPF, DKIM and DMARC records, and (for WooCommerce stores) many orders stuck in "Pending payment".

= How does the plugin detect my SMTP plugin? =
It briefly runs the mail-configuration hooks (`phpmailer_init`) that other plugins register, on a temporary object that never sends anything, to see which ones switch WordPress to SMTP. This stays on your site: no data is sent to any external service. The result is saved for up to 12 hours, and the "Re-check" button refreshes it.

== External services ==

The "Server IP Blacklists" check sends DNS queries to the public blacklists DroneBL and PSBL. This happens only when your site does not send email through an SMTP plugin or mail service, and only when you open the plugin's report or Tools > Site Health. The answer is saved for about 12 hours (about 1 hour after a failed lookup; the Re-check button and a changed IP address also trigger a new lookup). The queries go through your server's own DNS resolver.

* What is sent: a DNS query for your web server's public IPv4 address (written in reverse, for example `4.3.2.1.dnsbl.dronebl.org`). The lists' operators can see that address and the address of your DNS resolver. No other data is sent.
* DroneBL: https://dronebl.org/ (terms: https://dronebl.org/docs/howtouse)
* PSBL: https://psbl.org/ (usage: https://psbl.org/howto)

Spamhaus is intentionally not queried, because its free service is limited to low-volume non-commercial use. Developers can change the lists with the `ecehc_dnsbl_zones` filter.

= Does the plugin store personal data? =
The Email Log keeps, for a few days (7 by default), the time of each email, which plugin sent it, its WooCommerce email type, whether it was accepted or failed, the recipient's email domain and a partly hidden address such as j***@example.com, and an error message with any addresses hidden. It never stores the message, its subject or a full email address. You can turn logging off, shorten the retention or clear the log in the Email Log tab. A suggestion for your privacy policy is added under Settings > Privacy.

= Does the email log slow down my site? =
Each email causes one small database insert. Entries are pruned daily and the log is capped, so it stays small.

== Upgrade Notice ==

= 1.3.0 =
Adds an Email Log tab with statistics and failure alerts, plus a server IP blacklist check. The log needs WordPress 5.9 or newer and creates one small database table; it stores only a partly hidden recipient address, never the message, and you can turn it off, shorten it or clear it in the Email Log tab.

= 1.2.0 =
Adds an SMTP / mail service check, a Site Health test, fix guidance for SPF, DKIM and DMARC, and an unpaid orders check for WooCommerce. Fixes a misleading SPF warning for Postmark, SendGrid, Brevo, Amazon SES and SparkPost, and the DNS checks now use the domain of your From address. A different From domain is now a warning, not a failure.

= 1.1.1 =
Adds DKIM and DMARC checks and smarter SPF validation. Removes links to a discontinued website.

= 1.1.0 =
The health report no longer sends an email each time it loads. Send a test email to see the "Basic Email Functionality" result. Requires PHP 7.4+.

== Changelog ==

= 1.3.0 =
* New: "Email Log" tab. Records every email your site sends (WooCommerce, other plugins and WordPress itself) with its source, WooCommerce email type, status (accepted or failed) and error, and lets you filter by source, status and WooCommerce email type. Needs WordPress 5.9+. It stores a partly hidden recipient address and never the message, subject or full address, in a new table `{prefix}ecehc_email_log`.
* New: email statistics for the last 24 hours and 7 days: accepted and failed counts, failure rate, a breakdown by source and by WooCommerce email type, and the last failure.
* New: log settings in the Email Log tab: turn logging off, keep entries for 1, 3 or 7 days (7 by default), or clear the log.
* New: failure alerts inside wp-admin: an "Email Health" dashboard widget and a "Recent email failures" Site Health test, with no email needed. Thresholds can be changed with the `ecehc_alert_min_failures` and `ecehc_alert_min_rate` filters.
* New: "Server IP Blacklists" check. When your site sends mail directly (no SMTP plugin or mail service), it looks your server's IP up on the free DroneBL and PSBL blacklists. See "External services" for exactly what is sent.
* Dev: new hooks `ecehc_enable_email_log`, `ecehc_log_row`, `ecehc_email_logged`, `ecehc_log_retention_days`, `ecehc_log_max_rows`, `ecehc_dnsbl_zones` and `ecehc_server_ip`.

= 1.2.0 =
* New: "SMTP / Mail Service" check. Warns when your site seems to send email through plain PHP mail, and names the plugin that handles your outgoing email. The detection result is saved for up to 12 hours; use the new "Re-check" button to refresh it.
* New: the email checks appear as a test on Tools > Site Health.
* New: failing SPF, DKIM and DMARC checks show a "How to fix this" panel with a copy-paste record where one is documented, and a link to your mail provider's official setup guide (Mailgun, Mailjet, Elastic Email, Google, Microsoft 365, SendGrid, Brevo, Postmark, Amazon SES, SparkPost).
* New: "Unpaid Orders" check for WooCommerce stores. Warns when many recent orders are stuck in "Pending payment" or "Failed", since WooCommerce sends no order emails for those. Only order counts are read.
* Change: SPF, DKIM and DMARC are now checked for the domain of your From address instead of always the site domain. The report says which domain was checked.
* Change: "Sender Address Check" shows a warning, not a failure, when the From domain differs from the site domain, and a failure when it is a public mailbox such as gmail.com.
* Fix: no more "your SPF record does not include X" warning for providers that don't use a root-domain include (Postmark, SendGrid, Brevo, Amazon SES, SparkPost).
* Fix: a domain with no TXT records is reported as "No SPF record" instead of "DNS lookup failed".

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