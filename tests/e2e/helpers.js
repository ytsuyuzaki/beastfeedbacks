import { expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * ブロックをエディタに挿入し、キャンバス上で表示を確認するヘルパー
 *
 * @param {Object} fixtures           - テストフィクスチャ
 * @param {Object} fixtures.editor    - エディタユーティリティ
 * @param {string} fixtures.blockName - ブロック名 (例: 'beastfeedbacks/survey-form')
 */
export async function insertBlockAndVerify( { editor, blockName } ) {
	await editor.insertBlock( { name: blockName } );
	await expect(
		editor.canvas.locator( `[data-type="${ blockName }"]` )
	).toBeVisible();
}

/**
 * 投稿を公開し、公開パネルの "View Post" リンクからフロントエンドページへ遷移するヘルパー
 *
 * @param {Object} fixtures        - テストフィクスチャ
 * @param {Object} fixtures.editor - エディタユーティリティ
 * @param {Object} fixtures.page   - Playwright ページオブジェクト
 */
export async function publishAndVisit( { editor, page } ) {
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

/**
 * ブロックを挿入・公開し、フロントエンドページへ遷移する一括ヘルパー
 *
 * @param {Object} fixtures           - テストフィクスチャ
 * @param {Object} fixtures.editor    - エディタユーティリティ
 * @param {Object} fixtures.page      - Playwright ページオブジェクト
 * @param {string} fixtures.blockName - ブロック名 (例: 'beastfeedbacks/survey-form')
 */
export async function insertPublishAndVisit( {
	editor,
	page,
	blockName = 'beastfeedbacks/survey-form',
} ) {
	await editor.insertBlock( { name: blockName } );
	await publishAndVisit( { editor, page } );
}

/**
 * 指定したフィードバックフォーム要素のロケータを取得するヘルパー
 *
 * @param {Object} page     - Playwright ページオブジェクト
 * @param {string} formName - フォームの name 属性 (例: 'beastfeedbacks_survey_form')
 * @return {import('@playwright/test').Locator} フォームのロケータ
 */
export function getFeedbackForm(
	page,
	formName = 'beastfeedbacks_survey_form'
) {
	return page.locator( `form[name="${ formName }"]` );
}

/**
 * BeastFeedbacks の管理画面（フィードバック一覧）に遷移するヘルパー
 *
 * @param {Object} fixtures       - テストフィクスチャ
 * @param {Object} fixtures.admin - Admin ユーティリティ
 * @param {Object} fixtures.page  - Playwright ページオブジェクト
 */
export async function visitFeedbackAdmin( { admin, page } ) {
	await admin.visitAdminPage( 'edit.php?post_type=beastfeedbacks' );
	await page.waitForLoadState( 'domcontentloaded' );
}

/**
 * フィードバック一覧テーブルの最新（1行目）の行ロケータを取得するヘルパー
 *
 * @param {Object} page - Playwright ページオブジェクト
 * @return {import('@playwright/test').Locator} 先頭行のロケータ
 */
export function getLatestFeedbackRow( page ) {
	return page.locator( '#the-list tr' ).first();
}
