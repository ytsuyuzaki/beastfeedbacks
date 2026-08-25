import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	insertBlockAndVerify,
	publishAndVisit,
	insertPublishAndVisit,
	getFeedbackForm,
	visitFeedbackAdmin,
	getLatestFeedbackRow,
} from './helpers';

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
		await insertBlockAndVerify( {
			editor,
			blockName: 'beastfeedbacks/survey-form',
		} );

		// 投稿を公開してフロントエンドページへ移動する
		await publishAndVisit( { editor, page } );

		// フロントエンドにアンケートフォームが表示されていることを確認する
		const form = getFeedbackForm( page, 'beastfeedbacks_survey_form' );
		await expect( form ).toBeVisible();
		await expect(
			form.locator( 'input[type="radio"]' ).first()
		).toBeVisible();
		await expect( form.locator( 'textarea' ) ).toBeVisible();
		await expect( form.locator( 'button[type="submit"]' ) ).toBeVisible();
	} );

	test( 'フォームを送信するとデータが実行され、データベースに保存されること', async ( {
		admin,
		editor,
		page,
	} ) => {
		const feedbackMessage = 'E2Eテストフィードバックメッセージ';

		// ブロックを挿入・公開してフロントエンドページへ移動する
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/survey-form',
		} );

		const form = getFeedbackForm( page, 'beastfeedbacks_survey_form' );
		await expect( form ).toBeVisible();

		// ラジオボタンを選択する（最初の選択肢）
		const firstRadio = form.locator( 'input[type="radio"]' ).first();
		await firstRadio.check();
		await expect( firstRadio ).toBeChecked();

		// テキストエリアに入力する
		const textarea = form.locator( 'textarea' );
		await textarea.fill( feedbackMessage );
		await expect( textarea ).toHaveValue( feedbackMessage );

		// フォームを送信する
		await form.locator( 'button[type="submit"]' ).click();

		// 送信完了メッセージを確認する
		await expect(
			page.getByText(
				/Thank you for your responses to the questionnaire/i
			)
		).toBeVisible();

		// 管理画面に移動してデータベースに保存された内容を確認する
		await visitFeedbackAdmin( { admin, page } );

		const latestRow = getLatestFeedbackRow( page );
		await expect( latestRow ).toBeVisible();

		// Type が survey として保存されていることを確認
		await expect(
			latestRow.locator( '.column-beastfeedbacks_type' )
		).toHaveText( 'survey' );

		// Response カラムに入力したメッセージが含まれていることを確認
		await expect(
			latestRow.locator( '.column-beastfeedbacks_response' )
		).toContainText( feedbackMessage );

		// Source カラムに元記事へのリンクが存在することを確認
		await expect(
			latestRow.locator(
				'.column-beastfeedbacks_source a[target="_blank"]'
			)
		).toBeVisible();
	} );
} );
