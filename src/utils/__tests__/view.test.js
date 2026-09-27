import { addMessage, submitForm } from '../view';

describe( 'src/utils/view.js', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		document.body.innerHTML = '';
		window.fetch = jest.fn();
	} );

	afterEach( () => {
		jest.useRealTimers();
		jest.restoreAllMocks();
	} );

	const setupDOM = () => {
		const container = document.createElement( 'div' );
		container.innerHTML = `
			<form name="test_form" action="/wp-admin/admin-ajax.php" method="POST">
				<input type="text" name="field1" value="value1" />
				<button type="submit">Submit</button>
			</form>
		`;
		document.body.appendChild( container );
		const form = container.querySelector( 'form' );
		const submitButton = container.querySelector( 'button[type="submit"]' );
		return { container, form, submitButton };
	};

	describe( 'addMessage', () => {
		it( 'creates a span element by default and inserts it after the form', () => {
			const { form } = setupDOM();
			addMessage( form, 'Test message' );

			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'SPAN' );
			expect( messageElement.textContent ).toBe( 'Test message' );
		} );

		it( 'creates a custom tag element and auto-hides after specified timeout', () => {
			const { form } = setupDOM();
			addMessage( form, 'Paragraph message', {
				tagName: 'p',
				autoHideMs: 3000,
			} );

			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'P' );
			expect( messageElement.textContent ).toBe( 'Paragraph message' );

			jest.advanceTimersByTime( 3000 );
			expect( messageElement.style.display ).toBe( 'none' );
		} );
	} );

	describe( 'submitForm', () => {
		it( 'handles standard form submit with default options', async () => {
			const { form, submitButton } = setupDOM();
			const mockResponse = { message: 'Success message' };

			jest.spyOn( window, 'fetch' ).mockResolvedValue( {
				ok: true,
				json: jest.fn().mockResolvedValue( mockResponse ),
			} );

			const event = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );
			Object.defineProperty( event, 'submitter', {
				value: submitButton,
				writable: false,
			} );
			Object.defineProperty( event, 'target', {
				value: form,
				writable: false,
			} );

			submitForm( event );

			expect( submitButton.hasAttribute( 'disabled' ) ).toBe( true );
			expect( window.fetch ).toHaveBeenCalledWith(
				'/wp-admin/admin-ajax.php',
				{
					method: form.method,
					body: expect.any( FormData ),
				}
			);

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'SPAN' );
			expect( messageElement.textContent ).toBe( 'Success message' );
		} );

		it( 'uses custom getBody, disableSubmitter=false, tagName=p, autoHideMs and onSuccess callback', async () => {
			const { form } = setupDOM();
			const mockResponse = { count: 10, message: 'Updated' };
			const onSuccessSpy = jest.fn();

			jest.spyOn( window, 'fetch' ).mockResolvedValue( {
				ok: true,
				json: jest.fn().mockResolvedValue( mockResponse ),
			} );

			const customBody = new FormData();
			customBody.append( 'custom', 'data' );

			const event = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );
			Object.defineProperty( event, 'target', {
				value: form,
				writable: false,
			} );

			submitForm( event, {
				getBody: () => customBody,
				disableSubmitter: false,
				tagName: 'p',
				autoHideMs: 3000,
				onSuccess: onSuccessSpy,
			} );

			expect( window.fetch ).toHaveBeenCalledWith(
				'/wp-admin/admin-ajax.php',
				{
					method: form.method,
					body: customBody,
				}
			);

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			expect( onSuccessSpy ).toHaveBeenCalledWith( mockResponse, form );
			const messageElement = form.nextSibling;
			expect( messageElement.tagName ).toBe( 'P' );
			expect( messageElement.textContent ).toBe( 'Updated' );

			jest.advanceTimersByTime( 3000 );
			expect( messageElement.style.display ).toBe( 'none' );
		} );

		it( 'handles server error response (success: false) and re-enables submitter', async () => {
			const { form, submitButton } = setupDOM();

			jest.spyOn( window, 'fetch' ).mockResolvedValue( {
				ok: false,
				status: 400,
				json: jest.fn().mockResolvedValue( {
					success: false,
					data: { message: 'Invalid data' },
				} ),
			} );

			const event = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );
			Object.defineProperty( event, 'submitter', {
				value: submitButton,
				writable: false,
			} );
			Object.defineProperty( event, 'target', {
				value: form,
				writable: false,
			} );

			submitForm( event );

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			const messageElement = form.nextSibling;
			expect( messageElement.textContent ).toBe( 'Invalid data' );
			expect( submitButton.hasAttribute( 'disabled' ) ).toBe( false );
		} );

		it( 'handles network error and re-enables submitter', async () => {
			const { form, submitButton } = setupDOM();

			jest.spyOn( window, 'fetch' ).mockRejectedValue(
				new Error( 'Network failure' )
			);

			const event = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );
			Object.defineProperty( event, 'submitter', {
				value: submitButton,
				writable: false,
			} );
			Object.defineProperty( event, 'target', {
				value: form,
				writable: false,
			} );

			submitForm( event );

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			const messageElement = form.nextSibling;
			expect( messageElement.textContent ).toBe(
				'Oops! Something went wrong.'
			);
			expect( submitButton.hasAttribute( 'disabled' ) ).toBe( false );
		} );
	} );
} );
