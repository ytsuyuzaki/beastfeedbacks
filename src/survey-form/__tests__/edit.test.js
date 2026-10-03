import { render, screen } from '@testing-library/react';
import { Edit } from '../index';

describe( 'Survey Form Block Edit component', () => {
	it( 'renders form and inner blocks container', () => {
		render( <Edit /> );

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'mock-inner-blocks-props' )
		).toBeInTheDocument();

		// Verify the form element exists
		const form = document.querySelector(
			'form[name="beastfeedbacks_survey_form"]'
		);
		expect( form ).toBeInTheDocument();

		// Verify the default template contains expected blocks
		const innerBlocksProps = screen.getByTestId(
			'mock-inner-blocks-props'
		);
		const options = JSON.parse(
			innerBlocksProps.getAttribute( 'data-options' )
		);

		expect( options.template ).toHaveLength( 4 );
		expect( options.template[ 0 ][ 0 ] ).toBe( 'core/heading' );
		expect( options.template[ 1 ][ 0 ] ).toBe(
			'beastfeedbacks/survey-choice'
		);
		expect( options.template[ 2 ][ 0 ] ).toBe(
			'beastfeedbacks/survey-input'
		);
		expect( options.template[ 3 ][ 0 ] ).toBe( 'core/button' );
	} );
} );
