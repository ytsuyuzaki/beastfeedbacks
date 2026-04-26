import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * アンケートフォームブロックを挿入・公開し、フロントエンドページへ遷移するヘルパー
 *
 * @param {Object} fixtures        - テストフィクスチャ
 * @param {Object} fixtures.editor - エディタユーティリティ
 * @param {Object} fixtures.page   - Playwright ページオブジェクト
 */
async function insertPublishAndVisit( { editor, page } ) {
	await editor.insertBlock( { name: 'beastfeedbacks/survey-form' } );
	await editor.publishPost();

	// 公開パネルが開いた状態で "View Post" リンクの href を取得して直接遷移する
	// （パネルヘッダーがリンクをブロックするためクリックではなく goto を使用）
	const viewPostLink = page
		.getByRole( 'link', { name: /view post/i } )
		.first();
	await expect( viewPostLink ).toBeVisible();
	const href = await viewPostLink.getAttribute( 'href' );
	await page.goto( href );

	await page.waitForLoadState( 'domcontentloaded' );
}

test.describe( 'Survey Form Block', () => {
	test.beforeEach( async ( { admin } ) => {
		// それぞれのテストの前に新しい投稿を作成する
		await admin.createNewPost();
	} );

	test( 'Gutenbergエディタでブロックを設置・保存し、表示画面でフォームが表示されること', async ( {
		editor,
		page,
	} ) => {
		// エディタ上にフォームブロックが表示されていることを確認
		// ブロックエディタのコンテンツは iframe 内にあるため editor.canvas を使用
		await editor.insertBlock( { name: 'beastfeedbacks/survey-form' } );
		await expect(
			editor.canvas.locator( '[data-type="beastfeedbacks/survey-form"]' )
		).toBeVisible();

		// 投稿を公開してフロントエンドページへ移動する
		await editor.publishPost();
		const viewPostLink = page
			.getByRole( 'link', { name: /view post/i } )
			.first();
		await expect( viewPostLink ).toBeVisible();
		const href = await viewPostLink.getAttribute( 'href' );
		await page.goto( href );
		await page.waitForLoadState( 'domcontentloaded' );

		// フロントエンドにアンケートフォームが表示されていることを確認する
		const form = page.locator( 'form[name="beastfeedbacks_survey_form"]' );
		await expect( form ).toBeVisible();
		await expect(
			form.locator( 'input[type="radio"]' ).first()
		).toBeVisible();
		await expect( form.locator( 'textarea' ) ).toBeVisible();
		await expect( form.locator( 'button[type="submit"]' ) ).toBeVisible();
	} );

	test( 'フォームを送信するとデータが書き込まれること', async ( {
		editor,
		page,
	} ) => {
		// ブロックを挿入・公開してフロントエンドページへ移動する
		await insertPublishAndVisit( { editor, page } );

		const form = page.locator( 'form[name="beastfeedbacks_survey_form"]' );
		await expect( form ).toBeVisible();

		// ラジオボタンを選択する（最初の選択肢）
		const firstRadio = form.locator( 'input[type="radio"]' ).first();
		await firstRadio.check();
		await expect( firstRadio ).toBeChecked();

		// テキストエリアに入力する
		const textarea = form.locator( 'textarea' );
		await textarea.fill( 'テストフィードバックメッセージ' );
		await expect( textarea ).toHaveValue(
			'テストフィードバックメッセージ'
		);

		// フォームを送信する
		await form.locator( 'button[type="submit"]' ).click();

		// 送信完了メッセージ（データ書き込み成功）を確認する
		await expect(
			page.getByText(
				/Thank you for your responses to the questionnaire/i
			)
		).toBeVisible();
	} );
} );
