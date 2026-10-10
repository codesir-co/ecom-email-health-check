# Support playbook

How we answer on the WordPress.org support forum and in GitHub issues. Not shipped in the
plugin zip (`/docs` is in `.distignore`).

## Goals

- Every thread gets a first answer within **48 hours** (weekends included on a best-effort basis).
- Threads that are solved are **marked resolved**, so people can see what already has an answer.
- Be genuinely helpful first. Never paste links just to promote the plugin.

## Weekly routine (about one hour)

1. Open the plugin's forum feed: <https://wordpress.org/support/plugin/ecom-email-health-check/feed/>
   and answer anything new or unanswered.
2. Look at open threads elsewhere on the forum, using these searches: "woocommerce emails not sending",
   "order emails going to spam", "spf dkim dmarc", "email log". Only join a thread where the person is
   **asking for a tool or a way to diagnose or log emails**. Put the real answer first (the cause and the
   fix, which usually has nothing to do with our plugin), mention the plugin at most once and secondarily,
   and say that you are its author. Never post link-only replies or the same text twice. When in doubt, do
   not reply: unsolicited mentions are removed by the forum moderators as promotion.
3. Check GitHub issues for the repository.
4. Record the counts (threads, resolved, response time) in the private tracker notes, not in the public
   repository (see issue #93).

## First reply template

> Thanks for the report. To look into it, could you click **Copy support report** on the plugin's Health
> Report page and paste the text here? It has your versions, your site and mail domains, the check results
> and the detected mail setup, and, if the blacklist check ran, your server IP address. It has no passwords,
> message contents or full email addresses (addresses are partly hidden). Please remove anything you would
> rather not post publicly.

Always ask for: WordPress, PHP and WooCommerce versions, which SMTP plugin or mail service is used (if any),
and what the Email Log shows for the affected email.

## Canned answers (adapt, do not paste blindly)

**"Accepted" in the log, but the customer never got it.** "Accepted" means WordPress handed the email to
your mail system without an error; it cannot see what the receiving server did. Check the spam folder, then
the SPF, DKIM and DMARC results in the Health Report for your From domain. If you send through an SMTP service,
its own dashboard shows delivery and bounces.

**SPF fails or says no sending service is authorised.** A domain may have only one SPF record. Add your
sending service's include to the existing record (the "How to fix this" panel shows the record for common
providers). Providers such as Postmark, SendGrid, Brevo, Amazon SES and SparkPost authenticate through their
own return-path records, so they do not need an include in the root record.

**DKIM says "Not checked".** The plugin can only try common selector names; many providers use their own.
That is not a failure. Take the DKIM record from your provider's dashboard and check it with an online
DKIM checker.

**DMARC policy is "none".** That is monitoring only and is a fine start. Move to quarantine or reject once SPF or DKIM
passes for all the services that send as your domain and your reports look clean.

**Test email fails with "Could not instantiate mail function".** The server cannot send mail (the host blocks
or does not provide it). Use an SMTP plugin or a transactional email service; the Health Report names the plugin
it detects.

**The plugin says plain PHP mail, but I use an SMTP plugin.** The plugin finds SMTP by briefly running the
mail hooks that other plugins register, and by reading WP Mail SMTP, Post SMTP and FluentSMTP settings. Click
**Re-check** (the result is saved for up to 12 hours) and send the support report if it is still wrong.

**Server IP blacklist check says skipped.** It only runs when the site sends straight from the server (no
SMTP plugin or mail service), because otherwise the server IP is not what receivers see. From WP-CLI it is
skipped unless the IP is set with the `ecehc_server_ip` filter.

**Unpaid orders warning.** WooCommerce sends no order emails for orders in "Pending payment" or "Failed",
so many of them usually mean a payment gateway problem, not an email problem.

**The report shows a spinner that never finishes, or "The checks could not be loaded".** The checks load in the
background. If it says "The checks could not be loaded", use the "Run them again on the server" link. If the
spinner just keeps spinning, add `&ecehc_sync=1` to the page address
(`wp-admin/admin.php?page=ecom-dashboard&ecehc_sync=1`) to run the checks while the page loads, and send the
browser console error.

**Does the log store personal data?** Only a partly hidden recipient address (`j***@example.com`), its domain, the
source, the status and an error with addresses hidden, for 7 days by default. Never the message or subject. It can be turned
off, shortened or cleared in the Email Log tab.

## Closing a thread

When it is solved: say what fixed it and mark the thread resolved. Do not ask for reviews in the forum (the
plugin shows its own single, dismissible request, and asking only people who are happy is selective). Never offer
anything in exchange for a review.

## Do not

- Promise delivery results (the plugin diagnoses, it does not deliver email).
- Ask for passwords, API keys or full email addresses.
- Link to the discontinued MailSir site, or to any promotional external domain without UTM parameters (project rule; links to wordpress.org and to documentation are fine).
