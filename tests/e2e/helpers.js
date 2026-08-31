import { expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * REST API 経由での投稿作成時に使用するブロックHTMLテンプレート
 */
export const POST_BLOCK_CONTENTS = {
	LIKE: '<!-- wp:beastfeedbacks/like --><div class="wp-block-beastfeedbacks-like"><div class="wp-block-button"><button type="submit" class="wp-block-button__link wp-element-button">Like</button></div></div><!-- /wp:beastfeedbacks/like -->',
	VOTE: '<!-- wp:beastfeedbacks/vote --><div class="wp-block-beastfeedbacks-vote"><div class="wp-block-button"><button type="submit" class="wp-block-button__link wp-element-button">Yes</button></div><div class="wp-block-button"><button type="submit" class="wp-block-button__link wp-element-button">No</button></div></div><!-- /wp:beastfeedbacks/vote -->',
	SURVEY: '<!-- wp:beastfeedbacks/survey-form --><div class="wp-block-beastfeedbacks-survey-form"><!-- wp:beastfeedbacks/survey-choice {"items":["Good","Bad"],"label":"Satisfaction","tagType":"radio"} --><div class="wp-block-beastfeedbacks-survey-choice"><p class="beastfeedbacks-survey-choice_label">Satisfaction</p><div class="beastfeedbacks-survey-choice_items"><label class="beastfeedbacks-survey-choice_item"><input type="radio" name="Satisfaction" value="Good"/>Good</label><label class="beastfeedbacks-survey-choice_item"><input type="radio" name="Satisfaction" value="Bad"/>Bad</label></div></div><!-- /wp:beastfeedbacks/survey-choice --><!-- wp:beastfeedbacks/survey-input {"label":"Description","tagType":"textarea"} --><div class="wp-block-beastfeedbacks-survey-input"><label class="beastfeedbacks-survey-input_label">Description</label><textarea name="Description" rows="3"></textarea></div><!-- /wp:beastfeedbacks/survey-input --><!-- wp:button {"tagName":"button","type":"submit"} --><div class="wp-block-button"><button type="submit" class="wp-block-button__link wp-element-button">Submit</button></div><!-- /wp:button --></div><!-- /wp:beastfeedbacks/survey-form -->',
};

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
 * REST API 経由でブロックを含む投稿を作成し、フロントエンドページへ遷移する高速ヘルパー
 *
 * @param {Object} fixtures              - テストフィクスチャ
 * @param {Object} fixtures.requestUtils - REST API ユーティリティ
 * @param {Object} fixtures.page         - Playwright ページオブジェクト
 * @param {string} fixtures.content      - 投稿本文 (ブロックコメント HTML)
 * @return {Promise<Object>} 作成された投稿オブジェクト
 */
export async function createPostWithContentAndVisit( {
	requestUtils,
	page,
	content,
} ) {
	const post = await requestUtils.createPost( {
		status: 'publish',
		title: 'Feedback Test Post',
		content,
	} );
	await page.goto( post.link );
	await page.waitForLoadState( 'domcontentloaded' );
	return post;
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
