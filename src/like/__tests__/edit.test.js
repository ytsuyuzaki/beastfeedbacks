import { render, screen } from '@testing-library/react';
import { Edit } from '../index';

describe( 'Like Block Edit component', () => {
	it( 'renders block props wrapper and form', () => {
		render( <Edit /> );

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'mock-inner-blocks' ) ).toBeInTheDocument();
		expect( screen.getByText( '0' ) ).toBeInTheDocument();
	} );
} );
