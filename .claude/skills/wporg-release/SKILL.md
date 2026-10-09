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
4. Do the bump/changelog on a `release/<version>` branch from `develop` and PR it into `develop`. STOP and wait for the user to merge.
5. Open a PR `develop` → `trunk` titled `Release <version>`. After the user approves/merges it, confirm with the user before tagging.
6. On updated `trunk`: `git tag <version>` (tag equals Stable tag; earlier tags used a `v` prefix, newer ones don't, both deploy) and `git push origin <version>`. The GitHub Action `deploy-with-tag.yml` deploys to SVN; banners/icons come from `.wordpress-org/`.
7. Verify via the workflow run (`gh run list`, `gh run watch <id>`) and `https://api.wordpress.org/plugins/info/1.0/ecom-email-health-check.json` (the version must match). The workflow retries the deploy action up to three times because Docker Hub occasionally answers the image build with a 504. If the run still fails, read `gh run view <id> --log-failed`: a registry error (`failed to authorize`, `504`) is transient, so re-run with `gh run rerun <id>` after a few minutes; anything else needs a look before re-running.
8. Merging to trunk, tagging and pushing are outward-facing — never do them without explicit user approval.
