<?php
/**
 * Tests for BeastFeedbacks_Admin::get_export_posts().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Get_Export_Posts_Test extends BeastFeedbacks_TestCase {

	/**
	 * Clean up request globals after each test.
	 */
	protected function tear_down(): void {
		$_REQUEST = array();
		$_GET     = array();
		$_POST    = array();
		parent::tear_down();
	}

	/** @test */
	public function get_export_posts_returns_only_published_beastfeedbacks_posts(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Published beastfeedbacks post.
		$published_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_title'  => 'Published Feedback',
			)
		);

		// Draft beastfeedbacks post.
		$draft_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'draft',
				'post_title'  => 'Draft Feedback',
			)
		);

		// Trashed beastfeedbacks post.
		$trash_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Trashed Feedback',
			)
		);

		// Regular page post.
		$page_id = $this->create_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Regular Page',
			)
		);

		$posts    = $admin->get_export_posts();
		$post_ids = wp_list_pluck( $posts, 'ID' );

		$this->assertContains( $published_id, $post_ids );
		$this->assertNotContains( $draft_id, $post_ids );
		$this->assertNotContains( $trash_id, $post_ids );
		$this->assertNotContains( $page_id, $post_ids );
	}

	/** @test */
	public function get_export_posts_returns_posts_in_ascending_order(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post1_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_date'   => '2025-01-01 10:00:00',
			)
		);

		$post2_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_date'   => '2025-01-02 10:00:00',
			)
		);

		$posts    = $admin->get_export_posts();
		$post_ids = wp_list_pluck( $posts, 'ID' );

		$index1 = array_search( $post1_id, $post_ids, true );
		$index2 = array_search( $post2_id, $post_ids, true );

		$this->assertNotFalse( $index1 );
		$this->assertNotFalse( $index2 );
		$this->assertLessThan( $index2, $index1 );
	}

	/** @test */
	public function get_export_posts_respects_type_filter(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->init();

		$survey_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( array( 'type' => 'survey' ) ),
			)
		);
		add_post_meta( $survey_id, 'beastfeedbacks_type', 'survey' );

		$vote_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( array( 'type' => 'vote' ) ),
			)
		);
		add_post_meta( $vote_id, 'beastfeedbacks_type', 'vote' );

		$nonce               = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$_REQUEST            = array(
			'_wpnonce'           => $nonce,
			'beastfeedbacks_type' => 'survey',
		);

		$posts    = $admin->get_export_posts();
		$post_ids = wp_list_pluck( $posts, 'ID' );

		$this->assertContains( $survey_id, $post_ids );
		$this->assertNotContains( $vote_id, $post_ids );
	}

	/** @test */
	public function get_export_posts_respects_source_filter(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->init();

		$parent1_id = $this->create_post( array( 'post_type' => 'page' ) );
		$parent2_id = $this->create_post( array( 'post_type' => 'page' ) );

		$post1_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent1_id,
			)
		);

		$post2_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent2_id,
			)
		);

		$nonce               = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$_REQUEST            = array(
			'_wpnonce'                => $nonce,
			'beastfeedbacks_parent_id' => $parent1_id,
		);

		$posts    = $admin->get_export_posts();
		$post_ids = wp_list_pluck( $posts, 'ID' );

		$this->assertContains( $post1_id, $post_ids );
		$this->assertNotContains( $post2_id, $post_ids );
	}
}
