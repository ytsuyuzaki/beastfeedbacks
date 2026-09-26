import { submitForm } from '../utils/view';

const submit = ( e ) => {
	submitForm( e, {
		disableSubmitter: false,
		tagName: 'p',
		autoHideMs: 3000,
		onSuccess: ( data, form ) => {
			if ( data?.count ) {
				const likeCounts = form.querySelectorAll( '.like-count' );
				for ( const likeCount of likeCounts ) {
					likeCount.textContent = data.count;
				}
			}
		},
	} );
};

// 複数フォームを設定した場合に考慮
const forms = document.querySelectorAll(
	'form[name="beastfeedbacks_like_form"]'
);
for ( const form of forms ) {
	form.addEventListener( 'submit', submit );
}
