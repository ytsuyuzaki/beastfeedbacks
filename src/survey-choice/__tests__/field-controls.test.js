import { render } from '@testing-library/react';
import FieldControls from '../field-controls';
import SharedFieldControls from '../../components/field-controls';

jest.mock( '../../components/field-controls', () => {
	return jest.fn( () => <div data-testid="shared-field-controls" /> );
} );

describe( 'Survey Choice FieldControls component', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders SharedFieldControls with correct tagTypes and passed props', () => {
		const props = {
			attributes: { tagType: 'radio' },
			setAttributes: jest.fn(),
		};

		render( <FieldControls { ...props } /> );

		expect( SharedFieldControls ).toHaveBeenCalledWith(
			{
				...props,
				tagTypes: [ 'radio', 'checkbox', 'select' ],
			},
			undefined
		);
	} );
} );
