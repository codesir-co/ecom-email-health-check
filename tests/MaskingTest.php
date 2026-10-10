<?php
use CodeSir\EmailHealthCheck\Log\EmailLogger;

// Privacy: addresses are partly hidden, also inside error messages.
$m = EmailLogger::mask_recipient( 'john@example.com' );
ecehc_assert_same( 'j***@example.com', $m['masked'], 'one address is masked' );
ecehc_assert_same( 'example.com', $m['domain'], 'its domain is kept lower-case' );

$m = EmailLogger::mask_recipient( 'Jane Doe <Jane@Example.COM>, bob@test.org' );
ecehc_assert_same( 'J***@Example.COM +1', $m['masked'], 'name <address> form, extra recipients counted' );
ecehc_assert_same( 'example.com', $m['domain'], 'domain of the first recipient, lower-case' );

$m = EmailLogger::mask_recipient( array( 'ab@example.com', 'c@example.com' ) );
ecehc_assert_same( '***@example.com +1', $m['masked'], 'a 1-2 character name is hidden completely' );

$m = EmailLogger::mask_recipient( 'garbage' );
ecehc_assert_same( '', $m['masked'], 'a non-address gives nothing' );

ecehc_assert_same(
	'Invalid address: (to): j***@example.com',
	EmailLogger::mask_addresses( 'Invalid address: (to): john.smith@example.com' ),
	'an address in an error message is masked'
);
ecehc_assert_same(
	'SMTP Error: failed: j***@example.com: <550 5.1.1 user unknown>',
	EmailLogger::mask_addresses( 'SMTP Error: failed: john@example.com: <550 5.1.1 user unknown>' ),
	'server reply text around the address is kept'
);
ecehc_assert_same( 'nothing to hide here', EmailLogger::mask_addresses( 'nothing to hide here' ), 'text without addresses is unchanged' );
ecehc_assert_same(
	'Error: o***@example.com failed',
	EmailLogger::mask_addresses( "Error: o'brien@example.com failed" ),
	'an apostrophe in the local part is masked completely'
);
ecehc_assert_same(
	"user's mail 'a***@c.org' bounced",
	EmailLogger::mask_addresses( "user's mail 'a.b@c.org' bounced" ),
	'a quoted address is masked and the quotes are kept'
);
