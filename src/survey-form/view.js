import { submitForm } from '../utils/view';

// 複数フォームを設定した場合に考慮
const forms = document.querySelectorAll(
	'form[name="beastfeedbacks_survey_form"]'
);
for ( const form of forms ) {
	form.addEventListener( 'submit', submitForm );
}
