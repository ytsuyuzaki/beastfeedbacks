import { waitFor } from '@testing-library/react';

describe( 'Vote Block view.js', () => {
	let fetchSpy;

	beforeEach( () => {
		document.body.innerHTML = '';
		jest.resetModules();
		if ( typeof global.fetch !== 'function' ) {
			global.fetch = () => {};
		}
		fetchSpy = jest.spyOn( global, 'fetch' );
	} );

	afterEach( () => {
		fetchSpy.mockRestore();
	} );

	const createFormDOM = () => {
		const container = document.createElement( 'div' );
		container.innerHTML = `
			<form name="beastfeedbacks_vote_form" action="/wp-admin/admin-ajax.php" method="POST">
				<button type="submit">Option A</button>
				<button type="submit">Option B</button>
			</form>
		`;
		document.body.appendChild( container );
		return {
			container,
			form: container.querySelector( 'form' ),
			buttons: container.querySelectorAll( 'button' ),
		};
	};

	const dispatchSubmit = ( form, submitter ) => {
		let event;
		if ( typeof window.SubmitEvent === 'function' ) {
			event = new window.SubmitEvent( 'submit', {
				submitter,
				bubbles: true,
				cancelable: true,
			} );
		} else {
			event = new Event( 'submit', { bubbles: true, cancelable: true } );
			Object.defineProperty( event, 'submitter', { value: submitter } );
		}
		form.dispatchEvent( event );
		return event;
	};

	describe( 'Initialization / Event listener registration', () => {
		it( 'registers submit event listener when form exists on DOM', () => {
			const { form, buttons } = createFormDOM();

			fetchSpy.mockImplementation( () => new Promise( () => {} ) );

			require( '../view' );

			dispatchSubmit( form, buttons[ 0 ] );

			expect( fetchSpy ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'Success flow (Vote submission & double submit prevention)', () => {
		it( 'disables clicked button, sends FormData with selected and select options, calls fetch, and appends success message span', async () => {
			const { form, buttons } = createFormDOM();

			const responseMessage = 'Thank you for your feedback!';
			fetchSpy.mockResolvedValue( {
				ok: true,
				json: async () => ( { message: responseMessage } ),
			} );

			require( '../view' );

			const clickedButton = buttons[ 0 ];
			dispatchSubmit( form, clickedButton );

			// Check button disabled attribute
			expect( clickedButton ).toHaveAttribute( 'disabled' );

			// Check fetch call arguments
			expect( fetchSpy ).toHaveBeenCalledTimes( 1 );
			const [ url, options ] = fetchSpy.mock.calls[ 0 ];
			expect( url ).toBe( '/wp-admin/admin-ajax.php' );
			expect( options.method.toUpperCase() ).toBe( 'POST' );
			expect( options.body ).toBeInstanceOf( FormData );

			// Check FormData contents
			const formData = options.body;
			expect( formData.get( 'selected' ) ).toBe( 'Option A' );
			expect( formData.get( 'select' ) ).toBe( 'Option A,Option B' );

			// Wait for promise resolution and message element addition
			await waitFor( () => {
				const messageSpan = form.nextSibling;
				expect( messageSpan ).not.toBeNull();
				expect( messageSpan.tagName ).toBe( 'SPAN' );
				expect( messageSpan.textContent ).toBe( responseMessage );
			} );
		} );
	} );

	describe( 'Error handling (Network / Server errors)', () => {
		it( 'appends error message span when fetch encounters a server HTTP error (ok: false)', async () => {
			const { form, buttons } = createFormDOM();

			fetchSpy.mockResolvedValue( {
				ok: false,
				status: 500,
			} );

			require( '../view' );

			dispatchSubmit( form, buttons[ 0 ] );

			await waitFor( () => {
				const messageSpan = form.nextSibling;
				expect( messageSpan ).not.toBeNull();
				expect( messageSpan.tagName ).toBe( 'SPAN' );
				expect( messageSpan.textContent ).toBe(
					'Oops! Something went wrong.'
				);
			} );
		} );

		it( 'appends error message span when fetch fails with a network exception', async () => {
			const { form, buttons } = createFormDOM();

			fetchSpy.mockRejectedValue( new Error( 'Network Error' ) );

			require( '../view' );

			dispatchSubmit( form, buttons[ 0 ] );

			await waitFor( () => {
				const messageSpan = form.nextSibling;
				expect( messageSpan ).not.toBeNull();
				expect( messageSpan.tagName ).toBe( 'SPAN' );
				expect( messageSpan.textContent ).toBe(
					'Oops! Something went wrong.'
				);
				expect( buttons[ 0 ].disabled ).toBe( false );
			} );
		} );

		it( 'displays custom error message and re-enables button when server returns rate limit error response', async () => {
			const { form, buttons } = createFormDOM();

			fetchSpy.mockResolvedValue( {
				ok: false,
				status: 429,
				json: jest.fn().mockResolvedValue( {
					success: false,
					data: { message: 'Too many requests' },
				} ),
			} );

			require( '../view' );

			dispatchSubmit( form, buttons[ 0 ] );

			await waitFor( () => {
				const messageSpan = form.nextSibling;
				expect( messageSpan ).not.toBeNull();
				expect( messageSpan.tagName ).toBe( 'SPAN' );
				expect( messageSpan.textContent ).toBe( 'Too many requests' );
				expect( buttons[ 0 ].disabled ).toBe( false );
			} );
		} );
	} );
} );
