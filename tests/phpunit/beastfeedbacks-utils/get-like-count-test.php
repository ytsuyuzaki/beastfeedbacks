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
}
