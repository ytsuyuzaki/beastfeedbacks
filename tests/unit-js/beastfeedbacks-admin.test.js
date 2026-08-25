describe( 'beastfeedbacks-admin.js', () => {
	let mockAddLoadEvent;
	let mockCreateObjectURL;
	let mockRevokeObjectURL;
	let mockPost;
	let mockJQuery;

	beforeEach( () => {
		// Reset DOM
		document.body.innerHTML = '';

		// Reset mocks
		mockAddLoadEvent = jest.fn( ( cb ) => cb() );
		window.addLoadEvent = mockAddLoadEvent;

		mockCreateObjectURL = jest.fn( () => 'blob:http://localhost/mock-url' );
		mockRevokeObjectURL = jest.fn();
		window.URL.createObjectURL = mockCreateObjectURL;
		window.URL.revokeObjectURL = mockRevokeObjectURL;

		// Mock Blob if needed
		if ( ! window.Blob ) {
			window.Blob = jest.fn();
		}

		// Mock jQuery
		mockPost = jest.fn();
		mockJQuery = jest.fn( ( fn ) => {
			if ( typeof fn === 'function' ) {
				fn( mockJQuery );
			}
			return mockJQuery;
		} );
		mockJQuery.post = mockPost;
		window.jQuery = mockJQuery;
		window.$ = mockJQuery;

		// Reset module registry so requiring the script runs the DOMContentLoaded listener setup
		jest.resetModules();
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		delete window.addLoadEvent;
		delete window.jQuery;
		delete window.$;
	} );

	test( 'DOM上に .beastfeedbacks-export-btn が存在しない場合は何もしないこと', () => {
		require( '../../public/js/beastfeedbacks-admin.js' );
		document.dispatchEvent( new Event( 'DOMContentLoaded' ) );

		expect( mockAddLoadEvent ).not.toHaveBeenCalled();
	} );

	describe( 'CSVエクスポート正常系', () => {
		let btn;

		beforeEach( () => {
			btn = document.createElement( 'button' );
			btn.className = 'beastfeedbacks-export-btn';
			btn.dataset.endpoint = 'http://example.com/wp-admin/admin-ajax.php';
			btn.dataset.action = 'beastfeedbacks_export';
			btn.dataset.nonce = 'test-nonce-123';
			document.body.appendChild( btn );
		} );

		test( 'ボタンクリック時に btn.disabled = true になり、$.post が指定引数で呼び出されること', () => {
			require( '../../public/js/beastfeedbacks-admin.js' );
			document.dispatchEvent( new Event( 'DOMContentLoaded' ) );

			expect( mockAddLoadEvent ).toHaveBeenCalled();

			btn.click();

			expect( btn.disabled ).toBe( true );
			expect( mockPost ).toHaveBeenCalledWith(
				'http://example.com/wp-admin/admin-ajax.php',
				{
					action: 'beastfeedbacks_export',
					_wpnonce: 'test-nonce-123',
				},
				expect.any( Function )
			);
		} );

		test( 'Content-Disposition ヘッダーからファイル名が抽出され、Blob / a タグダウンロード / revokeObjectURL / btn.disabled 復元が行われること', () => {
			require( '../../public/js/beastfeedbacks-admin.js' );
			document.dispatchEvent( new Event( 'DOMContentLoaded' ) );

			btn.click();

			const postCallback = mockPost.mock.calls[ 0 ][ 2 ];
			const mockXhr = {
				getResponseHeader: jest.fn( ( header ) =>
					header === 'content-disposition'
						? 'attachment; filename=export-2026.csv'
						: ''
				),
			};

			const appendChildSpy = jest.spyOn( document.body, 'appendChild' );
			const removeChildSpy = jest.spyOn( document.body, 'removeChild' );
			const anchorClickSpy = jest
				.spyOn( window.HTMLAnchorElement.prototype, 'click' )
				.mockImplementation( () => {} );

			let createdAnchor = null;
			const origCreateElement = document.createElement.bind( document );
			jest.spyOn( document, 'createElement' ).mockImplementation(
				( tagName, options ) => {
					const el = origCreateElement( tagName, options );
					if ( tagName === 'a' ) {
						createdAnchor = el;
					}
					return el;
				}
			);

			postCallback( 'csv,data\n1,2', 'success', mockXhr );

			expect( mockXhr.getResponseHeader ).toHaveBeenCalledWith(
				'content-disposition'
			);
			expect( mockCreateObjectURL ).toHaveBeenCalled();
			expect( createdAnchor ).not.toBeNull();
			expect( createdAnchor.download ).toBe( 'export-2026.csv' );
			expect( createdAnchor.href ).toBe(
				'blob:http://localhost/mock-url'
			);
			expect( appendChildSpy ).toHaveBeenCalledWith( createdAnchor );
			expect( anchorClickSpy ).toHaveBeenCalled();
			expect( removeChildSpy ).toHaveBeenCalledWith( createdAnchor );
			expect( mockRevokeObjectURL ).toHaveBeenCalledWith(
				'blob:http://localhost/mock-url'
			);
			expect( btn.disabled ).toBe( false );
		} );

		test( 'ヘッダーにファイル名が存在しない場合、デフォルトファイル名 Beastfeedbacks-Export.csv が使用されること', () => {
			require( '../../public/js/beastfeedbacks-admin.js' );
			document.dispatchEvent( new Event( 'DOMContentLoaded' ) );

			btn.click();

			const postCallback = mockPost.mock.calls[ 0 ][ 2 ];
			const mockXhr = {
				getResponseHeader: jest.fn( () => '' ),
			};

			const anchorClickSpy = jest
				.spyOn( window.HTMLAnchorElement.prototype, 'click' )
				.mockImplementation( () => {} );

			let createdAnchor = null;
			const origCreateElement = document.createElement.bind( document );
			jest.spyOn( document, 'createElement' ).mockImplementation(
				( tagName, options ) => {
					const el = origCreateElement( tagName, options );
					if ( tagName === 'a' ) {
						createdAnchor = el;
					}
					return el;
				}
			);

			postCallback( 'csv,data\n1,2', 'success', mockXhr );

			expect( createdAnchor ).not.toBeNull();
			expect( createdAnchor.download ).toBe(
				'Beastfeedbacks-Export.csv'
			);
			expect( anchorClickSpy ).toHaveBeenCalled();
			expect( btn.disabled ).toBe( false );
		} );
	} );
} );
