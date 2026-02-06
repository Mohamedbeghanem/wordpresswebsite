/* Deserialize the form data */
function bwfan_deserialize_obj( query ) {
	if ( '' === query ) {
		return '';
	}
	const setValue = function ( root, path, value ) {
		if ( path.length > 1 ) {
			const dir = path.shift();
			if ( typeof root[ dir ] === 'undefined' ) {
				root[ dir ] = '' === path[ 0 ] ? [] : {};
			}

			arguments.callee( root[ dir ], path, value );
		} else if ( root instanceof Array ) {
			root.push( value );
		} else {
			root[ path ] = value;
		}
	};
	const nvp = query.split( '&' );
	const data = {};
	for ( let i = 0; i < nvp.length; i++ ) {
		const pair = nvp[ i ].split( '=' );
		const name = decodeURIComponent( pair[ 0 ] );
		const value = decodeURIComponent( pair[ 1 ] );

		let path = name.match( /(^[^\[]+)(\[.*\]$)?/ );
		const first = path[ 1 ];
		if ( path[ 2 ] ) {
			//case of 'array[level1]' || 'array[level1][level2]'
			path = path[ 2 ].match( /(?=\[(.*)\]$)/ )[ 1 ].split( '][' );
		} else {
			//case of 'name'
			path = [];
		}
		path.unshift( first );

		setValue( data, path, value );
	}
	return data;
}

( function ( $ ) {
	$( document ).ready( function () {
		const adminSyncButton = $( '#bwfan-admin-resync-order' );
		adminSyncButton.on( 'click', function () {
			$( this ).addClass( 'loading' );
			$( this ).html( bwfanProObj.localize_text.loading );
			fetch(
				bwfanProObj.siteUrl +
					'/wp-json/' +
					bwfanProObj.apiNamespace +
					'/contact/' +
					bwfanProObj.contactId +
					'/resync-order',
				{
					credentials: 'include',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': wpApiSettings.nonce,
					},
				}
			)
				.then( ( response ) => response.json() )
				.then( function ( result ) {
					let message = '';
					if ( result.code == 200 ) {
						$( '#bwfan-editorder-message-section' ).addClass(
							'bwf-success'
						);
					} else {
						$( '#bwfan-editorder-message-section' ).addClass(
							'bwf-error'
						);
					}
					$( '#bwfan-admin-resync-order' )
						.html( bwfanProObj.localize_text.text )
						.removeClass( 'loading' );
					if ( result.hasOwnProperty( 'message' ) ) {
						message = result.message;
					}

					if (
						result.hasOwnProperty( 'result' ) &&
						result.result.hasOwnProperty( 'wc' )
					) {
						window.location.reload();
					}

					if ( message !== '' ) {
						$( '#bwfan-editorder-message-section' )
							.html( result.message )
							.show();
						setTimeout( function () {
							$( '#bwfan-editorder-message-section' )
								.hide()
								.removeClass( 'bwf-success bwf-error' );
						}, 3000 );
					}
				} );
		} );
	} );
} )( jQuery );
