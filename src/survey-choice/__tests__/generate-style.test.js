import { GenerateStyle } from '../index';

describe( 'GenerateStyle', () => {
	it( 'returns default styles when called with no arguments or empty object', () => {
		const expectedDefaults = {
			display: 'flex',
			flexFlow: null,
			justifyContent: undefined,
			flexWrap: 'wrap',
		};

		expect( GenerateStyle() ).toEqual( expectedDefaults );
		expect( GenerateStyle( {} ) ).toEqual( expectedDefaults );
	} );

	it( 'returns default styles when layout is explicitly null', () => {
		expect( GenerateStyle( { layout: null } ) ).toEqual( {
			display: 'flex',
			flexFlow: null,
			justifyContent: undefined,
			flexWrap: 'wrap',
		} );
	} );

	it( 'uses layout defaults when layout is an empty object', () => {
		expect( GenerateStyle( { layout: {} } ) ).toEqual( {
			display: 'flex',
			flexFlow: null,
			justifyContent: undefined,
			flexWrap: 'wrap',
		} );
	} );

	it( 'sets flexFlow to column when orientation is vertical', () => {
		const result = GenerateStyle( {
			layout: { orientation: 'vertical' },
		} );
		expect( result.flexFlow ).toBe( 'column' );
	} );

	it( 'sets flexFlow to null when orientation is horizontal or not vertical', () => {
		const resultHorizontal = GenerateStyle( {
			layout: { orientation: 'horizontal' },
		} );
		expect( resultHorizontal.flexFlow ).toBeNull();
	} );

	it( 'applies custom layout properties (type, justifyContent, flexWrap)', () => {
		const result = GenerateStyle( {
			layout: {
				type: 'grid',
				justifyContent: 'center',
				flexWrap: 'nowrap',
			},
		} );

		expect( result ).toEqual( {
			display: 'grid',
			flexFlow: null,
			justifyContent: 'center',
			flexWrap: 'nowrap',
		} );
	} );

	it( 'sets width to 100% when justifyContent is space-between', () => {
		const result = GenerateStyle( {
			layout: {
				justifyContent: 'space-between',
			},
		} );

		expect( result ).toEqual( {
			display: 'flex',
			flexFlow: null,
			justifyContent: 'space-between',
			flexWrap: 'wrap',
			width: '100%',
		} );
	} );

	it( 'does not set width property when justifyContent is not space-between', () => {
		const result = GenerateStyle( {
			layout: {
				justifyContent: 'center',
			},
		} );

		expect( result ).toEqual( {
			display: 'flex',
			flexFlow: null,
			justifyContent: 'center',
			flexWrap: 'wrap',
		} );
		expect( result.width ).toBeUndefined();
	} );
} );
