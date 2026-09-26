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

// 複数フォームを設定した場合に考慮
const forms = document.querySelectorAll(
	'form[name="beastfeedbacks_vote_form"]'
);
for ( const form of forms ) {
	form.addEventListener( 'submit', submit );
}
