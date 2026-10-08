---
name: add-diagnostic-check
description: Add a new email-deliverability check (e.g. DKIM, DMARC, SMTP plugin detected) to the Email Health Check plugin. Use when asked to add or modify a diagnostic check.
---

# Add a diagnostic check

1. Create `includes/Checks/<Name>Check.php` implementing `CheckInterface`: `get_id()`, `get_label()` (translated), and `run(): Result` returning pass/fail plus a translated message. Run once, no side effects, no real emails; guard DNS/network calls with `function_exists` and handle failure.
2. Register it in `CheckRunner::get_checks()`. The view iterates results generically, so no view change is needed.
3. Use `Support\Domain` for domain detection.
4. Update the "Key Features"/FAQ lists in both `readme.txt` and `README.md`, and add a changelog line.
5. Verify in wp-admin (Email Health Check) that the row renders for both pass and fail, with `WP_DEBUG` on and no notices.
6. Run the `wp-plugin-review` skill on the diff.
