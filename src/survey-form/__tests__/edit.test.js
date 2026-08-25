import { render, screen } from '@testing-library/react';
import { Edit } from '../index';

describe( 'Survey Form Block Edit component', () => {
	it( 'renders form and inner blocks container', () => {
		render( <Edit /> );

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'mock-inner-blocks-props' )
		).toBeInTheDocument();
	} );
} );
