import { render, screen } from '@testing-library/react';
import { Edit } from '../index';

describe( 'Vote Block Edit component', () => {
	it( 'renders block props wrapper and inner blocks', () => {
		render( <Edit /> );

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'mock-inner-blocks' ) ).toBeInTheDocument();
	} );
} );
