# Extending Email Health Check

The hooks and functions listed here are the plugin's public API for add-ons and other developers. Everything else (classes, function names, the HTML of the admin screens, option and table internals) is internal and can change in any release.

## Stability promise

- A hook or function listed here keeps its name, its arguments and the keys of the arrays it passes or returns. For `ecehc_run_checks()` this means it takes no arguments, returns an array, and keeps the same keys as `ecehc_checks_completed`. Existing arguments and keys are not removed, renamed or changed in meaning within the same major version.
- New keys can be added to the arrays passed to hooks, and new values can appear in a list of allowed values (for example a new check status). Read the keys you need, and ignore keys you do not know.
- A hook is removed or changed incompatibly only in a major version (for example 2.0), and its deprecation is announced in the changelog at least one release before.
- Hooks and functions not listed here are internal.
- The plugin sends nothing anywhere because of these hooks. What a callback does with the data is the callback's responsibility. The data can include domains and the server IP address, so treat it as private.

## Functions

### `ecehc_run_checks()`

Runs the full set of checks and returns the results.

```php
$results = ecehc_run_checks(); // array
```

It does exactly what the Health Report does: the `ecehc_checks` filter applies and `ecehc_checks_completed` fires. The return value is the same array that action receives (see below for its shape). It echoes nothing, sends no headers and needs no admin context, so it works in WP-Cron and WP-CLI. Wrap calls in `function_exists( 'ecehc_run_checks' )` if your add-on can load without this plugin.

Notes:

- It performs DNS lookups through the server's resolver, like the report, and can take several seconds. Schedule it (for example with WP-Cron); do not call it on page loads.
- The function itself stores no results and sends nothing, but individual checks cache some lookups in transients (for example the blacklist lookup for up to 12 hours), so a very frequent schedule sees cached data. Keep the results yourself if you need history.
- Callbacks on `ecehc_checks_completed` run on every call.

## Actions

### `ecehc_checks_completed`

Fires after every run of the full set of checks: the Health Report (including its background request and the `?ecehc_sync=1` page), `wp ehc check`, the Site Health test and `ecehc_run_checks()`.

```php
do_action( 'ecehc_checks_completed', array $results );
```

`$results` has one entry per check, keyed by check id (`spf_record`, `dkim_record`, `smtp_configured`, ... plus any id added with `ecehc_checks`), in the order the checks ran:

| Key | Type | Meaning |
| --- | --- | --- |
| `label` | string | Translated, human-readable check name. |
| `status` | string | `pass`, `warning`, `fail` or `unknown` (shown as "Not checked"). |
| `message` | string | Plain-language explanation, translated. |
| `guidance` | array or null | Fix help for failing or warning checks when a provider is known; otherwise null. |

Notes:

- It runs every time the checks run, which can be several times a day when people open the report. A callback that needs a schedule should keep its own timestamp.
- The array is passed by value: changing it has no effect on what the plugin shows.
- It runs while the report loads, so keep callbacks fast.

```php
add_action(
	'ecehc_checks_completed',
	function ( array $results ) {
		foreach ( $results as $id => $result ) {
			if ( 'fail' === $result['status'] ) {
				error_log( 'Email check failed: ' . $id );
			}
		}
	}
);
```

### `ecehc_email_logged`

Fires after an email was written to the email log (not when logging is off, and not when the write failed).

```php
do_action( 'ecehc_email_logged', array $row );
```

`$row` is exactly what was stored (after `ecehc_log_row`), with these keys: `created_at` (UTC `Y-m-d H:i:s`), `status` (`sent` or `failed`), `source_key`, `source_label`, `email_type`, `email_type_label`, `recipient` (partly hidden address), `recipient_domain` and `error` (addresses hidden). The subject and message are never stored or passed.

## Filters

| Filter | Arguments and return | Purpose |
| --- | --- | --- |
| `ecehc_checks` | `CheckInterface[] $checks` returns the same | Add, remove or reorder checks. Entries that do not implement `CheckInterface` are dropped. A check id must be unique. |
| `ecehc_mail_provider` | `array\|null $provider` (`name` string, `spf` string[]) returns the same | Tell the plugin which mail provider you send through. |
| `ecehc_free_mailbox_domains` | `string[] $domains` | Public mailbox domains that cannot be authenticated. |
| `ecehc_dkim_selectors` | `string[] $selectors` | DKIM selector names that are tried. |
| `ecehc_dnsbl_zones` | `array<string,string> $zones` (zone => name) | Blacklist zones that are queried. Only add lists whose terms allow queries from any site. |
| `ecehc_server_ip` | `string $ip` | IPv4 address the blacklist check looks up. |
| `ecehc_pending_orders_minimum` | `int $minimum` (default 5) | Unpaid orders needed for the WooCommerce warning. |
| `ecehc_enable_email_log` | `bool $enabled` | Switch the email log feature on or off for the whole site. |
| `ecehc_log_row` | `array $row` (same keys as `ecehc_email_logged`) | Change a row before it is written. Unknown keys are dropped. |
| `ecehc_log_retention_days` | `int $days` | Days of log rows kept. |
| `ecehc_log_max_rows` | `int $rows` (default 5000, minimum 100) | Maximum rows kept. |
| `ecehc_alert_min_failures` | `int $failures` (default 3) | Failed emails in 24 hours that trigger the alert. |
| `ecehc_alert_min_rate` | `int $percent` (default 10) | Failure rate in 24 hours that triggers the alert. |

## Writing a check

A check is a class that implements `CodeSir\EmailHealthCheck\Checks\CheckInterface` (`get_id()`, `get_label()` and `run()`) and returns a `Result`: `new Result( true, $message )` for a pass, `new Result( false, $message )` for a failure, `Result::warning( $message )` or `Result::unknown( $message )`. Add it with the `ecehc_checks` filter. `run()` is called once per run, so do slow work carefully.

## Tests

`php tests/run.php` covers `ecehc_run_checks()` (`tests/RunChecksApiTest.php`), `ecehc_checks_completed` (`tests/CheckRunnerTest.php`) and the shape of `ecehc_email_logged` and `ecehc_log_row` (`tests/EmailLogHooksTest.php`). Add a test when you add a hook.
