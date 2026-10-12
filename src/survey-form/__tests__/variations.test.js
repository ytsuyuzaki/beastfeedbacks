import variations from '../variations';

describe( 'survey-form variations', () => {
	it( 'should export an array of variations', () => {
		expect( Array.isArray( variations ) ).toBe( true );
		expect( variations.length ).toBeGreaterThan( 0 );
	} );

	it( 'should contain the product survey variation', () => {
		const productVariation = variations.find(
			( variation ) => variation.name === 'product'
		);

		expect( productVariation ).toBeDefined();
		expect( productVariation.name ).toBe( 'product' );
		expect( typeof productVariation.title ).toBe( 'string' );
		expect( Array.isArray( productVariation.innerBlocks ) ).toBe( true );
		expect( productVariation.innerBlocks.length ).toBeGreaterThan( 0 );
	} );

	it( 'product variation should have expected inner blocks structure', () => {
		const productVariation = variations.find(
			( variation ) => variation.name === 'product'
		);

		productVariation.innerBlocks.forEach( ( block ) => {
			expect( Array.isArray( block ) ).toBe( true );
			expect( typeof block[ 0 ] ).toBe( 'string' );
			expect( typeof block[ 1 ] ).toBe( 'object' );
		} );

		expect( productVariation.innerBlocks[0][0] ).toBe( 'core/heading' );
		expect( productVariation.innerBlocks[productVariation.innerBlocks.length - 1][0] ).toBe( 'core/button' );
	} );
} );
