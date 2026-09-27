import { submitForm } from '../utils/view';

const submit = ( e ) => {
	submitForm( e, {
		getBody: ( form, submitter ) => {
			const buttons = form.getElementsByTagName( 'button' );
			const select = [];
			for ( const button of buttons ) {
				select.push( button.textContent );
			}

			const body = new FormData( form );
			body.append( 'selected', submitter ? submitter.textContent : '' );
			body.append( 'select', select );
			return body;
		},
	} );
};

/**
 * Attach submit event listeners to all vote forms on the page.
 * Handles cases where multiple forms are present.
 */
const forms = document.querySelectorAll(
	'form[name="beastfeedbacks_vote_form"]'
);
for ( const form of forms ) {
	form.addEventListener( 'submit', submit );
}
