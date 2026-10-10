( function () {
	'use strict';

	/**
	 * Copy text with a temporary textarea, for browsers or contexts where the
	 * async clipboard API is unavailable or refuses (e.g. no focus or HTTP).
	 *
	 * @param {string} value Text to copy.
	 * @return {boolean} Whether the copy succeeded.
	 */
	function legacyCopy( value ) {
		var field = document.createElement( 'textarea' );
		var copied = false;

		field.value = value;
		field.setAttribute( 'readonly', '' );
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.appendChild( field );
		field.select();

		try {
			copied = document.execCommand( 'copy' );
		} catch ( e ) {
			copied = false;
		}

		document.body.removeChild( field );
		return copied;
	}

	// Health Report: the checks run in the background and fill the page when ready.
	function loadReport() {
		var placeholder = document.getElementById( 'ecehc-main-report' );

		if ( ! placeholder || ! placeholder.hasAttribute( 'data-ecehc-ajax' ) ) {
			return;
		}

		var syncUrl = placeholder.getAttribute( 'data-sync-url' );

		// Browsers without fetch or URLSearchParams (or without ajaxurl) get the page with the checks run on the server.
		if ( ! window.fetch || ! window.URLSearchParams || typeof window.ajaxurl === 'undefined' ) {
			if ( syncUrl ) {
				window.location.replace( syncUrl );
			}
			return;
		}

		var body = new URLSearchParams();
		body.append( 'action', 'ecehc_load_report' );
		body.append( 'nonce', placeholder.getAttribute( 'data-nonce' ) );

		var fail = function () {
			var status = placeholder.querySelector( '.ecehc-report-status' );
			if ( ! status ) {
				return;
			}

			status.textContent = placeholder.getAttribute( 'data-error' ) + ' ';

			if ( syncUrl ) {
				var link = document.createElement( 'a' );
				link.href = syncUrl;
				link.textContent = placeholder.getAttribute( 'data-retry' );
				status.appendChild( link );
			}
		};

		window.fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.success ) {
					fail();
					return;
				}

				var done = placeholder.getAttribute( 'data-done' );
				var slot = document.getElementById( 'ecehc-checklist-slot' );
				if ( slot ) {
					slot.innerHTML = data.data.checklist;
				}
				placeholder.outerHTML = data.data.report;

				// Tell screen reader users that the results arrived, and honour a #hash the page was opened with.
				if ( window.wp && window.wp.a11y && window.wp.a11y.speak && done ) {
					window.wp.a11y.speak( done );
				}
				if ( window.location.hash ) {
					var target = document.getElementById( window.location.hash.substring( 1 ) );
					if ( target && target.scrollIntoView ) {
						target.scrollIntoView();
					}
				}
			} )
			.catch( fail );
	}


	// Ask before a destructive action (data-ecehc-confirm).
	document.addEventListener( 'click', function ( event ) {
		var guarded = event.target.closest( '[data-ecehc-confirm]' );

		if ( guarded && ! window.confirm( guarded.getAttribute( 'data-ecehc-confirm' ) ) ) {
			event.preventDefault();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ecehc-copy' );
		if ( ! button ) {
			return;
		}

		var value = button.getAttribute( 'data-ecehc-copy' );
		var done = function () {
			if ( ! button.hasAttribute( 'data-label' ) ) {
				button.setAttribute( 'data-label', button.textContent );
			}
			var label = button.getAttribute( 'data-label' );
			button.textContent = button.getAttribute( 'data-copied' );
			setTimeout( function () {
				button.textContent = label;
			}, 1500 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( value ).then( done, function () {
				if ( legacyCopy( value ) ) {
					done();
				}
			} );
			return;
		}

		if ( legacyCopy( value ) ) {
			done();
		}
	} );

	// Last, so a problem here can never stop the handlers above from being registered.
	try {
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', loadReport );
		} else {
			loadReport();
		}
	} catch ( error ) {
		window.console && window.console.error && window.console.error( error );
	}
}() );
