import { render, screen } from '@testing-library/react';
import { Edit } from '../index';

describe( 'Like Block Edit component', () => {
	it( 'renders block props wrapper and form', () => {
		const { container } = render( <Edit /> );

		// Check the block wrapper
		const blockWrapper = screen.getByTestId( 'mock-block-props' );
		expect( blockWrapper ).toBeInTheDocument();
		expect( blockWrapper.tagName.toLowerCase() ).toBe( 'div' );

		// Check the form rendering
		const form = container.querySelector( 'form[name="beastfeedbacks_like_form"]' );
		expect( form ).toBeInTheDocument();

		// Check the balloon layout and like count
		const balloon = container.querySelector( '.beastfeedbacks-like_balloon' );
		expect( balloon ).toBeInTheDocument();
		const likeCount = container.querySelector( '.like-count' );
		expect( likeCount ).toBeInTheDocument();
		expect( likeCount.textContent ).toBe( '0' );

		// Check the InnerBlocks rendering and props
		const innerBlocks = screen.getByTestId( 'mock-inner-blocks' );
		expect( innerBlocks ).toBeInTheDocument();

		const allowedBlocks = JSON.parse( innerBlocks.getAttribute( 'data-allowed-blocks' ) );
		expect( allowedBlocks ).toEqual( [ 'core/button' ] );

		const template = JSON.parse( innerBlocks.getAttribute( 'data-template' ) );
		expect( template ).toEqual( [
			[
				'core/button',
				{
					text: 'Like',
					tagName: 'button',
					type: 'submit',
				},
			],
		] );

		const templateLock = innerBlocks.getAttribute( 'data-template-lock' );
		expect( templateLock ).toBe( 'all' );
	} );
} );
