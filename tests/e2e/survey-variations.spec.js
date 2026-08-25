import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	publishAndVisit,
	getFeedbackForm,
	visitFeedbackAdmin,
	getLatestFeedbackRow,
} from './helpers';

test.describe( 'Survey Form Variations Block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'Choice/Input子ブロックの全タイプを配置・送信し、管理画面で正しく保存されること', async ( {
		admin,
		editor,
		page,
	} ) => {
		const nameValue = 'テスト太郎';
		const commentValue = '大変素晴らしいコンテンツです。';

		// Survey Form ブロックと各タイプの Choice / Input 子ブロックを挿入
		await editor.insertBlock( {
			name: 'beastfeedbacks/survey-form',
			innerBlocks: [
				{
					name: 'beastfeedbacks/survey-input',
					attributes: {
						label: 'お名前',
						tagType: 'text',
					},
				},
				{
					name: 'beastfeedbacks/survey-input',
					attributes: {
						label: 'ご意見',
						tagType: 'textarea',
					},
				},
				{
					name: 'beastfeedbacks/survey-choice',
					attributes: {
						label: '満足度',
						tagType: 'radio',
						items: [ '良い', '普通', '悪い' ],
					},
				},
				{
					name: 'beastfeedbacks/survey-choice',
					attributes: {
						label: '興味のある分野',
						tagType: 'checkbox',
						items: [ 'WordPress', 'React', 'PHP' ],
					},
				},
				{
					name: 'beastfeedbacks/survey-choice',
					attributes: {
						label: '年代',
						tagType: 'select',
						items: [ '20代', '30代', '40代以上' ],
					},
				},
				{
					name: 'core/button',
					attributes: {
						text: 'Submit',
						tagName: 'button',
						type: 'submit',
					},
				},
			],
		} );

		// 投稿を公開してフロントエンドページへ移動
		await publishAndVisit( { editor, page } );

		// フロントエンドでフォームの表示を確認
		const form = getFeedbackForm( page, 'beastfeedbacks_survey_form' );
		await expect( form ).toBeVisible();

		// 1. Text Input（お名前）に入力
		const nameInput = form.locator( 'input[name="お名前"]' );
		await expect( nameInput ).toBeVisible();
		await nameInput.fill( nameValue );

		// 2. Textarea Input（ご意見）に入力
		const commentTextarea = form.locator( 'textarea[name="ご意見"]' );
		await expect( commentTextarea ).toBeVisible();
		await commentTextarea.fill( commentValue );

		// 3. Radio Choice（満足度）で「良い」を選択
		const radioGood = form.locator( 'input[name="満足度"][value="良い"]' );
		await expect( radioGood ).toBeVisible();
		await radioGood.check();
		await expect( radioGood ).toBeChecked();

		// 4. Checkbox Choice（興味のある分野）で「WordPress」と「React」を選択
		const wpCheckbox = form.locator(
			'input[name="興味のある分野[]"][value="WordPress"]'
		);
		const reactCheckbox = form.locator(
			'input[name="興味のある分野[]"][value="React"]'
		);
		await expect( wpCheckbox ).toBeVisible();
		await expect( reactCheckbox ).toBeVisible();
		await wpCheckbox.check();
		await reactCheckbox.check();
		await expect( wpCheckbox ).toBeChecked();
		await expect( reactCheckbox ).toBeChecked();

		// 5. Select Choice（年代）で「30代」を選択
		const selectAge = form.locator( 'select[name="年代"]' );
		await expect( selectAge ).toBeVisible();
		await selectAge.selectOption( '30代' );
		await expect( selectAge ).toHaveValue( '30代' );

		// フォームを送信
		await form.locator( 'button[type="submit"]' ).click();

		// 送信完了メッセージを確認
		await expect(
			page.getByText(
				/Thank you for your responses to the questionnaire/i
			)
		).toBeVisible();

		// 管理画面に移動してデータベースに保存された内容を確認
		await visitFeedbackAdmin( { admin, page } );

		const latestRow = getLatestFeedbackRow( page );
		await expect( latestRow ).toBeVisible();

		// Type が survey として保存されていることを確認
		await expect(
			latestRow.locator( '.column-beastfeedbacks_type' )
		).toHaveText( 'survey' );

		// Response カラムに送信したすべての入力値が保存されていることを確認
		const responseColumn = latestRow.locator(
			'.column-beastfeedbacks_response'
		);
		await expect( responseColumn ).toContainText( 'お名前' );
		await expect( responseColumn ).toContainText( nameValue );
		await expect( responseColumn ).toContainText( 'ご意見' );
		await expect( responseColumn ).toContainText( commentValue );
		await expect( responseColumn ).toContainText( '満足度' );
		await expect( responseColumn ).toContainText( '良い' );
		await expect( responseColumn ).toContainText( '興味のある分野' );
		await expect( responseColumn ).toContainText( 'WordPress' );
		await expect( responseColumn ).toContainText( 'React' );
		await expect( responseColumn ).toContainText( '年代' );
		await expect( responseColumn ).toContainText( '30代' );
	} );
} );
