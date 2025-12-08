import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'plugin active', () => {
	test( 'verifies the plugin is active (activate if needed)', async ( {
		admin,
		page,
	} ) => {
		const pluginSlug = 'beastfeedbacks';

		// プラグイン一覧へ
		await admin.visitAdminPage( 'plugins.php' );
		await page.waitForLoadState( 'domcontentloaded' );

		// 該当プラグインの行
		const row = page.locator( `tr[data-slug="${ pluginSlug }"]` );
		await expect( row, 'Plugin row should exist' ).toHaveCount( 1 );

		// すでに active クラスが付いているか？
		let isActive = await row.evaluate( ( el ) =>
			el.classList.contains( 'active' )
		);

		// 未有効の場合は「有効化」リンクをクリック（言語非依存：action=activate）
		if ( ! isActive ) {
			const activateLink = row.locator( 'a[href*="action=activate"]' );
			await expect(
				activateLink,
				'Activate link should exist'
			).toHaveCount( 1 );
			await activateLink.click();

			// ページリロード待ち
			await page.waitForLoadState( 'domcontentloaded' );

			// 同じセレクタで再取得（ロケータは自動再解決されるが、明示的に待つ）
			await expect(
				page.locator( `tr[data-slug="${ pluginSlug }"].active` )
			).toHaveCount( 1 );

			// 確認用に再評価
			isActive = await page
				.locator( `tr[data-slug="${ pluginSlug }"]` )
				.evaluate( ( el ) => el.classList.contains( 'active' ) );
		}

		// 最終アサーション
		expect( isActive ).toBe( true );

		// 参考：無効化リンク（存在すれば有効化済みの証拠）
		await expect(
			page.locator(
				`tr[data-slug="${ pluginSlug }"] a[href*="action=deactivate"]`
			)
		).toHaveCount( 1 );
	} );
} );
