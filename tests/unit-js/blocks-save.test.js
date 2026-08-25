import { render, screen } from '@testing-library/react';
import { registerBlockType } from '@wordpress/blocks';

// Import block modules to trigger registerBlockType calls
import '../../src/like/index';
import '../../src/vote/index';
import '../../src/survey-form/index';
import variationsFromExport from '../../src/survey-form/variations';
import '../../src/survey-input/index';
import '../../src/survey-choice/index';

const getBlockConfig = ( blockName ) => {
	const call = registerBlockType.mock.calls.find(
		( c ) => c[ 0 ] === blockName
	);
	return call ? call[ 1 ] : null;
};

describe( 'Block save functions & variations', () => {
	describe( 'Like block save', () => {
		it( 'renders save component with InnerBlocks.Content', () => {
			const config = getBlockConfig( 'beastfeedbacks/like' );
			expect( config ).toBeDefined();
			expect( typeof config.save ).toBe( 'function' );

			const SaveComponent = config.save;
			render( <SaveComponent /> );

			expect(
				screen.getByTestId( 'mock-block-props-save' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'mock-inner-blocks-content' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Vote block save', () => {
		it( 'renders save component with InnerBlocks.Content', () => {
			const config = getBlockConfig( 'beastfeedbacks/vote' );
			expect( config ).toBeDefined();
			expect( typeof config.save ).toBe( 'function' );

			const SaveComponent = config.save;
			render( <SaveComponent /> );

			expect(
				screen.getByTestId( 'mock-block-props-save' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'mock-inner-blocks-content' )
			).toBeInTheDocument();
		} );
	} );

	describe( 'Survey Form block save', () => {
		it( 'renders save component with InnerBlocks.Content', () => {
			const config = getBlockConfig( 'beastfeedbacks/survey-form' );
			expect( config ).toBeDefined();
			expect( typeof config.save ).toBe( 'function' );

			const SaveComponent = config.save;
			render( <SaveComponent /> );

			expect(
				screen.getByTestId( 'mock-block-props-save' )
			).toBeInTheDocument();
			expect(
				screen.getByTestId( 'mock-inner-blocks-content' )
			).toBeInTheDocument();
		} );

		it( 'registers variations in block configuration', () => {
			const config = getBlockConfig( 'beastfeedbacks/survey-form' );
			expect( config.variations ).toEqual( variationsFromExport );
		} );
	} );

	describe( 'Survey Form variations definition', () => {
		it( 'exports array of variation objects with required properties', () => {
			expect( Array.isArray( variationsFromExport ) ).toBe( true );
			expect( variationsFromExport.length ).toBeGreaterThan( 0 );

			variationsFromExport.forEach( ( variation ) => {
				expect( variation ).toHaveProperty( 'name' );
				expect( variation ).toHaveProperty( 'title' );
				expect( variation ).toHaveProperty( 'innerBlocks' );
				expect( Array.isArray( variation.innerBlocks ) ).toBe( true );
			} );
		} );

		it( 'contains product survey variation definition', () => {
			const productVariation = variationsFromExport.find(
				( v ) => v.name === 'product'
			);
			expect( productVariation ).toBeDefined();
			expect( productVariation.title ).toBe( 'Product survey' );
			expect( productVariation.innerBlocks.length ).toBeGreaterThan( 0 );
		} );
	} );

	describe( 'Survey Input block save', () => {
		const getSaveComponent = () => {
			const config = getBlockConfig( 'beastfeedbacks/survey-input' );
			return config ? config.save : null;
		};

		it( 'renders default text input field', () => {
			const SaveComponent = getSaveComponent();
			expect( SaveComponent ).toBeDefined();

			const attributes = {
				label: 'Your Name',
				tagType: 'text',
				required: false,
				placeholder: 'Enter your name',
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			expect( screen.getByText( 'Your Name' ) ).toBeInTheDocument();
			const input = container.querySelector( 'input[type="text"]' );
			expect( input ).toBeInTheDocument();
			expect( input ).toHaveAttribute( 'name', 'Your Name' );
			expect( input ).toHaveAttribute( 'placeholder', 'Enter your name' );
			expect( input ).not.toHaveAttribute( 'required' );
		} );

		it( 'renders textarea field when tagType is textarea', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Comments',
				tagType: 'textarea',
				required: false,
				placeholder: 'Write comments...',
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			expect( screen.getByText( 'Comments' ) ).toBeInTheDocument();
			const textarea = container.querySelector( 'textarea' );
			expect( textarea ).toBeInTheDocument();
			expect( textarea ).toHaveAttribute( 'name', 'Comments' );
			expect( textarea ).toHaveAttribute(
				'placeholder',
				'Write comments...'
			);
			expect( textarea ).toHaveAttribute( 'rows', '3' );
		} );

		it( 'renders required badge and required attribute when required is true', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Email',
				tagType: 'text',
				required: true,
				placeholder: '',
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const requiredSpan = container.querySelector(
				'.beastfeedbacks-survey-input_label_required'
			);
			expect( requiredSpan ).toBeInTheDocument();

			const input = container.querySelector( 'input' );
			expect( input ).toHaveAttribute( 'required' );
		} );

		it( 'applies width style when width attribute is specified', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Feedback',
				tagType: 'text',
				width: 50,
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const wrapper = container.firstChild;
			expect( wrapper ).toHaveStyle( { width: '50%' } );
		} );

		it( 'strips HTML tags from label to generate the name attribute', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: '<b>Full Name</b>',
				tagType: 'text',
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const input = container.querySelector( 'input' );
			expect( input ).toHaveAttribute( 'name', 'Full Name' );
		} );
	} );

	describe( 'Survey Choice block save', () => {
		const getSaveComponent = () => {
			const config = getBlockConfig( 'beastfeedbacks/survey-choice' );
			return config ? config.save : null;
		};

		it( 'renders radio input choices', () => {
			const SaveComponent = getSaveComponent();
			expect( SaveComponent ).toBeDefined();

			const attributes = {
				label: 'Gender',
				tagType: 'radio',
				required: false,
				items: [ 'Male', 'Female' ],
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const radios = container.querySelectorAll( 'input[type="radio"]' );
			expect( radios ).toHaveLength( 2 );
			expect( radios[ 0 ] ).toHaveAttribute( 'name', 'Gender' );
			expect( radios[ 0 ] ).toHaveAttribute( 'value', 'Male' );
			expect( radios[ 1 ] ).toHaveAttribute( 'value', 'Female' );
		} );

		it( 'renders checkbox input choices with array brackets in name', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Interests',
				tagType: 'checkbox',
				required: false,
				items: [ 'Sports', 'Music' ],
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const checkboxes = container.querySelectorAll(
				'input[type="checkbox"]'
			);
			expect( checkboxes ).toHaveLength( 2 );
			expect( checkboxes[ 0 ] ).toHaveAttribute( 'name', 'Interests[]' );
			expect( checkboxes[ 0 ] ).toHaveAttribute( 'value', 'Sports' );
		} );

		it( 'renders select dropdown choice', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Age Group',
				tagType: 'select',
				required: false,
				items: [ '10s', '20s' ],
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const select = container.querySelector( 'select' );
			expect( select ).toBeInTheDocument();
			expect( select ).toHaveAttribute( 'name', 'Age Group' );

			const options = container.querySelectorAll( 'option' );
			expect( options ).toHaveLength( 3 ); // 1 default 'Please select' + 2 items
			expect( options[ 0 ] ).toHaveValue( '' );
			expect( options[ 1 ] ).toHaveValue( '10s' );
			expect( options[ 2 ] ).toHaveValue( '20s' );
		} );

		it( 'renders required badge and required attributes when required is true', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Satisfaction',
				tagType: 'radio',
				required: true,
				items: [ 'Good', 'Bad' ],
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const requiredSpan = container.querySelector(
				'.beastfeedbacks-survey-choice_label_required'
			);
			expect( requiredSpan ).toBeInTheDocument();

			const inputs = container.querySelectorAll( 'input' );
			inputs.forEach( ( input ) => {
				expect( input ).toHaveAttribute( 'required' );
			} );
		} );

		it( 'applies width and layout styles correctly', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: 'Choice',
				tagType: 'radio',
				items: [ 'A' ],
				width: 75,
				layout: {
					type: 'flex',
					orientation: 'vertical',
					justifyContent: 'space-between',
					flexWrap: 'nowrap',
				},
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const rootWrapper = container.firstChild;
			expect( rootWrapper ).toHaveStyle( { width: '75%' } );

			const itemsWrapper = container.querySelector(
				'.beastfeedbacks-survey-choice_items'
			);
			expect( itemsWrapper ).toHaveStyle( {
				display: 'flex',
				flexFlow: 'column',
				justifyContent: 'space-between',
				flexWrap: 'nowrap',
				width: '100%',
			} );
		} );

		it( 'strips HTML tags from label for input name attribute', () => {
			const SaveComponent = getSaveComponent();
			const attributes = {
				label: '<span>Category</span>',
				tagType: 'checkbox',
				items: [ 'Item 1' ],
			};

			const { container } = render(
				<SaveComponent attributes={ attributes } />
			);

			const input = container.querySelector( 'input' );
			expect( input ).toHaveAttribute( 'name', 'Category[]' );
		} );
	} );
} );
