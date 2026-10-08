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
}() );
