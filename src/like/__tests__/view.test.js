describe( 'src/like/view.js', () => {
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
		document.body.innerHTML = `
			<div>
				<form name="beastfeedbacks_like_form" action="/wp-admin/admin-ajax.php" method="POST">
					<div>
						<p class="like-count">0</p>
					</div>
					<button type="submit">Like</button>
				</form>
			</div>
		`;
		return document.querySelector(
			'form[name="beastfeedbacks_like_form"]'
		);
	};

	it( 'registers submit event listener when form exists on DOM', () => {
		const form = setupDOM();
		const addEventListenerSpy = jest.spyOn( form, 'addEventListener' );

		jest.isolateModules( () => {
			require( '../view' );
		} );

		expect( addEventListenerSpy ).toHaveBeenCalledWith(
			'submit',
			expect.any( Function )
		);
	} );

	describe( 'Like submission success scenario', () => {
		it( 'prevents default form submit, calls fetch with correct parameters, updates like count, appends message and hides it after 3 seconds', async () => {
			const form = setupDOM();

			const mockJsonResponse = {
				count: 5,
				message: 'Thank you for your feedback!',
			};

			const fetchSpy = jest.spyOn( window, 'fetch' ).mockResolvedValue( {
				ok: true,
				json: jest.fn().mockResolvedValue( mockJsonResponse ),
			} );

			jest.isolateModules( () => {
				require( '../view' );
			} );

			const submitEvent = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );
			const preventDefaultSpy = jest.spyOn(
				submitEvent,
				'preventDefault'
			);

			form.dispatchEvent( submitEvent );

			expect( preventDefaultSpy ).toHaveBeenCalled();
			expect( fetchSpy ).toHaveBeenCalledTimes( 1 );
			expect( fetchSpy ).toHaveBeenCalledWith(
				'/wp-admin/admin-ajax.php',
				{
					method: form.method,
					body: expect.any( FormData ),
				}
			);

			// Drain microtasks for nested promise resolutions
			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			// Verify count update
			const countElement = form.querySelector( '.like-count' );
			expect( countElement.textContent ).toBe( '5' );

			// Verify message paragraph appended directly after form parent element's insertBefore call
			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'P' );
			expect( messageElement.textContent ).toBe(
				'Thank you for your feedback!'
			);

			// Fast-forward 3 seconds
			jest.advanceTimersByTime( 3000 );
			expect( messageElement.style.display ).toBe( 'none' );
		} );
	} );

	describe( 'Like submission error handling', () => {
		it( 'displays error message when fetch response is not ok (response.ok === false)', async () => {
			const form = setupDOM();

			jest.spyOn( window, 'fetch' ).mockResolvedValue( {
				ok: false,
				json: jest.fn(),
			} );

			jest.isolateModules( () => {
				require( '../view' );
			} );

			const submitEvent = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );

			form.dispatchEvent( submitEvent );

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'P' );
			expect( messageElement.textContent ).toBe(
				'Oops! Something went wrong.'
			);
		} );

		it( 'displays error message on network failure', async () => {
			const form = setupDOM();

			jest.spyOn( window, 'fetch' ).mockRejectedValue(
				new Error( 'Network error' )
			);

			jest.isolateModules( () => {
				require( '../view' );
			} );

			const submitEvent = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );

			form.dispatchEvent( submitEvent );

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'P' );
			expect( messageElement.textContent ).toBe(
				'Oops! Something went wrong.'
			);
		} );

		it( 'displays custom error message when server returns rate limit error response', async () => {
			const form = setupDOM();

			jest.spyOn( window, 'fetch' ).mockResolvedValue( {
				ok: false,
				status: 429,
				json: jest.fn().mockResolvedValue( {
					success: false,
					data: { message: 'Too many requests' },
				} ),
			} );

			jest.isolateModules( () => {
				require( '../view' );
			} );

			const submitEvent = new Event( 'submit', {
				bubbles: true,
				cancelable: true,
			} );

			form.dispatchEvent( submitEvent );

			for ( let i = 0; i < 10; i++ ) {
				await Promise.resolve();
			}

			const messageElement = form.nextSibling;
			expect( messageElement ).not.toBeNull();
			expect( messageElement.tagName ).toBe( 'P' );
			expect( messageElement.textContent ).toBe( 'Too many requests' );
		} );
	} );
} );
