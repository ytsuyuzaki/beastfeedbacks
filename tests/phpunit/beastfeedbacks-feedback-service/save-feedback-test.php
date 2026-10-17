<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service::save_feedback().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Save_Feedback_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function test_save_feedback_creates_post_correctly(): void {
		$service = \BeastFeedbacks_Feedback_Service::get_instance();

		$post_id = $this->create_post(
			array(
				'post_title' => 'parent post',
			)
		);

		$type    = 'survey';
		$title   = 'Test Feedback Title';
		$time    = '2023-01-01 12:00:00';
		$content = wp_json_encode( array( 'some' => 'data' ) );

		$feedback_id = $service->save_feedback( $post_id, $type, $title, $time, $content );

		$this->assertIsInt( $feedback_id );
		$this->assertGreaterThan( 0, $feedback_id );

		$this->created_ids[] = $feedback_id;

		$feedback_post = get_post( $feedback_id );

		$this->assertSame( 'beastfeedbacks', $feedback_post->post_type );
		// when using a past date, WP might adjust status depending on various things, but wp_insert_post specifies 'publish'.
		$this->assertSame( 'publish', $feedback_post->post_status );
		$this->assertSame( (int) $post_id, (int) $feedback_post->post_parent );
		$this->assertSame( $title, $feedback_post->post_title );
		$this->assertSame( $content, $feedback_post->post_content );
		$this->assertSame( '2023-01-01 12:00:00', $feedback_post->post_date );

		$saved_type = get_post_meta( $feedback_id, 'beastfeedbacks_type', true );
		$this->assertSame( 'survey', $saved_type );
	}

	/** @test */
	public function test_save_feedback_sanitizes_title(): void {
		$service = \BeastFeedbacks_Feedback_Service::get_instance();

		$post_id = $this->create_post();

		$type    = 'vote';
		$title   = 'Test <script>alert("xss")</script> Title O\'Connor';
		$time    = current_time( 'mysql' );
		$content = '{}';

		$feedback_id = $service->save_feedback( $post_id, $type, $title, $time, $content );

		$this->created_ids[] = $feedback_id;

		$feedback_post = get_post( $feedback_id );

		$this->assertSame( 'Test alert("xss") Title O\'Connor', $feedback_post->post_title );
	}

	/** @test */
	public function test_save_feedback_generates_md5_post_name(): void {
		$service = \BeastFeedbacks_Feedback_Service::get_instance();

		$post_id = $this->create_post();

		$type    = 'like';
		$title   = 'Unique Title For MD5';
		$time    = current_time( 'mysql' );
		$content = '{}';

		$feedback_id = $service->save_feedback( $post_id, $type, $title, $time, $content );

		$this->created_ids[] = $feedback_id;

		$feedback_post = get_post( $feedback_id );

		$this->assertSame( md5( $title ), $feedback_post->post_name );
	}
}
