import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createPostWithContentAndVisit,
	POST_BLOCK_CONTENTS,
	getFeedbackForm,
} from './helpers';

test.describe( 'Frontend UI Interactions & Edge Cases', () => {
	test( 'VoteボタンおよびSurvey Form送信ボタンクリック直後にdisabledとなり二重送信が防止されること', async ( {
		requestUtils,
		page,
	} ) => {
		// --- Vote Block の検証 ---
		await createPostWithContentAndVisit( {
			requestUtils,
			page,
			content: POST_BLOCK_CONTENTS.VOTE,
		} );

		const voteForm = getFeedbackForm( page, 'beastfeedbacks_vote_form' );
		await expect( voteForm ).toBeVisible();
		const yesButton = voteForm.getByRole( 'button', { name: /yes/i } );

		let voteRequestCount = 0;
		page.on( 'request', ( req ) => {
			if ( req.url().includes( 'admin-ajax.php' ) ) {
				voteRequestCount++;
			}
		} );

		// Vote ボタンをクリック
		await yesButton.click();

		// ボタンが即座に disabled になることを確認
		await expect( yesButton ).toBeDisabled();

		// disabled 状態のボタンを再度クリック試行
		await yesButton.click( { force: true } ).catch( () => {} );

		// 送信完了メッセージの表示を確認
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();

		// リクエストが1回のみ送信されたことを確認
		expect( voteRequestCount ).toBe( 1 );

		// --- Survey Form Block の検証 ---
		await createPostWithContentAndVisit( {
			requestUtils,
			page,
			content: POST_BLOCK_CONTENTS.SURVEY,
		} );

		const surveyForm = getFeedbackForm(
			page,
			'beastfeedbacks_survey_form'
		);
		await expect( surveyForm ).toBeVisible();

		const firstRadio = surveyForm.locator( 'input[type="radio"]' ).first();
		await firstRadio.check();

		const submitButton = surveyForm.locator( 'button[type="submit"]' );

		let surveyRequestCount = 0;
		page.on( 'request', ( req ) => {
			if ( req.url().includes( 'admin-ajax.php' ) ) {
				surveyRequestCount++;
			}
		} );

		// 送信ボタンをクリック
		await submitButton.click();

		// ボタンが即座に disabled になることを確認
		await expect( submitButton ).toBeDisabled();

		// disabled 状態のボタンを再度クリック試行
		await submitButton.click( { force: true } ).catch( () => {} );

		// 送信完了メッセージの表示を確認
		await expect(
			page.getByText(
				/Thank you for your responses to the questionnaire/i
			)
		).toBeVisible();

		// リクエストが1回のみ送信されたことを確認
		expect( surveyRequestCount ).toBe( 1 );
	} );

	test( '同一ページ内に複数のLikeブロックが設置された場合、クリックしたブロックのカウントのみ更新されること', async ( {
		requestUtils,
		page,
	} ) => {
		await createPostWithContentAndVisit( {
			requestUtils,
			page,
			content: `${ POST_BLOCK_CONTENTS.LIKE }\n${ POST_BLOCK_CONTENTS.LIKE }`,
		} );

		const forms = page.locator( 'form[name="beastfeedbacks_like_form"]' );
		await expect( forms ).toHaveCount( 2 );

		const form1 = forms.nth( 0 );
		const form2 = forms.nth( 1 );

		// 初期表示で両方のカウントが '0' であることを確認
		await expect( form1.locator( '.like-count' ) ).toHaveText( '0' );
		await expect( form2.locator( '.like-count' ) ).toHaveText( '0' );

		// 1つ目の Like ボタンをクリック
		const likeButton1 = form1.getByRole( 'button', { name: /like/i } );
		await likeButton1.click();

		// 1つ目のブロックのカウントのみが '1' に更新されることを確認
		await expect( form1.locator( '.like-count' ) ).toHaveText( '1' );
		// 2つ目のブロックのカウントは '0' のままであることを確認
		await expect( form2.locator( '.like-count' ) ).toHaveText( '0' );
	} );

	test( '通信エラー発生時に画面上にエラーメッセージが表示されること', async ( {
		requestUtils,
		page,
	} ) => {
		// 意図的な500エラー発生時のブラウザ標準エラーログ出力を抑制
		page.removeAllListeners( 'console' );

		// admin-ajax.php へのリクエストをインターセプトして 500 エラーを返却する
		await page.route( '**/admin-ajax.php', ( route ) =>
			route.fulfill( {
				status: 500,
				contentType: 'text/plain',
				body: 'Error',
			} )
		);

		await createPostWithContentAndVisit( {
			requestUtils,
			page,
			content: POST_BLOCK_CONTENTS.LIKE,
		} );

		const form = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( form ).toBeVisible();

		const likeButton = form.getByRole( 'button', { name: /like/i } );
		await likeButton.click();

		// エラーメッセージが表示されることを確認
		await expect(
			page.getByText( 'Oops! Something went wrong.' )
		).toBeVisible();
	} );
} );
