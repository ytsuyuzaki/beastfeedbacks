import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	insertPublishAndVisit,
	getFeedbackForm,
	visitFeedbackAdmin,
	getLatestFeedbackRow,
} from './helpers';

test.describe( 'Admin Feedback Trash & Untrash', () => {
	test.beforeEach( async ( { admin, editor, page } ) => {
		// テストごとに新規投稿を作成し、Likeブロックを設置・公開・投票してフィードバックデータを作成
		await admin.createNewPost();
		await insertPublishAndVisit( {
			editor,
			page,
			blockName: 'beastfeedbacks/like',
		} );

		const form = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( form ).toBeVisible();
		const likeButton = form.getByRole( 'button', { name: /like/i } );
		await likeButton.click();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();
	} );

	test( 'フィードバック一覧からゴミ箱への移動 (Trash) が正常に行われること', async ( {
		admin,
		page,
	} ) => {
		// 1. 通常の一覧画面へ移動
		await visitFeedbackAdmin( { admin, page } );

		const latestRow = getLatestFeedbackRow( page );
		await expect( latestRow ).toBeVisible();

		// 行ホバー前またはホバー時に「ゴミ箱 (Trash)」リンクが存在することを確認
		await latestRow.hover();
		const trashLink = latestRow.locator( 'a.submitdelete' );
		await expect( trashLink ).toBeVisible();

		// ゴミ箱件数の初期値取得
		const trashCountLink = page.locator( 'ul.subsubsub li.trash a .count' );
		let initialTrashCount = 0;
		if ( ( await trashCountLink.count() ) > 0 ) {
			const countText = await trashCountLink.innerText();
			initialTrashCount = parseInt(
				countText.replace( /[^0-9]/g, '' ),
				10
			);
		}

		// ゴミ箱に移動リンクをクリック
		await trashLink.click();
		await page.waitForLoadState( 'domcontentloaded' );

		// 通常の一覧画面から対象レコードが消える、または通知メッセージが表示されることを確認
		await expect(
			page.locator( '#message' ).filter( { hasText: /trash/i } )
		).toBeVisible();

		// 上部のゴミ箱リンクの件数が増加していることを確認
		const updatedTrashCountLink = page.locator(
			'ul.subsubsub li.trash a .count'
		);
		await expect( updatedTrashCountLink ).toBeVisible();
		const updatedCountText = await updatedTrashCountLink.innerText();
		const updatedTrashCount = parseInt(
			updatedCountText.replace( /[^0-9]/g, '' ),
			10
		);

		expect( updatedTrashCount ).toBe( initialTrashCount + 1 );
	} );

	test( 'ゴミ箱画面からの復元 (Untrash) が正常に行われること', async ( {
		admin,
		page,
	} ) => {
		// 1. 通常の一覧画面へ移動し、ゴミ箱へ移動
		await visitFeedbackAdmin( { admin, page } );
		const latestRow = getLatestFeedbackRow( page );
		await expect( latestRow ).toBeVisible();

		await latestRow.hover();
		const trashLink = latestRow.locator( 'a.submitdelete' );
		await trashLink.click();
		await page.waitForLoadState( 'domcontentloaded' );

		// 2. ゴミ箱画面 (post_status=trash) へ遷移
		await admin.visitAdminPage(
			'edit.php?post_status=trash&post_type=beastfeedbacks'
		);
		await page.waitForLoadState( 'domcontentloaded' );

		const trashedRow = getLatestFeedbackRow( page );
		await expect( trashedRow ).toBeVisible();

		// 3. 対象レコードの「復元 (Restore / Untrash)」リンクをクリック
		await trashedRow.hover();
		const restoreLink = trashedRow.locator( 'a[href*="action=untrash"]' );
		await expect( restoreLink ).toBeVisible();
		await restoreLink.click();
		await page.waitForLoadState( 'domcontentloaded' );

		// 復元完了メッセージを確認
		await expect(
			page
				.locator( '#message' )
				.filter( { hasText: /restored|untrash/i } )
		).toBeVisible();

		// 4. 通常の一覧画面に戻り、復元されたレコードが再度表示されることを確認
		await visitFeedbackAdmin( { admin, page } );
		const restoredRow = getLatestFeedbackRow( page );
		await expect( restoredRow ).toBeVisible();
		await expect(
			restoredRow.locator( '.column-beastfeedbacks_type' )
		).toHaveText( 'like' );
	} );
} );
