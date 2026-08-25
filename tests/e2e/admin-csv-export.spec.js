import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	insertPublishAndVisit,
	getFeedbackForm,
	visitFeedbackAdmin,
} from './helpers';

test.describe( 'CSV Export in Admin Feedback Page', () => {
	test.beforeEach( async ( { admin, editor, page } ) => {
		// 1. Create a post with Like block and submit feedback
		await admin.createNewPost();
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/like',
		} );
		const likeForm = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( likeForm ).toBeVisible();
		await likeForm.getByRole( 'button', { name: /like/i } ).click();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();

		// 2. Create a post with Vote block and submit feedback
		await admin.createNewPost();
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/vote',
		} );
		const voteForm = getFeedbackForm( page, 'beastfeedbacks_vote_form' );
		await expect( voteForm ).toBeVisible();
		await voteForm.getByRole( 'button', { name: /yes/i } ).click();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();

		// 3. Create a post with Survey Form block and submit feedback
		await admin.createNewPost();
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/survey-form',
		} );
		const surveyForm = getFeedbackForm(
			page,
			'beastfeedbacks_survey_form'
		);
		await expect( surveyForm ).toBeVisible();
		const firstRadio = surveyForm.locator( 'input[type="radio"]' ).first();
		await firstRadio.check();
		const textarea = surveyForm.locator( 'textarea' );
		await textarea.fill( 'E2E CSV Export Test Response' );
		await surveyForm.locator( 'button[type="submit"]' ).click();
		await expect(
			page.getByText(
				/Thank you for your responses to the questionnaire/i
			)
		).toBeVisible();
	} );

	test( '管理画面でCSVエクスポートボタンが表示され、クリックして正常にCSVファイルがダウンロードされること', async ( {
		admin,
		page,
	} ) => {
		// フィードバック一覧画面にアクセスする
		await visitFeedbackAdmin( { admin, page } );

		// CSVエクスポートボタンが表示されていることを確認する
		const exportButton = page.locator( '.beastfeedbacks-export-btn' );
		await expect( exportButton ).toBeVisible();

		// ダウンロードイベントの完了を待機しつつエクスポートボタンをクリック
		const downloadPromise = page.waitForEvent( 'download' );
		await exportButton.click();
		const download = await downloadPromise;

		// ダウンロードファイル名が *.csv であることを確認する
		const suggestedFileName = download.suggestedFilename();
		expect( suggestedFileName ).toMatch( /\.csv$/ );

		// ダウンロードしたファイルの内容を読み込み検証する
		const stream = await download.createReadStream();
		const chunks = [];
		for await ( const chunk of stream ) {
			chunks.push( chunk );
		}
		const content = Buffer.concat( chunks ).toString( 'utf-8' );

		// ヘッダー行に主要カラムが含まれていることを確認する
		const lines = content.trim().split( /\r?\n/ );
		expect( lines.length ).toBeGreaterThan( 1 );

		const header = lines[ 0 ];
		expect( header ).toContain( 'source' );
		expect( header ).toContain( 'date' );
		expect( header ).toContain( 'type' );
		expect( header ).toContain( 'ip_address' );
		expect( header ).toContain( 'user_agent' );

		// データ行に各種フィードバックタイプ（like, vote, survey）が含まれていることを確認する
		const fileBody = lines.slice( 1 ).join( '\n' );
		expect( fileBody ).toContain( 'like' );
		expect( fileBody ).toContain( 'vote' );
		expect( fileBody ).toContain( 'survey' );
		expect( fileBody ).toContain( 'E2E CSV Export Test Response' );
	} );
} );
