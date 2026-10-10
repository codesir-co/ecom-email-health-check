# Email Health Check & Log for WooCommerce – SPF, DKIM, DMARC & Alerts

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/ecom-email-health-check.svg)](https://wordpress.org/plugins/ecom-email-health-check/)
[![License](https://img.shields.io/github/license/codesir-co/ecom-email-health-check)](LICENSE)

A free, simple tool to diagnose and test your eCommerce email delivery, ensuring your order confirmations and notifications always reach your customers.

## 🤔 The Problem

Are your eCommerce emails ending up in spam folders? Is your store failing to send critical order confirmations, shipping updates, or password reset emails? Many hosting providers have poor email delivery configurations that can silently fail, leaving you and your customers in the dark.

## ✨ The Solution

Email Health Check is a lightweight diagnostic tool that quickly identifies common email delivery issues on your WordPress site. It provides a clear, actionable report so you can stop guessing and start fixing the problem.

### Key Features:

* **Email Sending Test:** A one-click test to confirm if your site is even capable of sending emails.
* **Sender Address Check:** Verifies that your "From" email address is configured correctly to avoid being flagged as spam by mail clients.
* **SPF Record Validation:** Checks your domain's SPF record (single record, authorizes a sender) and warns if your SMTP service is missing from it.
* **DKIM and DMARC Checks:** Looks for a DKIM key and a DMARC policy.
* **Easy-to-Read Report:** Get a simple Pass/Fail report right in your WordPress dashboard, so you know exactly where the issues are.

### Screenshots

_Add screenshots here to show your plugin in action. Here are some ideas:_

1.  **Dashboard Report:** A screenshot of the plugin's main page showing a successful health report.
2.  **Failed Report:** A screenshot of the main page showing a failed report with the issues highlighted in red.
3.  **Test Email:** A screenshot of the admin notice after sending a successful test email.

## 🚀 Installation

### Via WordPress Dashboard

1.  Go to `Plugins > Add New` in your WordPress dashboard.
2.  Search for "Email Health Check".
3.  Click "Install Now" and then "Activate".

### Manual Installation

1.  Download the plugin from the [WordPress.org Plugin Repository](https://wordpress.org/plugins/ecom-email-health-check/).
2.  Unzip the file and upload the `ecom-email-health-check` folder to your `/wp-content/plugins/` directory.
3.  Activate the plugin through the 'Plugins' menu in WordPress.

## ⚙️ Usage

After activating the plugin, you will be redirected to the plugin's page. You can also find it at `WooCommerce > Email Health`.

From this page, you can:
* View your email health report.
* Run a one-click test to send a sample email to your admin address.

## 🤝 Contributing

We welcome bug reports, feature requests, and pull requests! Please open an issue on this repository to get started.

## 📝 License

This project is licensed under the GPL v2.0 or later. See the [LICENSE](LICENSE) file for details.