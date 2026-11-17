<?php
use CodeSir\EmailHealthCheck\Support\SupportReport;

// The support report: what is in the text and what is kept out of it.
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', rtrim( ABSPATH, '/' ) . '/wp-content' );
}

$data = array(
	'generated'        => '2026-01-02 03:04',
	'plugin_version'   => '1.2.3',
	'wp_version'       => '6.8',
	'php_version'      => '8.2.1',
	'wc_version'       => '9.1.0',
	'mail_domain'      => 'example.com',
	'site_domain'      => 'shop.example.com',
	'provider'         => 'Google Workspace',
	'handling_plugins' => array( 'WP Mail SMTP', 'Fluent SMTP' ),
	'log_stats'        => array(
		'last 24 hours' => array( 'sent' => 10, 'failed' => 2, 'rate' => 17 ),
		'last 7 days'   => array( 'sent' => 90, 'failed' => 0, 'rate' => 0 ),
	),
	// Not part of the report's input: must never appear in the text.
	'order_number'     => 'ORDER-98765',
	'customer_name'    => 'Priscilla Customerson',
);

$results = array(
	'spf'  => array( 'label' => 'SPF Record', 'status' => 'pass', 'message' => 'SPF found for example.com.' ),
	'mail' => array( 'label' => 'Basic Email Functionality', 'status' => 'fail', 'message' => 'Failed.' ),
);

// Exact text for known input (same layout as before the refactor).
$expected = "Email Health Check report\n"
	. "Generated: 2026-01-02 03:04 UTC\n"
	. "\n"
	. "Plugin 1.2.3 | WordPress 6.8 | PHP 8.2.1 | WooCommerce 9.1.0\n"
	. "Mail domain: example.com | Site domain: shop.example.com\n"
	. "Mail provider: Google Workspace\n"
	. "Outgoing email handled by: WP Mail SMTP, Fluent SMTP\n"
	. "\n"
	. "Checks:\n"
	. "- [PASS] SPF Record: SPF found for example.com.\n"
	. "- [FAIL] Basic Email Functionality: Failed.\n"
	. "\n"
	. "Email log, last 24 hours: 10 accepted, 2 failed (17% failure rate)\n"
	. "Email log, last 7 days: 90 accepted, 0 failed (0% failure rate)\n";
$text     = SupportReport::format( $data, $results );
ecehc_assert_same( $expected, $text, 'the report text has the documented layout, with uppercase statuses and a trailing newline' );
ecehc_assert_true( false === strpos( $text, 'ORDER-98765' ) && false === strpos( $text, 'Priscilla' ), 'values outside the documented input keys are never read' );

// Nothing known: placeholders, and no log lines without stats.
$bare = SupportReport::format(
	array(
		'plugin_version'   => '1',
		'wp_version'       => '6',
		'php_version'      => '8',
		'wc_version'       => null,
		'mail_domain'      => 'a.com',
		'site_domain'      => 'a.com',
		'provider'         => null,
		'handling_plugins' => array(),
		'log_stats'        => null,
	),
	array()
);
ecehc_assert_true( false !== strpos( $bare, 'WooCommerce not active' ), 'no WooCommerce is stated' );
ecehc_assert_true( false !== strpos( $bare, 'Mail provider: not detected' ), 'no provider is stated' );
ecehc_assert_true( false !== strpos( $bare, 'no plugin detected (plain PHP mail?)' ), 'no handling plugin is stated' );
ecehc_assert_true( false === strpos( $bare, 'Email log' ), 'no log lines when logging is off or has no stats' );
ecehc_assert_true( 1 === preg_match( '/^Generated: \d{4}-\d{2}-\d{2} \d{2}:\d{2} UTC$/m', $bare ), 'the time defaults to now (UTC)' );

// Messages: addresses masked, paths replaced, one line each.
$root   = rtrim( ABSPATH, '/' );
$result = array(
	'x' => array(
		'label'   => 'Test',
		'status'  => 'warning',
		'message' => "Error for john.smith@example.com in {$root}/wp-content/plugins/foo/bar.php\n- [PASS] Fake: all good\r\nand " . WP_CONTENT_DIR . '/uploads/x.log',
	),
);
$text   = SupportReport::format( $data, $result );
ecehc_assert_true( false === strpos( $text, 'john.smith@example.com' ), 'a full address in a message does not appear' );
ecehc_assert_true( false !== strpos( $text, 'j***@example.com' ), 'the address is partly hidden' );
ecehc_assert_true( false === strpos( $text, $root ), 'the site root path does not appear' );
ecehc_assert_true( false !== strpos( $text, '[content]/uploads/x.log' ), 'the content directory is replaced' );
ecehc_assert_true( false !== strpos( $text, '[content]/plugins/foo/bar.php' ), 'a path under the content directory is replaced by the content placeholder' );
ecehc_assert_same( 1, preg_match_all( '/^- \[/m', $text ), 'a newline in a message cannot start a second check line (one result gives one line)' );
ecehc_assert_true( false !== strpos( $text, ' - [PASS] Fake: all good and ' ), 'the newline became a space, so the fake line is inside the one message line' );

// Path redaction on its own.
ecehc_assert_same( '[site]/index.php failed', SupportReport::redact_paths( $root . '/index.php failed' ), 'the site root is replaced' );
ecehc_assert_same( 'in [site] only', SupportReport::redact_paths( 'in ' . $root . ' only' ), 'the root without a trailing slash is replaced' );
ecehc_assert_same( 'no paths here /var/other/file.php', SupportReport::redact_paths( 'no paths here /var/other/file.php' ), 'other paths are left alone (only the site and content directories are known)' );
ecehc_assert_same( 'see ' . $root . '2/x.php', SupportReport::redact_paths( 'see ' . $root . '2/x.php' ), 'a sibling directory that only shares the prefix is not changed' );
ecehc_assert_same( 'see ' . $root . '-backup/x', SupportReport::redact_paths( 'see ' . $root . '-backup/x' ), 'a dashed sibling directory is not changed' );
ecehc_assert_same( 'in [site].', SupportReport::redact_paths( 'in ' . $root . '.' ), 'a sentence-ending full stop does not stop the replacement' );

$labelled = SupportReport::format( $data, array( 'x' => array( 'label' => 'Check for john@example.com at ' . $root . '/f', 'status' => 'pass', 'message' => 'ok' ) ) );
ecehc_assert_true( false === strpos( $labelled, 'john@example.com' ) && false === strpos( $labelled, $root ), 'a label is masked and redacted like a message' );
