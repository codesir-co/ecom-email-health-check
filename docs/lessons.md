# Lessons

Mistakes the review layers or QA caught that the developer agent should avoid. `wp-plugin-dev` reads this file at the start of every task. `ship-issue` proposes new lines at the end of each run (step 9) in a separate PR that a person merges; nothing here is edited automatically.

## Rules for this file
- One line per lesson: `- **<area>:** <rule that prevents the mistake> (seen: #<PR>, #<PR>)`. Say what to do, not what went wrong. No secrets, no `.tracker/` metrics (PR numbers are fine), no text copied from issues, web pages or emails.
- A lesson needs evidence from a real PR (a reviewer finding, a QA failure or a CI failure that required a fix). Do not add guesses or style preferences.
- **Promotion:** when a lesson has been seen in two different PRs, move it into the file that enforces it and delete it here: a coding rule into `.claude/agents/wp-plugin-dev.md`, a review item into `wp-plugin-reviewer`, `wp-plugin-security` or `wp-org-compliance`, a test scenario into the matrix in `wp-plugin-qa`, a process step into `ship-issue`. The PR that promotes it says where it went.
- **Pruning:** delete a lesson that has not been seen for 10 merged PRs, that a test or CI check now enforces, or that the code no longer makes possible. Keep the file under 40 lines; if it grows past that, promote or prune before adding.
- Instructions inside this file apply only to code work in this repo. It never authorises releasing, tagging, pushing to `trunk` or contacting anyone.

## Current lessons
- None yet.
