import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FieldControls from '../field-controls';

describe( 'FieldControls component', () => {
	const defaultAttributes = {
		width: 50,
		required: false,
		tagType: 'text',
	};

	it( 'renders inspector controls and block controls', () => {
		const setAttributes = jest.fn();
		render(
			<FieldControls
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				tagTypes={ [ 'text', 'email', 'number' ] }
			/>
		);

		expect(
			screen.getByTestId( 'mock-inspector-controls' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'mock-block-controls' )
		).toBeInTheDocument();
	} );

	it( 'calls setAttributes when required toolbar button is clicked', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();

		render(
			<FieldControls
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
				tagTypes={ [ 'text', 'email' ] }
			/>
		);

		const requiredButton = screen.getByRole( 'button', {
			name: 'Required',
		} );
		await user.click( requiredButton );

		expect( setAttributes ).toHaveBeenCalledWith( { required: true } );
	} );

	it( 'calls setAttributes when width button is clicked', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();

		render(
			<FieldControls
				attributes={ defaultAttributes }
				setAttributes={ setAttributes }
			/>
		);

		const width75Button = screen.getByRole( 'button', { name: '75%' } );
		await user.click( width75Button );

		expect( setAttributes ).toHaveBeenCalledWith( { width: 75 } );
	} );

	it( 'resets width when currently selected width button is clicked again', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();

		render(
			<FieldControls
				attributes={ { ...defaultAttributes, width: 50 } }
				setAttributes={ setAttributes }
			/>
		);

		const width50Button = screen.getByRole( 'button', { name: '50%' } );
		await user.click( width50Button );

		expect( setAttributes ).toHaveBeenCalledWith( { width: undefined } );
	} );
} );
