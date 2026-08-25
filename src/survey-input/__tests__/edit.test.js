import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Edit } from '../index';

describe( 'Survey Input Block Edit component', () => {
	const defaultAttributes = {
		label: 'Your Name',
		required: false,
		tagType: 'text',
		placeholder: 'Enter your name',
		width: 100,
	};

	it( 'renders text input mode with label and placeholder', () => {
		const setAttributes = jest.fn();
		const { container } = render(
			<Edit
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
			/>
		);

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect( screen.getAllByText( 'Your Name' )[ 0 ] ).toBeInTheDocument();
		expect(
			screen.getAllByText( 'Enter your name' )[ 0 ]
		).toBeInTheDocument();
		expect(
			container.querySelector(
				'.beastfeedbacks-survey-input_label_required'
			)
		).toBeNull();
	} );

	it( 'renders required indicator when required attribute is true', () => {
		const setAttributes = jest.fn();
		const { container } = render(
			<Edit
				attributes={ { ...defaultAttributes, required: true } }
				setAttributes={ setAttributes }
			/>
		);

		expect(
			container.querySelector(
				'.beastfeedbacks-survey-input_label_required'
			)
		).toBeInTheDocument();
	} );

	it( 'renders textarea mode when tagType is textarea', () => {
		const setAttributes = jest.fn();
		render(
			<Edit
				attributes={ { ...defaultAttributes, tagType: 'textarea' } }
				setAttributes={ setAttributes }
			/>
		);

		const richTexts = screen.getAllByTestId( 'mock-rich-text' );
		const dummyTextarea = richTexts.find( ( el ) =>
			el.classList.contains( 'dummy-textarea' )
		);
		expect( dummyTextarea ).toBeInTheDocument();
	} );

	it( 'calls setAttributes when RichText is updated', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();
		render(
			<Edit
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
			/>
		);

		const labelRichText = screen.getAllByText( 'Your Name' )[ 0 ];
		await user.click( labelRichText );

		expect( setAttributes ).toHaveBeenCalledWith( {
			label: 'Updated Value',
		} );
	} );
} );
