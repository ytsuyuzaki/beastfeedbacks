import { render, screen } from '@testing-library/react';
import { Edit } from '../index';

describe( 'Survey Choice Block Edit component', () => {
	const defaultAttributes = {
		label: 'Satisfaction',
		required: false,
		tagType: 'radio',
		items: [ 'Very satisfied', 'Satisfied', 'Normal' ],
		width: 100,
	};

	it( 'renders label and choice items', () => {
		const setAttributes = jest.fn();
		render(
			<Edit
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect(
			screen.getAllByText( 'Satisfaction' )[ 0 ]
		).toBeInTheDocument();
		expect(
			screen.getAllByText( 'Very satisfied' )[ 0 ]
		).toBeInTheDocument();
		expect( screen.getAllByText( 'Satisfied' )[ 0 ] ).toBeInTheDocument();
		expect( screen.getAllByText( 'Normal' )[ 0 ] ).toBeInTheDocument();
	} );

	it( 'renders required indicator when required is true', () => {
		const setAttributes = jest.fn();
		const { container } = render(
			<Edit
				attributes={ { ...defaultAttributes, required: true } }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		expect(
			container.querySelector(
				'.beastfeedbacks-survey-choice_label_required'
			)
		).toBeInTheDocument();
	} );
} );
