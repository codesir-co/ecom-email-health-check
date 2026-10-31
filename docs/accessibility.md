# Accessibility notes (issue #143)

This records what was reviewed on the plugin's admin screens, how, what changed and what was not checked. It is not a conformance claim: the plugin has not been audited against WCAG 2.2 or certified by anyone.

## Method
- Read every view in `views/`, `includes/Admin/DashboardWidget.php`, the notice output in `TestEmailHandler.php` and `RecheckHandler.php`, `assets/css/admin.css` and `assets/js/admin.js`, against the issue's criteria.
- Contrast ratios computed with the WCAG relative-luminance formula in a small throwaway Node script (not kept in the repo). Backgrounds used: white card `#ffffff`, striped table row `#f6f7f7`, WordPress admin body `#f0f0f1`.
- `php -l` on edited PHP, `php tests/run.php`, `node tests/admin-fix-first.test.js`, `node --check assets/js/admin.js`.

## Already fine (no change needed)
- Form controls: test email address and subject, the three log filters, the retention select and the log-enabled checkbox all have a `<label for>`, a wrapping label or a `screen-reader-text` label. The subject placeholder is only a hint next to a real label.
- "How to fix this" and "Preview the report" use native `<details>/<summary>`, so they are keyboard operable and expose their expanded state.
- Status text (Pass, Warning, Fail, Not checked, Accepted, Failed) was always written out next to the icon.
- The background report load already had a `role="status"` region and a `wp.a11y.speak` announcement of the result and "Fix this first".
- The getting-started checklist already hid its icons and added "(done)" / "(to do)" text; the tab nav has `aria-label` and `aria-current`.

## Changed
- Icons next to status words (`views/report-card.php`, `views/email-log.php`, dashboard widget) are wrapped in `aria-hidden="true"` so they are decorative.
- Health Report table: added a screen-reader caption, and the check name cell is now `<th scope="row">` so each status and message is tied to its check.
- Email Log: `scope="col"` on all header cells, a screen-reader caption on the log table, pagination wrapped in a labelled `<nav>`, with "Previous page" / "Next page" text for the arrow links.
- "Official setup guide" opens a new tab; it now says so in screen-reader text.
- Result notices printed after the test email, re-check and log settings are full page loads and were not announced. They now carry the class `ecehc-notice`, and `admin.js` speaks the first one through `wp.a11y.speak` (assertive for errors, polite otherwise). Focus is not moved and the notice stays as before.
- The Copy buttons now announce "Copied" through `wp.a11y.speak` (the visible label already changed, but a live announcement was missing).
- `admin.css`: added `:focus-visible` outlines (2px `#2271b1`) for the guidance summaries, the checklist and review-notice `button-link` buttons, the "Fix this first" link, the WooCommerce panel links and the log "Reset" link. No outlines were removed anywhere.
- `admin.css`: a missing closing brace after `.ecehc-checklist li > span[aria-hidden]` swallowed the next rule (`.ecehc-support-report`). The brace is added.

## Contrast
| Use | Colour | on white | on #f6f7f7 | on #f0f0f1 | Result |
|---|---|---|---|---|---|
| Pass text | #007017 | 6.31 | 5.88 | 5.54 | ok |
| Fail text (before) | #d63638 | 4.73 | 4.40 | 4.15 | failed on striped log rows |
| Fail text (after) | #b32d2e | 6.30 | 5.87 | 5.53 | ok |
| Not checked text | #8a5a00 | 5.93 | 5.52 | 5.20 | ok |
| Warning text | #996800 | 4.84 | 4.51 | 4.25 | ok on white and striped; only on the white panels in practice |
| Summary link | #2271b1 | 5.17 | 4.81 | 4.54 | ok |
| Record meta, done items | #50575e | 7.33 | 6.83 | 6.43 | ok |
| Fix-first border, warning (before) | #dba617 | 2.22 | | | below 3:1 |
| Fix-first border, warning (after) | #996800 | 4.84 | | | ok |
| Fix-first border, ok | #00a32a | 3.35 | | | ok (3:1 non-text) |
| Fix-first border, fail | #d63638 | 4.73 | | | ok (3:1 non-text) |
| Fix-first border, not checked | #8a5a00 | 5.93 | | | ok |
| Woo panel border | #2271b1 | 5.17 | | | ok |

The one text failure was the Fail colour on the striped log table (4.40, needs 4.5), fixed by `#b32d2e`, which is also used on the dashboard widget and cards. The fix-first borders are an accent only; the state is also given in words.

`#ecehc-cta-card` (`border-left` `#1e87f0`, 3.63 on white) has no matching element in the plugin and was left as it is.

## Not checked
- No screen reader (NVDA, JAWS, VoiceOver, Orca) was run, so the announcements above are by code reading only.
- No axe, WAVE or Lighthouse run, and no browser exercise of the screens: the keyboard order, the focus outlines and the layout were not seen on a running site.
- Colours owned by WordPress core (notice backgrounds and text, `.button`, `.description` grey, `nav-tab`) were not measured.
- Zoom / reflow at 200 to 400 percent and the narrow-screen layout were not tested.
- Text spacing, target size (2.5.8) and the WooCommerce settings screens (not ours) were not checked.
- Dashboard widget markup was reviewed only in code.
