<?php
/**
 * Tests for BeastFeedbacks_Utils::get_like_count().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Utils_Get_Like_Count_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_like_count_returns_zero_when_no_likes(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'parent',
			)
		);

		// ノイズ1: publish でない
		$draft_like = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'draft',
				'post_parent' => $parent_id,
				'post_title'  => 'noise-draft-like',
			)
		);
		add_post_meta( $draft_like, 'beastfeedbacks_type', 'like' );

		// ノイズ2: 親が違う
		$other_parent = $this->create_post(
			array(
				'post_title' => 'other-parent',
			)
		);
		$this->create_like_post( $other_parent );

		// ノイズ3: meta が like ではない
		$this->create_vote_post( $parent_id );

		$count = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 0, $count, 'like が無ければ 0 を返すべき' );
	}

	/** @test */
	public function get_like_count_returns_positive_number_when_likes_exist(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'parent',
			)
		);

		// 条件に合う like を3件
		for ( $i = 0; $i < 3; $i++ ) {
			$this->create_like_post( $parent_id );
		}

		// ノイズ: type が vote
		$this->create_vote_post( $parent_id );

		$count = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 3, $count, 'like が3件なら 3 を返すべき' );
	}

	/** @test */
	public function get_like_count_uses_object_cache(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'parent',
			)
		);

		$this->create_like_post( $parent_id );

		// Initial query populates object cache.
		$count1 = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 1, $count1 );

		// Verify object cache stores the count.
		$cached = wp_cache_get( 'like_count_' . $parent_id, 'beastfeedbacks' );
		$this->assertSame( 1, $cached );

		// Manually override cache to prove get_like_count reads from cache instead of querying DB.
		wp_cache_set( 'like_count_' . $parent_id, 99, 'beastfeedbacks' );
		$count2 = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 99, $count2 );
	}

	/** @test */
	public function saving_like_post_invalidates_cache(): void {
		\BeastFeedbacks_Utils::init();

		$parent_id = $this->create_post(
			array(
				'post_title' => 'parent',
			)
		);

		$this->create_like_post( $parent_id );

		// Prime cache.
		$count1 = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 1, $count1 );

		// Adding another like post triggers save_post_beastfeedbacks and invalidates cache.
		$this->create_like_post( $parent_id );

		$count2 = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 2, $count2 );
	}

	/** @test */
	public function deleting_like_post_invalidates_cache(): void {
		\BeastFeedbacks_Utils::init();

		$parent_id = $this->create_post(
			array(
				'post_title' => 'parent',
			)
		);

		$like_id = $this->create_like_post( $parent_id );

		// Prime cache.
		$count1 = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 1, $count1 );

		// Delete like post.
		wp_delete_post( $like_id, true );

		$count2 = \BeastFeedbacks_Utils::get_like_count( $parent_id );
		$this->assertSame( 0, $count2 );
	}
}
