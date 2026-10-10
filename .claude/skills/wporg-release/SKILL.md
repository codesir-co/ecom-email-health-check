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
3. Check `.distignore` excludes dev files; `php -l` every PHP file. Run the official Plugin Check on a copy built per `.distignore` (the `Checks` CI workflow does this; locally, with the Plugin Check plugin installed: `mkdir -p /tmp/x/ecom-email-health-check && rsync -a --exclude .git --exclude-from=.distignore ./ /tmp/x/ecom-email-health-check/` then `wp plugin check /tmp/x/ecom-email-health-check --slug=ecom-email-health-check`). It must report no errors.
4. Do the bump/changelog on a `release/<version>` branch from `develop` and PR it into `develop`. STOP and wait for the user to merge.
5. Open a PR `develop` → `trunk` titled `Release <version>`. After the user approves/merges it, confirm with the user before tagging.
6. On updated `trunk`: `git tag <version>` (tag equals Stable tag; earlier tags used a `v` prefix, newer ones don't, both deploy) and `git push origin <version>`. The GitHub Action `deploy-with-tag.yml` deploys to SVN; banners/icons come from `.wordpress-org/`.
7. Verify via the workflow run (`gh run list`, `gh run watch <id>`) and `https://api.wordpress.org/plugins/info/1.0/ecom-email-health-check.json` (the version must match; do not trust a green run alone). The workflow pins the 10up deploy action and tries it up to three times, cleaning the SVN working copy between attempts, because network errors (Subversion install, SVN server) can fail a run. If the run still fails, read `gh run view <id> --log-failed`: for a network error (timeouts, 5xx, `Could not resolve host`) re-run with `gh run rerun <id>` after a few minutes. A re-run is safe: if the earlier attempt had already published the tag, the action reports it as already published and exits successfully. Anything else needs a look before re-running.
8. Merging to trunk, tagging and pushing are outward-facing — never do them without explicit user approval.
