document.addEventListener( 'DOMContentLoaded', () => {
	const btn = document.querySelector( '.beastfeedbacks-export-btn' );
	if ( ! btn ) return;

	const { endpoint, action, nonce } = btn.dataset;

	addLoadEvent( function () {
		btn.onclick = () => {
			btn.disabled = true;
			jQuery( function ( $ ) {
				$.post(
					endpoint,
					{
						action: action,
						_wpnonce: nonce,
					},
					function ( response, status, xhr ) {
						const blob = new Blob( [ response ], {
							type: 'application/octetstream',
						} );

						const a = document.createElement( 'a' );
						a.href = window.URL.createObjectURL( blob );

						var contentDispositionHeader = xhr.getResponseHeader(
							'content-disposition'
						);
						a.download =
							contentDispositionHeader.split(
								'filename='
							)[ 1 ] || 'Beastfeedbacks-Export.csv';

						document.body.appendChild( a );
						a.click();
						document.body.removeChild( a );
						window.URL.revokeObjectURL( a.href );
						btn.disabled = false;
					}
				);
			} );
		};
	} );
} );
