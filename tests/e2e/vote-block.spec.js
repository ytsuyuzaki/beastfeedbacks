import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	insertBlockAndVerify,
	publishAndVisit,
	insertPublishAndVisit,
	getFeedbackForm,
	visitFeedbackAdmin,
	getLatestFeedbackRow,
} from './helpers';

test.describe( 'Vote Block', () => {
	test.beforeEach( async ( { admin } ) => {
		// それぞれのテストの前に新しい投稿を作成する
		await admin.createNewPost();
	} );

	test( 'Gutenbergエディタでブロックを設置・保存し、表示画面で投票ボタンが表示されること', async ( {
		editor,
		page,
	} ) => {
		// エディタ上にVoteブロックが表示されていることを確認
		await insertBlockAndVerify( {
			editor,
			blockName: 'beastfeedbacks/vote',
		} );

		// 投稿を公開してフロントエンドページへ移動する
		await publishAndVisit( { editor, page } );

		// フロントエンドに投票フォームとボタンが表示されていることを確認する
		const form = getFeedbackForm( page, 'beastfeedbacks_vote_form' );
		await expect( form ).toBeVisible();
		await expect(
			form.getByRole( 'button', { name: /yes/i } )
		).toBeVisible();
		await expect(
			form.getByRole( 'button', { name: /no/i } )
		).toBeVisible();
	} );

	test( '投票ボタンをクリックすると投票が実行され、データベースに保存されること', async ( {
		admin,
		editor,
		page,
	} ) => {
		// ブロックを挿入・公開してフロントエンドページへ移動する
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/vote',
		} );

		const form = getFeedbackForm( page, 'beastfeedbacks_vote_form' );
		await expect( form ).toBeVisible();

		const yesButton = form.getByRole( 'button', { name: /yes/i } );
		await expect( yesButton ).toBeVisible();

		// Yes ボタンをクリックして投票する
		await yesButton.click();

		// ボタンが無効化され、送信完了メッセージが表示されることを確認する
		await expect( yesButton ).toBeDisabled();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();

		// 管理画面に移動してデータベースに保存された内容を確認する
		await visitFeedbackAdmin( { admin, page } );

		const latestRow = getLatestFeedbackRow( page );
		await expect( latestRow ).toBeVisible();

		// Type が vote として保存されていることを確認
		await expect(
			latestRow.locator( '.column-beastfeedbacks_type' )
		).toHaveText( 'vote' );

		// Response カラムに選択した内容（Yes）が含まれていることを確認
		await expect(
			latestRow.locator( '.column-beastfeedbacks_response' )
		).toContainText( 'Yes' );

		// Source カラムに元記事へのリンクが存在することを確認
		await expect(
			latestRow.locator(
				'.column-beastfeedbacks_source a[target="_blank"]'
			)
		).toBeVisible();
	} );
} );
