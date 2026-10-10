<?php
use CodeSir\EmailHealthCheck\Support\Domain;

ecehc_assert_same( 'shop', Domain::email_domain( 'x@shop' ), 'domain part of an address' );
ecehc_assert_true( Domain::are_related( 'example.com', 'example.com' ), 'the same domain is related' );
ecehc_assert_true( Domain::are_related( 'mail.example.com', 'example.com' ), 'a subdomain is related to its parent' );
ecehc_assert_true( Domain::are_related( 'example.com', 'shop.example.com' ), 'a parent is related to its subdomain' );
ecehc_assert_true( Domain::are_related( 'Shop.Example.COM', 'example.com' ), 'case is ignored' );
ecehc_assert_same( false, Domain::are_related( 'notexample.com', 'example.com' ), 'a longer name that merely ends the same is NOT related' );
ecehc_assert_same( false, Domain::are_related( 'example.org', 'example.com' ), 'different domains are not related' );
ecehc_assert_true( Domain::is_free_mailbox( 'gmail.com' ), 'gmail.com is a public mailbox' );
ecehc_assert_true( Domain::is_free_mailbox( 'GMAIL.com' ), 'case is ignored for public mailboxes' );
ecehc_assert_same( false, Domain::is_free_mailbox( 'mystore.com' ), 'a store domain is not a public mailbox' );
