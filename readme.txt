=== Email Health Check & Log for WooCommerce – SPF, DKIM, DMARC & Alerts ===
Contributors: engahmeds3ed
Tags: woocommerce, email log, deliverability, dkim, dmarc
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Email log, SPF/DKIM/DMARC checks and failure alerts. Find out why WooCommerce order emails go to spam or never arrive.

== Description ==

**WooCommerce emails not sending, or going to spam? Find out why, and fix it.**

Email Health Check looks at how your WordPress or WooCommerce site sends email and tells you what is wrong in plain language: SPF, DKIM and DMARC problems, mail sent through plain PHP mail, a blacklisted server IP, order emails that are switched off or stuck behind unpaid orders, and more. It also keeps an email log of what your site sends. It is free, needs no account, and sends no email content or personal data to any service. The only outside lookup is an optional server IP blacklist check (see External services).

= Diagnose =

* **Email Health Report:** one page with a Pass, Warning or Fail status for each check. The page opens at once and the checks (including the DNS lookups) run in the background, so slow DNS never holds it up.
* **Getting-started checklist:** a short checklist for new installs (send a test email, fix the top failing check, look at the Email Log) that ticks itself off and can be hidden.
* **Sending test:** send a test email to your own address or to any inbox you want to try (for example Gmail or Outlook), with an optional subject. The result shows right away, with the error if it failed.
* **Copy support report:** a plain-text summary of your setup (versions, domains, check results, the detected mail provider, recent email counts and, if the blacklist check ran, your server IP address) to paste into a support ticket, with no passwords, message contents or full email addresses. Review it before posting it publicly.
* **SMTP / mail service:** warns when mail goes out through plain PHP mail, and names the plugin that handles your outgoing email when it finds one.
* **Sender address, SPF, DKIM and DMARC:** checked for the domain you actually send from, since that is what receivers verify.
* **Server IP blacklists:** when you send straight from your server (no SMTP service), your IP is looked up on the free DroneBL and PSBL lists.
* **WooCommerce email setup:** flags turned-off key emails, a missing new-order recipient, an invalid From address and outdated theme overrides of WooCommerce email templates. It only reads your settings.
* **Unpaid orders (WooCommerce):** warns when many orders are stuck in "Pending payment" or "Failed". WooCommerce sends no order emails for those, so the real problem may be your payment gateway. Only order counts are read.

= Email log and failure alerts =

* **Email Log and statistics:** every email your site sends (WooCommerce, other plugins and WordPress itself) with its source, WooCommerce email type, status and error. Filter by source, status and WooCommerce email type, and see accepted and failed counts for the last 24 hours and 7 days. Only a partly hidden recipient address is stored, never the message. You can shorten the retention, turn logging off or clear the log.
* **Failure alerts in wp-admin:** an "Email Health" dashboard widget and a Site Health test warn you when many emails fail, without needing email to work.
* **Site Health integration:** the same checks appear under Tools > Site Health.

= Fix =

* **"How to fix this" panels:** failing SPF, DKIM and DMARC checks show a copy-paste DNS record where your provider documents one, and a link to the provider's official setup guide.

= WP-CLI =

* `wp ehc check` runs all checks (add `--format=json` for scripts). It exits with status 1 when any check fails. The server IP blacklist check needs a web request, so from the command line it is skipped unless you set the IP with the `ecehc_server_ip` filter.
* `wp ehc log` lists recent logged emails (`--source`, `--status`, `--type`, `--limit`), and `wp ehc log --stats` shows accepted and failed counts for the last 24 hours and 7 days.

= Works with =

The plugin identifies the provider from the settings of WP Mail SMTP, FluentSMTP and Post SMTP, and gives setup guidance for SendGrid, Mailgun, Brevo, Postmark, Amazon SES, SparkPost, Mailjet, Elastic Email, Google Workspace and Microsoft 365. It also works with any other SMTP plugin, or with none.

WooCommerce is a trademark of Automattic Inc. This plugin is independent and is not affiliated with or endorsed by WooCommerce or Automattic.

== Installation ==

### Via WordPress Dashboard
1. Go to `Plugins > Add New` in your WordPress dashboard.
2. Search for "Email Health Check".
3. Click "Install Now" and then "Activate".

### Manual Installation
1. Download the plugin from the [WordPress.org Plugin Repository](https://wordpress.org/plugins/ecom-email-health-check/).
2. Unzip the file and upload the `ecom-email-health-check` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Why are my WooCommerce emails not sending, or going to spam? =
The usual causes, all of which the Health Report checks:

* the site sends through plain PHP mail instead of an SMTP service;
* the SPF, DKIM or DMARC records for your From address are missing or wrong;
* the From address does not match your domain;
* the server's IP address is on a blacklist;
* the WooCommerce email is switched off or has no recipient;
* the order is stuck in "Pending payment", which sends no order email.

For SPF, DKIM and DMARC the report shows how to fix it for common mail providers. The Email Log then shows whether your emails are being accepted or failing.

= What problem does this plugin solve? =
Many hosting providers have poor email delivery configurations that can silently fail, leaving you and your customers in the dark. This plugin diagnoses those issues so you can address them.

= What does the plugin check? =
It checks for common issues like email sending failures, mail sent through plain PHP mail instead of SMTP, sender address misconfigurations, missing or incomplete SPF, DKIM and DMARC records, and (for WooCommerce stores) many orders stuck in "Pending payment".

= Do I need an SMTP plugin? =
Not to use this plugin. But if the report warns that your site sends through plain PHP mail, an SMTP plugin or a transactional email service is the usual fix, because authenticated email is far less likely to land in spam.

= What does "Not checked" mean? =
The plugin could not reach a conclusion, so it does not call it a failure. The most common case is DKIM: the key can only be found under a few common selector names, and many providers use their own. Check your provider's DNS instructions, or an online DKIM checker, to be sure.

= Why does the log say "Accepted" and not "Delivered"? =
WordPress can only tell that your mail system took the email without an error. Whether it reached the inbox depends on the receiving server, which this plugin cannot see.

= Does it work without WooCommerce? =
Yes. The report, the email log and the alerts work on any WordPress site. The WooCommerce-specific parts (email types and the unpaid orders check) appear only when WooCommerce is active.

= How do I send a test WooCommerce email? =
Recent versions of WooCommerce have this built in: go to WooCommerce > Settings > Emails, pick an email and use its preview to send yourself a test copy. It uses your real From address and mail setup, and the Health Report has a button that opens that screen. While the email log is on, the test appears in the Email Log tab as a WooCommerce email.

= How does the plugin detect my SMTP plugin? =
It briefly runs the mail-configuration hooks (`phpmailer_init`) that other plugins register, on a temporary object that never sends anything, to see which ones switch WordPress to SMTP. This stays on your site: no data is sent to any external service. The result is saved for up to 12 hours, and the "Re-check" button refreshes it.

= Does the plugin store personal data? =
The Email Log keeps, for a few days (7 by default), the time of each email, which plugin sent it, its WooCommerce email type, whether it was accepted or failed, the recipient's email domain and a partly hidden address such as j***@example.com, and an error message with any addresses hidden. It never stores the message, its subject or a full email address. You can turn logging off, shorten the retention or clear the log in the Email Log tab. A suggestion for your privacy policy is added under Settings > Privacy.

= Does the email log slow down my site? =
Each email causes one small database insert. Entries are pruned daily and the log is capped, so it stays small.

= Can I help translate the plugin? =
Yes, and thank you. Translations are done by the community on translate.wordpress.org: https://translate.wordpress.org/projects/wp-plugins/ecom-email-health-check/ . The plugin's texts can be translated, except the plain-text support report and the WP-CLI output, which stay in English on purpose. A draft Arabic translation is kept in the plugin's GitHub repository; it still needs a native speaker to review it and a translation editor to import it.

= Where do I get support? =
Please use the plugin's support forum on WordPress.org: https://wordpress.org/support/plugin/ecom-email-health-check/

== Screenshots ==

1. The Health Report: every check with a clear Pass, Warning or Fail status.
2. A failing check shows a "How to fix this" panel with a copy-paste DNS record and a link to your mail provider's official guide.
3. The Email Log tab: accepted and failed counts for the last 24 hours and 7 days, broken down by source and WooCommerce email type.
4. The latest emails, filterable by source, status and WooCommerce email type.
5. The Email Health dashboard widget warns you inside wp-admin when many emails fail.
6. The same warning in Tools > Site Health, so a problem shows up without opening the plugin.

== External services ==

The "Server IP Blacklists" check sends DNS queries to the public blacklists DroneBL and PSBL. This happens only when your site does not send email through an SMTP plugin or mail service, and only when you open the plugin's report or Tools > Site Health. The answer is saved for about 12 hours (about 1 hour after a failed lookup; the Re-check button and a changed IP address also trigger a new lookup). The queries go through your server's own DNS resolver.

* What is sent: a DNS query for your web server's public IPv4 address (written in reverse, for example `4.3.2.1.dnsbl.dronebl.org`). The lists' operators can see that address and the address of your DNS resolver. No other data is sent.
* DroneBL: https://dronebl.org/ (terms: https://dronebl.org/docs/howtouse)
* PSBL: https://psbl.org/ (usage: https://psbl.org/howto)

Spamhaus is intentionally not queried, because its free service is limited to low-volume non-commercial use. Developers can change the lists with the `ecehc_dnsbl_zones` filter.

== Upgrade Notice ==

= 1.4.0 =
Fixes the SMTP / Mail Service check, which always passed since 1.3.0, so the server IP blacklist check runs again. Adds a WooCommerce email setup check, WP-CLI commands, a copy support report button, a getting-started checklist and WooCommerce HPOS compatibility.

= 1.3.0 =
Adds an Email Log tab with statistics and failure alerts, plus a server IP blacklist check. The log needs WordPress 5.9+, adds one small database table and stores only a partly hidden recipient, never the message. Turn it off or clear it any time.

= 1.2.0 =
Adds an SMTP / mail service check, a Site Health test, fix guidance for SPF, DKIM and DMARC, and an unpaid orders check. These checks now use your From address domain, and a different From domain is a warning, not a failure.

= 1.1.1 =
Adds DKIM and DMARC checks and smarter SPF validation. Removes links to a discontinued website.

= 1.1.0 =
The health report no longer sends an email each time it loads. Send a test email to see the "Basic Email Functionality" result. Requires PHP 7.4+.

== Changelog ==

= 1.4.0 =
* Fix: the "SMTP / Mail Service" check always passed since 1.3.0, because it counted the email log's own mail hook as a mail service. It now warns again when your site uses plain PHP mail, and the server IP blacklist check is no longer skipped by mistake.
* New: "WooCommerce Email Setup" check. Flags turned-off key emails (new order, processing, completed), a missing new-order recipient, an invalid From address and outdated theme overrides of WooCommerce email templates. It only reads your settings.
* New: "Copy support report" button with a preview: a plain-text summary of your setup to paste into a support ticket, with no passwords, message contents or full email addresses.
* New: getting-started checklist for newly activated sites (send a test email, fix the top failing check, look at the Email Log). It ticks itself off and can be hidden.
* New: WP-CLI commands `wp ehc check` (exits with status 1 when a check fails) and `wp ehc log` (with `--stats`).
* New: a link from the Health Report to WooCommerce's own email preview and "send a test email".
* New: a single, dismissible request for a WordPress.org review, shown only on this plugin's screens ("Maybe later" works at most twice).
* New: declares compatibility with WooCommerce High-Performance Order Storage and the Cart and Checkout blocks, and adds the WooCommerce version headers.
* Improved: readme with a clearer description, FAQ and screenshots.

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