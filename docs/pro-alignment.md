# Free and Pro alignment

Used by the `plan-features` skill.

## Repositories
- Free plugin (this repo): `codesir-co/ecom-email-health-check`, released on WordPress.org.
- Pro plugin: **not created yet**. When it exists, set its name here: `codesir-co/<pro-repo>`. Until then `plan-features` proposes Pro items but creates nothing there. Choosing the name and brand waits for the domain decision.

## Split rules
Free (earns installs and ratings, keeps the plugin useful on its own):
- Every diagnostic check and fix guidance, the email log with its basic filters and retention, failure alerts by admin email and the Site Health test, WP-CLI, the support report.
- Anything that is a bug, a compatibility fix or a security fix.

Pro (continuous monitoring and automation that a paying store would value):
- Scheduled monitoring with alerts to other channels (Slack, webhooks), longer log history, resend of failed emails, delivery tracking through provider webhooks, multisite and multi-store overview, DNS fixes that call a DNS provider's API.

Rules:
- Never paywall or remove something already shipped free.
- The free plugin never contains trial limits, locked features or code that only serves Pro (WordPress.org guideline on serviceable features). It may expose a documented filter or action that Pro uses.
- The Pro plugin needs the free plugin and extends it through those hooks; it is distributed from the vendor's own site, not WordPress.org.

## Issue format (both repos)
- **Problem** and **who benefits**.
- **Acceptance criteria**: a checklist of observable, testable outcomes.
- **Out of scope**.
- **Placement**: free, Pro or both, and why.
- **Evidence**: sources, without private tracker numbers.
- **Effort**: S, M or L; risks (privacy, external requests, WooCommerce versions).
- **Related**: cross-links (`codesir-co/<repo>#N`) and "blocked by".
Labels: `enhancement` or `bug`, and `roadmap` for planned work.

## Privacy
This repo is public. Installs, ratings and search positions stay in the private `.tracker/` folder and are never copied into issues or docs.
