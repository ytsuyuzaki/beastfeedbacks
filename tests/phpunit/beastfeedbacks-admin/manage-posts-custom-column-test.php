<?php
/**
 * Tests for BeastFeedbacks_Admin::manage_posts_custom_column().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Manage_Posts_Custom_Column_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/** @test */
	public function manage_posts_custom_column_returns_early_for_unsupported_column(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'unsupported_column', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function manage_posts_custom_column_handles_unknown_column(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();
		ob_start();
		$admin->manage_posts_custom_column( 'unknown_column', 123 );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_date_for_beastfeedbacks_date(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_date'   => '2025-01-15 10:20:30',
			)
		);

		$post            = get_post( $post_id );
		$GLOBALS['post'] = $post;

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_date', $post_id );
		$output = ob_get_clean();

		$expected_date = date_i18n( 'Y/m/d', get_the_time( 'U' ) );
		$this->assertSame( $expected_date, $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_type_for_beastfeedbacks_type(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
			)
		);
		add_post_meta( $post_id, 'beastfeedbacks_type', 'survey' );

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_type', $post_id );
		$output = ob_get_clean();

		$this->assertSame( 'survey', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_source_link_for_beastfeedbacks_source(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Parent post
		$parent_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'parent post',
			)
		);

		// Child feedback post
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
			)
		);

		// Test when $GLOBALS['post'] is set (list table row context)
		$GLOBALS['post'] = get_post( $post_id );

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', $post_id );
		$output = ob_get_clean();

		$form_url   = get_permalink( $parent_id );
		$parsed_url = wp_parse_url( $form_url );
		$expected   = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $form_url ),
			esc_html( $parsed_url['path'] )
		);

		$this->assertSame( $expected, $output );

		// Test when $GLOBALS['post'] is not set or mismatched
		unset( $GLOBALS['post'] );

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', $post_id );
		$output_no_global = ob_get_clean();

		$this->assertSame( $expected, $output_no_global );
	}

	/** @test */
	public function manage_posts_custom_column_returns_empty_when_source_post_does_not_exist(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Non-existent post ID
		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', 999999 );
		$no_parent_output = ob_get_clean();

		$this->assertSame( '', $no_parent_output );
	}

	/** @test */
	public function manage_posts_custom_column_returns_empty_when_invalid_json_content(): void {
		$admin           = \BeastFeedbacks_Admin::get_instance();
		$invalid_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => 'invalid json content',
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $invalid_post_id );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_response_data_for_vote(): void {
		$admin        = \BeastFeedbacks_Admin::get_instance();
		$vote_content = array(
			'type'        => 'vote',
			'post_params' => array(
				'selected' => 'Option 1',
			),
			'ip_address'  => '192.168.1.1',
			'user_agent'  => 'Test User Agent',
		);
		$vote_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $vote_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $vote_post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Select', $output );
		$this->assertStringContainsString( 'Option 1', $output );
		$this->assertStringContainsString( 'IP_Address', $output );
		$this->assertStringContainsString( '192.168.1.1', $output );
		$this->assertStringContainsString( 'UserAgent', $output );
		$this->assertStringContainsString( 'Test User Agent', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_response_data_for_survey(): void {
		$admin          = \BeastFeedbacks_Admin::get_instance();
		$survey_content = array(
			'type'        => 'survey',
			'post_params' => array(
				'Question 1' => 'Answer 1',
				'Question 2' => array( 'Choice A', 'Choice B' ),
			),
			'ip_address'  => '10.0.0.1',
		);
		$survey_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $survey_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $survey_post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Question 1', $output );
		$this->assertStringContainsString( 'Answer 1', $output );
		$this->assertStringContainsString( 'Question 2', $output );
		$this->assertStringContainsString( 'Choice A', $output );
		$this->assertStringContainsString( 'Choice B', $output );
		$this->assertStringContainsString( 'Choice A<br />', $output );
		$this->assertStringContainsString( '10.0.0.1', $output );
		$this->assertStringNotContainsString( 'UserAgent', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_response_data_for_like(): void {
		$admin        = \BeastFeedbacks_Admin::get_instance();
		$like_content = array(
			'type'       => 'like',
			'ip_address' => '172.16.0.1',
			'user_agent' => 'Like Test Agent',
		);
		$like_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $like_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $like_post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'IP_Address', $output );
		$this->assertStringContainsString( '172.16.0.1', $output );
		$this->assertStringContainsString( 'UserAgent', $output );
		$this->assertStringContainsString( 'Like Test Agent', $output );
		$this->assertStringNotContainsString( 'Select', $output );
	}

	/** @test */
	public function manage_posts_custom_column_uses_global_post_when_matching(): void {
		$admin        = \BeastFeedbacks_Admin::get_instance();
		$like_content = array(
			'type'       => 'like',
			'ip_address' => '127.0.0.1',
		);
		$post_id      = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $like_content ),
			)
		);

		$post            = get_post( $post_id );
		$GLOBALS['post'] = $post;

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'IP_Address', $output );
		$this->assertStringContainsString( '127.0.0.1', $output );
	}

	/** @test */
	public function manage_posts_custom_column_handles_non_existent_post_gracefully(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', 9999999 );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function the_posts_filter_primes_parent_post_caches(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$parent_id1 = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		$parent_id2 = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$child_id1 = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id1,
			)
		);
		$child_id2 = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id2,
			)
		);

		clean_post_cache( $parent_id1 );
		clean_post_cache( $parent_id2 );

		$this->assertFalse( wp_cache_get( $parent_id1, 'posts' ) );
		$this->assertFalse( wp_cache_get( $parent_id2, 'posts' ) );

		set_current_screen( 'edit-beastfeedbacks' );

		$query = new WP_Query(
			array(
				'post_type' => 'beastfeedbacks',
			)
		);

		add_post_meta( $child_id1, 'beastfeedbacks_type', 'survey' );
		add_post_meta( $child_id2, 'beastfeedbacks_type', 'vote' );

		wp_cache_delete( $child_id1, 'post_meta' );
		wp_cache_delete( $child_id2, 'post_meta' );
		$this->assertFalse( wp_cache_get( $child_id1, 'post_meta' ) );
		$this->assertFalse( wp_cache_get( $child_id2, 'post_meta' ) );

		$posts = array( get_post( $child_id1 ), get_post( $child_id2 ) );
		$res   = $admin->prime_parent_post_caches( $posts, $query );

		$this->assertSame( $posts, $res );
		$this->assertNotFalse( wp_cache_get( $parent_id1, 'posts' ) );
		$this->assertNotFalse( wp_cache_get( $parent_id2, 'posts' ) );
		$this->assertNotFalse( wp_cache_get( $child_id1, 'post_meta' ) );
		$this->assertNotFalse( wp_cache_get( $child_id2, 'post_meta' ) );

		clean_post_cache( $parent_id1 );
		clean_post_cache( $parent_id2 );
		wp_cache_delete( $child_id1, 'post_meta' );
		wp_cache_delete( $child_id2, 'post_meta' );
		set_current_screen( 'front' );

		$admin->prime_parent_post_caches( $posts, $query );
		$this->assertFalse( wp_cache_get( $parent_id1, 'posts' ) );
		$this->assertFalse( wp_cache_get( $child_id1, 'post_meta' ) );
	}
}
