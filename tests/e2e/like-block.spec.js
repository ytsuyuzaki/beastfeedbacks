import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	insertBlockAndVerify,
	publishAndVisit,
	insertPublishAndVisit,
	getFeedbackForm,
	visitFeedbackAdmin,
	getLatestFeedbackRow,
} from './helpers';

test.describe( 'Like Block', () => {
	test.beforeEach( async ( { admin } ) => {
		// それぞれのテストの前に新しい投稿を作成する
		await admin.createNewPost();
	} );

	test( 'Gutenbergエディタでブロックを設置・保存し、表示画面でLikeボタンが表示されること', async ( {
		editor,
		page,
	} ) => {
		// エディタ上にLikeブロックが表示されていることを確認
		await insertBlockAndVerify( {
			editor,
			blockName: 'beastfeedbacks/like',
		} );

		// 投稿を公開してフロントエンドページへ移動する
		await publishAndVisit( { editor, page } );

		// フロントエンドにLikeボタンと初期カウントが表示されていることを確認する
		const form = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( form ).toBeVisible();
		await expect( form.locator( '.like-count' ) ).toHaveText( '0' );
		await expect(
			form.getByRole( 'button', { name: /like/i } )
		).toBeVisible();
	} );

	test( 'Likeボタンをクリックすると投票が実行され、データベースに保存されること', async ( {
		admin,
		editor,
		page,
	} ) => {
		// ブロックを挿入・公開してフロントエンドページへ移動する
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/like',
		} );

		const form = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( form ).toBeVisible();
		await expect( form.locator( '.like-count' ) ).toHaveText( '0' );

		// Likeボタンをクリックして実行する
		const likeButton = form.getByRole( 'button', { name: /like/i } );
		await likeButton.click();

		// 送信完了メッセージとカウント更新（1）を確認する
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();
		await expect( form.locator( '.like-count' ) ).toHaveText( '1' );

		// 管理画面に移動してデータベースに保存された内容を確認する
		await visitFeedbackAdmin( { admin, page } );

		const latestRow = getLatestFeedbackRow( page );
		await expect( latestRow ).toBeVisible();

		// Type が like として保存されていることを確認
		await expect(
			latestRow.locator( '.column-beastfeedbacks_type' )
		).toHaveText( 'like' );

		// Source カラムに元記事へのリンクが存在することを確認
		await expect(
			latestRow.locator(
				'.column-beastfeedbacks_source a[target="_blank"]'
			)
		).toBeVisible();
	} );
} );
