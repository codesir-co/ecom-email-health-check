// Node check (no dependencies) for the "Fix this first" link in assets/js/admin.js.
// Run with: node tests/admin-fix-first.test.js
const fs = require( 'fs' );
const vm = require( 'vm' );
const path = require( 'path' );
const assert = require( 'assert' );

function run( row ) {
	const handlers = [];
	const link = { getAttribute: () => 'ecehc-check-x' };
	const document = {
		readyState: 'complete',
		addEventListener: ( type, fn ) => handlers.push( fn ),
		getElementById: ( id ) => ( 'ecehc-check-x' === id ? row : null ),
		querySelector: () => null,
	};
	const sandbox = { document, window: {}, navigator: {}, setTimeout };
	vm.runInNewContext( fs.readFileSync( path.join( __dirname, '../assets/js/admin.js' ), 'utf8' ), sandbox );
	const event = { target: { closest: ( sel ) => ( '[data-ecehc-open]' === sel ? link : null ) } };
	handlers.forEach( ( fn ) => fn( event ) );
}

// Row without a guidance panel: the row itself gets tabindex -1 and focus.
const plain = { attrs: {}, focused: false, querySelector: () => null, setAttribute( k, v ) { this.attrs[ k ] = v; }, focus() { this.focused = true; } };
run( plain );
assert.strictEqual( plain.attrs.tabindex, '-1' );
assert.ok( plain.focused );

// Row with a panel: the panel opens and its summary gets focus; the row is left alone.
const summary = { focused: false, focus() { this.focused = true; } };
const panel = { open: false, querySelector: () => summary };
const guided = { attrs: {}, querySelector: () => panel, setAttribute( k, v ) { this.attrs[ k ] = v; }, focus() { throw new Error( 'row focused' ); } };
run( guided );
assert.ok( panel.open && summary.focused );
assert.deepStrictEqual( guided.attrs, {} );

console.log( 'admin-fix-first: ok' );
