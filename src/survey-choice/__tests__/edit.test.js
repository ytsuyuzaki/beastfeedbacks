import { render, screen, fireEvent } from '@testing-library/react';
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

	it( 'applies width styling when width attribute is provided', () => {
		const setAttributes = jest.fn();
		render(
			<Edit
				attributes={ { ...defaultAttributes, width: 50 } }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		const blockWrapper = screen.getByTestId( 'mock-block-props' );
		expect( blockWrapper ).toHaveStyle( { width: '50%' } );
	} );

	it( 'does not apply width styling when width attribute is missing or null', () => {
		const setAttributes = jest.fn();
		render(
			<Edit
				attributes={ { ...defaultAttributes, width: null } }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		const blockWrapper = screen.getByTestId( 'mock-block-props' );
		expect( blockWrapper.style.width ).toBe( '' );
	} );

	it( 'calls setAttributes when label changes', () => {
		const setAttributes = jest.fn();
		render(
			<Edit
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				isSelected={ false }
			/>
		);

		const textareas = screen.getAllByTestId( 'mock-rich-text-input' );
		fireEvent.change( textareas[ 0 ], { target: { value: 'New Label' } } );

		expect( setAttributes ).toHaveBeenCalledWith(
			expect.objectContaining( { label: 'New Label' } )
		);
	} );
} );
