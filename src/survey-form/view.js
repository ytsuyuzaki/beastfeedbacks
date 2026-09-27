import { submitForm } from '../utils/view';

/**
 * Attach submit event listeners to all survey forms on the page.
 * Handles cases where multiple forms are present.
 */
const forms = document.querySelectorAll(
	'form[name="beastfeedbacks_survey_form"]'
);
for ( const form of forms ) {
	form.addEventListener( 'submit', submitForm );
}
