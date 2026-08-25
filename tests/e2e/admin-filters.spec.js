import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { getFeedbackForm, visitFeedbackAdmin } from './helpers';

test.describe( '管理画面 一覧フィルター絞り込み機能', () => {
	let postAId;
	let postBId;
	let hrefA;
	let hrefB;

	test.beforeEach( async ( { admin, editor, page } ) => {
		// --- 投稿A の作成 ---
		await admin.createNewPost( { title: 'Post A' } );
		postAId = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getCurrentPostId()
		);
		await editor.insertBlock( { name: 'beastfeedbacks/like' } );
		await editor.insertBlock( { name: 'beastfeedbacks/vote' } );
		await editor.publishPost();

		// 投稿A のフロントエンドページに移動
		const viewPostLinkA = page
			.getByRole( 'link', { name: /view post/i } )
			.first();
		await expect( viewPostLinkA ).toBeVisible();
		hrefA = await viewPostLinkA.getAttribute( 'href' );
		await page.goto( hrefA );
		await page.waitForLoadState( 'domcontentloaded' );

		// 投稿A で Like フィードバック送信
		const likeFormA = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( likeFormA ).toBeVisible();
		await likeFormA.getByRole( 'button', { name: /like/i } ).click();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();

		// 投稿A で Vote フィードバック送信
		const voteFormA = getFeedbackForm( page, 'beastfeedbacks_vote_form' );
		await expect( voteFormA ).toBeVisible();
		await voteFormA.getByRole( 'button', { name: /yes/i } ).click();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();

		// --- 投稿B の作成 ---
		await admin.createNewPost( { title: 'Post B' } );
		postBId = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getCurrentPostId()
		);
		await editor.insertBlock( { name: 'beastfeedbacks/like' } );
		await editor.publishPost();

		// 投稿B のフロントエンドページに移動
		const viewPostLinkB = page
			.getByRole( 'link', { name: /view post/i } )
			.first();
		await expect( viewPostLinkB ).toBeVisible();
		hrefB = await viewPostLinkB.getAttribute( 'href' );
		await page.goto( hrefB );
		await page.waitForLoadState( 'domcontentloaded' );

		// 投稿B で Like フィードバック送信
		const likeFormB = getFeedbackForm( page, 'beastfeedbacks_like_form' );
		await expect( likeFormB ).toBeVisible();
		await likeFormB.getByRole( 'button', { name: /like/i } ).click();
		await expect(
			page.getByText( /Thank you for the vote/i )
		).toBeVisible();
	} );

	test( 'タイプ別フィルター絞り込み', async ( { admin, page } ) => {
		await visitFeedbackAdmin( { admin, page } );

		const typeFilter = page.locator(
			'select[name="beastfeedbacks_type"], select[name="type"]'
		);
		const submitButton = page.locator( '#post-query-submit' );

		// 1. like フィルター選択
		await typeFilter.selectOption( 'like' );
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
			submitButton.click(),
		] );

		// like タイプのみが表示され、vote や survey が表示されないこと
		const likeRows = page.locator( '#the-list > tr' );
		const countLike = await likeRows.count();
		expect( countLike ).toBeGreaterThan( 0 );

		for ( let i = 0; i < countLike; i++ ) {
			const typeText = await likeRows
				.nth( i )
				.locator( '.column-beastfeedbacks_type' )
				.textContent();
			expect( typeText.trim() ).toBe( 'like' );
			expect( typeText.trim() ).not.toBe( 'vote' );
			expect( typeText.trim() ).not.toBe( 'survey' );
		}

		// 2. vote フィルター選択
		await typeFilter.selectOption( 'vote' );
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
			submitButton.click(),
		] );

		// vote タイプのみが表示されること
		const voteRows = page.locator( '#the-list > tr' );
		const countVote = await voteRows.count();
		expect( countVote ).toBeGreaterThan( 0 );

		for ( let i = 0; i < countVote; i++ ) {
			const typeText = await voteRows
				.nth( i )
				.locator( '.column-beastfeedbacks_type' )
				.textContent();
			expect( typeText.trim() ).toBe( 'vote' );
			expect( typeText.trim() ).not.toBe( 'like' );
			expect( typeText.trim() ).not.toBe( 'survey' );
		}
	} );

	test( '送信元URLフィルター絞り込み', async ( { admin, page } ) => {
		await visitFeedbackAdmin( { admin, page } );

		const sourceFilter = page.locator(
			'select[name="beastfeedbacks_parent_id"], select[name="beastfeedbacks_source"], select[name="source"]'
		);
		const submitButton = page.locator( '#post-query-submit' );

		// 特定の投稿（投稿A）の送信元フィルターを選択
		await sourceFilter.selectOption( String( postAId ) );
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
			submitButton.click(),
		] );

		// 投稿A に紐づくフィードバックのみが表示されること
		const rows = page.locator( '#the-list > tr' );
		const count = await rows.count();
		expect( count ).toBeGreaterThan( 0 );

		for ( let i = 0; i < count; i++ ) {
			const sourceLink = rows
				.nth( i )
				.locator( '.column-beastfeedbacks_source a[target="_blank"]' );
			await expect( sourceLink ).toBeVisible();
			const linkHref = await sourceLink.getAttribute( 'href' );
			expect( linkHref ).toBe( hrefA );
			expect( linkHref ).not.toBe( hrefB );
			expect( postBId ).toBeDefined();
		}
	} );

	test( '複合フィルター絞り込み', async ( { admin, page } ) => {
		await visitFeedbackAdmin( { admin, page } );

		const typeFilter = page.locator(
			'select[name="beastfeedbacks_type"], select[name="type"]'
		);
		const sourceFilter = page.locator(
			'select[name="beastfeedbacks_parent_id"], select[name="beastfeedbacks_source"], select[name="source"]'
		);
		const submitButton = page.locator( '#post-query-submit' );

		// タイプ 'like' と 送信元 '投稿A' を指定
		await typeFilter.selectOption( 'like' );
		await sourceFilter.selectOption( String( postAId ) );
		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
			submitButton.click(),
		] );

		// 両方の条件に合致するレコードのみが表示されること
		const rows = page.locator( '#the-list > tr' );
		const count = await rows.count();
		expect( count ).toBe( 1 );

		const typeText = await rows
			.first()
			.locator( '.column-beastfeedbacks_type' )
			.textContent();
		expect( typeText.trim() ).toBe( 'like' );

		const sourceLink = rows
			.first()
			.locator( '.column-beastfeedbacks_source a[target="_blank"]' );
		await expect( sourceLink ).toBeVisible();
		const linkHref = await sourceLink.getAttribute( 'href' );
		expect( linkHref ).toBe( hrefA );
	} );
} );
