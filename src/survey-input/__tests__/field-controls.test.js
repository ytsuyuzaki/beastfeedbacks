import { render, screen } from '@testing-library/react';
import FieldControls from '../field-controls';
import SharedFieldControls from '../../components/field-controls';

jest.mock( '../../components/field-controls', () => {
	return jest.fn( () => <div data-testid="shared-field-controls" /> );
} );

describe( 'Survey Input FieldControls', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders SharedFieldControls with correct tagTypes and passed props', () => {
		const props = {
			attributes: { required: true, width: 50 },
			setAttributes: jest.fn(),
		};

		render( <FieldControls { ...props } /> );

		// We just check the first argument which contains the props
		expect( SharedFieldControls.mock.calls[ 0 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				...props,
				tagTypes: [ 'text', 'textarea', 'email', 'url', 'number' ],
			} )
		);

		expect(
			screen.getByTestId( 'shared-field-controls' )
		).toBeInTheDocument();
	} );
} );
