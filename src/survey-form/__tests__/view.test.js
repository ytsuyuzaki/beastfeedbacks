/**
 * External dependencies
 */

describe( 'Survey Form view script', () => {
	let fetchSpy;

	beforeEach( () => {
		document.body.innerHTML = '';
		jest.clearAllMocks();
		jest.resetModules();
		global.fetch = jest.fn();
		fetchSpy = jest.spyOn( global, 'fetch' );
	} );

	afterEach( () => {
		if ( fetchSpy ) {
			fetchSpy.mockRestore();
		}
		delete global.fetch;
	} );

	const createFormDOM = () => {
		const container = document.createElement( 'div' );
		container.innerHTML = `
			<form name="beastfeedbacks_survey_form" action="/wp-admin/admin-ajax.php" method="POST">
				<input type="text" name="question1" value="Answer 1" />
				<button type="submit">Submit</button>
			</form>
		`;
		document.body.appendChild( container );
		const form = container.querySelector( 'form' );
		const submitButton = container.querySelector( 'button[type="submit"]' );
		return { container, form, submitButton };
	};

	test( 'should register submit event listener on matching form elements', () => {
		const { form } = createFormDOM();
		const addEventListenerSpy = jest.spyOn( form, 'addEventListener' );

		require( '../view' );

		expect( addEventListenerSpy ).toHaveBeenCalledWith(
			'submit',
			expect.any( Function )
		);
	} );

	test( 'should disable submit button, send fetch POST request with FormData, and add success message on success', async () => {
		const { container, form, submitButton } = createFormDOM();

		let resolveJson;
		const jsonPromise = new Promise( ( resolve ) => {
			resolveJson = resolve;
		} );

		fetchSpy.mockResolvedValue( {
			ok: true,
			json: () => jsonPromise,
		} );

		require( '../view' );

		const event = new Event( 'submit', {
			bubbles: true,
			cancelable: true,
		} );
		Object.defineProperty( event, 'submitter', {
			value: submitButton,
			writable: false,
		} );

		form.dispatchEvent( event );

		expect( submitButton.hasAttribute( 'disabled' ) ).toBe( true );
		expect( fetchSpy ).toHaveBeenCalledTimes( 1 );
		expect( fetchSpy ).toHaveBeenCalledWith( '/wp-admin/admin-ajax.php', {
			method: form.method,
			body: expect.any( FormData ),
		} );

		const fetchCall = fetchSpy.mock.calls[ 0 ];
		const formData = fetchCall[ 1 ].body;
		expect( formData.get( 'question1' ) ).toBe( 'Answer 1' );

		resolveJson( { message: 'Thank you for your feedback!' } );
		await jsonPromise;
		// Flush Microtask queue
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		const messageSpan = container.querySelector( 'span' );
		expect( messageSpan ).not.toBeNull();
		expect( messageSpan.textContent ).toBe(
			'Thank you for your feedback!'
		);
		expect( form.nextSibling ).toBe( messageSpan );
	} );

	test( 'should display error message when fetch response is not ok', async () => {
		const { container, form, submitButton } = createFormDOM();

		fetchSpy.mockResolvedValue( {
			ok: false,
			status: 500,
		} );

		require( '../view' );

		const event = new Event( 'submit', {
			bubbles: true,
			cancelable: true,
		} );
		Object.defineProperty( event, 'submitter', {
			value: submitButton,
			writable: false,
		} );

		form.dispatchEvent( event );

		// Flush microtask queue
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		const messageSpan = container.querySelector( 'span' );
		expect( messageSpan ).not.toBeNull();
		expect( messageSpan.textContent ).toBe( 'Oops! Something went wrong.' );
	} );

	test( 'should display error message when fetch throws network error', async () => {
		const { container, form, submitButton } = createFormDOM();

		fetchSpy.mockRejectedValue( new Error( 'Network error' ) );

		require( '../view' );

		const event = new Event( 'submit', {
			bubbles: true,
			cancelable: true,
		} );
		Object.defineProperty( event, 'submitter', {
			value: submitButton,
			writable: false,
		} );

		form.dispatchEvent( event );

		// Flush microtask queue
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		const messageSpan = container.querySelector( 'span' );
		expect( messageSpan ).not.toBeNull();
		expect( messageSpan.textContent ).toBe( 'Oops! Something went wrong.' );
		expect( submitButton.disabled ).toBe( false );
	} );

	test( 'should display custom error message and re-enable button when rate limit is returned', async () => {
		const { container, form, submitButton } = createFormDOM();

		fetchSpy.mockResolvedValue( {
			ok: false,
			status: 429,
			json: jest.fn().mockResolvedValue( {
				success: false,
				data: { message: 'Too many requests' },
			} ),
		} );

		require( '../view' );

		const event = new Event( 'submit', {
			bubbles: true,
			cancelable: true,
		} );
		Object.defineProperty( event, 'submitter', {
			value: submitButton,
			writable: false,
		} );

		form.dispatchEvent( event );

		// Flush microtask queue
		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		const messageSpan = container.querySelector( 'span' );
		expect( messageSpan ).not.toBeNull();
		expect( messageSpan.textContent ).toBe( 'Too many requests' );
		expect( submitButton.disabled ).toBe( false );
	} );
} );
