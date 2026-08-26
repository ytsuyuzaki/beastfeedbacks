/* global addLoadEvent, jQuery */

document.addEventListener( 'DOMContentLoaded', () => {
	const btn = document.querySelector( '.beastfeedbacks-export-btn' );
	if ( ! btn ) {
		return;
	}

	const { endpoint, action, nonce } = btn.dataset;

	addLoadEvent( function () {
		btn.onclick = () => {
			btn.disabled = true;
			jQuery( function ( $ ) {
				const urlParams = new URLSearchParams( window.location.search );
				const requestData = {
					action,
					_wpnonce: nonce,
				};

				const typeParam = urlParams.get( 'beastfeedbacks_type' );
				if ( typeParam ) {
					requestData.beastfeedbacks_type = typeParam;
				}

				const parentIdParam = urlParams.get(
					'beastfeedbacks_parent_id'
				);
				if ( parentIdParam ) {
					requestData.beastfeedbacks_parent_id = parentIdParam;
				}

				$.post(
					endpoint,
					requestData,
					function ( response, status, xhr ) {
						const blob = new Blob( [ response ], {
							type: 'application/octetstream',
						} );

						const a = document.createElement( 'a' );
						a.href = window.URL.createObjectURL( blob );

						const contentDispositionHeader = xhr.getResponseHeader(
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
