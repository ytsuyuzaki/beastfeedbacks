import { __ } from '@wordpress/i18n';

/**
 * Adds a feedback message element next to the given form.
 *
 * @param {HTMLFormElement} form                     The form element.
 * @param {string}          message                  The message text to display.
 * @param {Object}          [options]                Options for creating the message element.
 * @param {string}          [options.tagName='span'] HTML tag name for the message element.
 * @param {number}          [options.autoHideMs=0]   Time in ms before hiding the message, or 0 for no timeout.
 */
export const addMessage = (
	form,
	message,
	{ tagName = 'span', autoHideMs = 0 } = {}
) => {
	const messageElement = document.createElement( tagName );
	messageElement.textContent = message;
	form.parentElement.insertBefore( messageElement, form.nextSibling );
	if ( autoHideMs > 0 ) {
		setTimeout( function () {
			messageElement.style.display = 'none';
		}, autoHideMs );
	}
};

/**
 * Handles form submit for BeastFeedbacks frontend view forms.
 *
 * @param {Event}    e                          Submit event.
 * @param {Object}   [options]                  Configuration options.
 * @param {Function} [options.getBody]          Function to build body FormData, signature: (form, submitter) => FormData.
 * @param {Function} [options.onSuccess]        Callback on successful submission, signature: (data, form) => void.
 * @param {string}   [options.tagName='span']   Message tag name ('span' or 'p').
 * @param {number}   [options.autoHideMs=0]     Message auto hide timeout in ms.
 * @param {boolean}  [options.disableSubmitter] Whether to disable the submitter button during submission.
 */
export const submitForm = ( e, options = {} ) => {
	const {
		getBody,
		onSuccess,
		tagName = 'span',
		autoHideMs = 0,
		disableSubmitter = true,
	} = options;

	e.preventDefault();
	const submitter = e.submitter;
	if ( disableSubmitter && submitter ) {
		submitter.setAttribute( 'disabled', true );
	}

	const form = e.target;
	const action = form.getAttribute( 'action' );
	const body = getBody ? getBody( form, submitter ) : new FormData( form );

	fetch( action, {
		method: form.method,
		body,
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
				addMessage( form, errorMessage, { tagName, autoHideMs } );
				if ( disableSubmitter && submitter ) {
					submitter.removeAttribute( 'disabled' );
				}
				return;
			}

			if ( onSuccess ) {
				onSuccess( data, form );
			}

			addMessage( form, data?.message, { tagName, autoHideMs } );
		} )
		.catch( () => {
			addMessage(
				form,
				__( 'Oops! Something went wrong.', 'beastfeedbacks' ),
				{ tagName, autoHideMs }
			);
			if ( disableSubmitter && submitter ) {
				submitter.removeAttribute( 'disabled' );
			}
		} );
};
