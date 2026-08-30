import { __ } from '@wordpress/i18n';

const addMessage = ( form, message ) => {
	const messageElement = document.createElement( 'span' );
	messageElement.textContent = message;
	form.parentElement.insertBefore( messageElement, form.nextSibling );
};

const submit = ( e ) => {
	e.preventDefault();
	const submitter = e.submitter;
	if ( submitter ) {
		submitter.setAttribute( 'disabled', true );
	}

	const form = e.target;
	const action = form.getAttribute( 'action' );

	fetch( action, {
		method: form.method,
		body: new FormData( form ),
	} )
		.then( ( response ) => {
			if ( typeof response.json === 'function' ) {
				return Promise.resolve( response.json() )
					.then( ( data ) => ( { ok: response.ok, data } ) )
					.catch( () => ( { ok: response.ok, data: {} } ) );
			}
			if ( ! response.ok ) {
				throw new Error();
			}
			return { ok: response.ok, data: {} };
		} )
		.then( ( { ok, data } ) => {
			if ( ! ok || data?.success === false ) {
				const errorMessage =
					data?.data?.message ||
					data?.message ||
					__( 'Oops! Something went wrong.', 'beastfeedbacks' );
				addMessage( form, errorMessage );
				if ( submitter ) {
					submitter.removeAttribute( 'disabled' );
				}
				return;
			}

			addMessage( form, data?.message );
		} )
		.catch( () => {
			addMessage(
				form,
				__( 'Oops! Something went wrong.', 'beastfeedbacks' )
			);
			if ( submitter ) {
				submitter.removeAttribute( 'disabled' );
			}
		} );
};

// 複数フォームを設定した場合に考慮
const forms = document.querySelectorAll(
	'form[name="beastfeedbacks_survey_form"]'
);
for ( const form of forms ) {
	form.addEventListener( 'submit', submit );
}
