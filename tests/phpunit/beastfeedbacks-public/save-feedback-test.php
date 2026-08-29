<?php
/**
 * Tests for BeastFeedbacks_Public::save_feedback().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Save_Feedback_Test extends BeastFeedbacks_TestCase {

	/**
	 * Verify that save_feedback creates a feedback post with correct attributes and meta data.
	 */
	public function test_save_feedback_creates_post_with_correct_attributes(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Post',
			)
		);

		$instance = \BeastFeedbacks_Public::get_instance();
		$type     = 'like';
		$time     = current_time( 'mysql' );
		$title    = "127.0.0.1 - {$time}";
		$content  = '{"user_agent":"TestAgent","ip_address":"127.0.0.1","type":"like","post_params":[]}';

		$saved_id = $instance->save_feedback( $parent_id, $type, $title, $time, $content );

		$this->assertIsInt( $saved_id );
		$this->assertGreaterThan( 0, $saved_id );
		$this->created_ids[] = $saved_id;

		$post = get_post( $saved_id );
		$this->assertNotNull( $post );
		$this->assertSame( 'beastfeedbacks', $post->post_type );
		$this->assertSame( 'publish', $post->post_status );
		$this->assertSame( $parent_id, $post->post_parent );
		$this->assertSame( $title, $post->post_title );
		$this->assertSame( md5( $title ), $post->post_name );
		$this->assertSame( $content, $post->post_content );
		$this->assertSame( $time, $post->post_date );
		$this->assertSame( $type, get_post_meta( $saved_id, 'beastfeedbacks_type', true ) );
	}

	/**
	 * Verify that save_feedback strips HTML tags from title and calculates post_name from title.
	 */
	public function test_save_feedback_sanitizes_title(): void {
		$parent_id = $this->create_post();
		$instance  = \BeastFeedbacks_Public::get_instance();

		$raw_title = '127.0.0.1 - <script>alert("xss")</script> <b>Title</b>';
		$time      = current_time( 'mysql' );
		$content   = '{"test":"data"}';

		$saved_id = $instance->save_feedback( $parent_id, 'survey', $raw_title, $time, $content );

		$this->assertIsInt( $saved_id );
		$this->assertGreaterThan( 0, $saved_id );
		$this->created_ids[] = $saved_id;

		$post = get_post( $saved_id );
		$this->assertNotNull( $post );
		$this->assertSame( '127.0.0.1 - alert("xss") Title', $post->post_title );
		$this->assertSame( md5( $raw_title ), $post->post_name );
	}

	/**
	 * Verify that save_feedback returns error or failure value when post insertion fails.
	 */
	public function test_save_feedback_returns_failure_on_insert_error(): void {
		$parent_id = $this->create_post();
		$instance  = \BeastFeedbacks_Public::get_instance();

		$fail_insert = static function () {
			return true;
		};
		add_filter( 'wp_insert_post_empty_content', $fail_insert );

		try {
			$saved = $instance->save_feedback( $parent_id, 'like', 'Title', current_time( 'mysql' ), '' );
		} finally {
			remove_filter( 'wp_insert_post_empty_content', $fail_insert );
		}

		$this->assertTrue( is_wp_error( $saved ) || 0 === $saved || false === $saved );
	}
}
