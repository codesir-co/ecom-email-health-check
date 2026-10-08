---
name: wporg-release
description: Prepare and ship a new release of this plugin to WordPress.org (version bump, readme changelog, tag). Use when asked to release, bump the version, or deploy.
disable-model-invocation: true
---

# Release to WordPress.org

1. Pick the new version (semver). Update ALL of:
   - `Version:` in `ecom-email-health-check.php`
   - `Stable tag:` in `readme.txt`, plus a `== Changelog ==` and `== Upgrade Notice ==` entry
   - `Tested up to:` if a new WP version is out
   - the `ECEHC_VERSION` constant in the main file
2. `grep -rn "<old version>"` to confirm no stale references.
3. Check `.distignore` excludes dev files; `php -l` every PHP file.
4. Commit (`update the version`-style message is fine), then STOP and confirm with the user before tagging/pushing.
5. `git tag <version>` (no `v` prefix; the workflow matches any tag, and the tag should equal Stable tag) and `git push origin trunk --tags`. The GitHub Action `deploy-with-tag.yml` then deploys to SVN; banners/icons come from `.wordpress-org/`.
6. Tagging and pushing are outward-facing — never do them without explicit user approval.
