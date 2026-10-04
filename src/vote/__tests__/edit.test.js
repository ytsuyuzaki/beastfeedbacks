import { render, screen } from '@testing-library/react';
import { Edit } from '../index';
import { __ } from '@wordpress/i18n';

describe( 'Vote Block Edit component', () => {
	it( 'renders block props wrapper and inner blocks', () => {
		render( <Edit /> );

		expect( screen.getByTestId( 'mock-block-props' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'mock-inner-blocks' ) ).toBeInTheDocument();
	} );

	it( 'renders the form with correct name attribute', () => {
		const { container } = render( <Edit /> );
		const form = container.querySelector( 'form' );
		expect( form ).toBeInTheDocument();
		expect( form ).toHaveAttribute( 'name', 'beastfeedbacks_vote_form' );
	} );

	it( 'renders inner blocks with correct TEMPLATE', () => {
		render( <Edit /> );
		const innerBlocks = screen.getByTestId( 'mock-inner-blocks' );

		const expectedTemplate = [
			[
				'core/heading',
				{
					level: 3,
					content: __(
						'Were you satisfied with the content of the article?',
						'beastfeedbacks'
					),
				},
			],
			[
				'core/buttons',
				{},
				[
					[
						'core/button',
						{
							text: __( 'Yes', 'beastfeedbacks' ),
							tagName: 'button',
							type: 'submit',
						},
					],
					[
						'core/button',
						{
							text: __( 'No', 'beastfeedbacks' ),
							tagName: 'button',
							type: 'submit',
						},
					],
				],
			],
		];

		const templateString = innerBlocks.getAttribute( 'data-template' );
		expect( JSON.parse( templateString ) ).toEqual( expectedTemplate );
	} );

	it( 'renders inner blocks with templateLock set to false', () => {
		render( <Edit /> );
		const innerBlocks = screen.getByTestId( 'mock-inner-blocks' );
		expect( innerBlocks ).toHaveAttribute( 'data-template-lock', 'false' );
	} );
} );
