<?php
/**
 * Tests for BeastFeedbacks_Public::format_feedback_content().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Format_Feedback_Content_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function format_feedback_content_encodes_json_correctly(): void {
		$content = \BeastFeedbacks_Public::get_instance()->format_feedback_content(
			'TestUA',
			'127.0.0.1',
			'survey',
			array( 'key' => 'val' )
		);

		$decoded = json_decode( stripslashes( $content ), true );
		$this->assertSame( 'TestUA', $decoded['user_agent'] );
		$this->assertSame( '127.0.0.1', $decoded['ip_address'] );
		$this->assertSame( 'survey', $decoded['type'] );
		$this->assertSame( array( 'key' => 'val' ), $decoded['post_params'] );
	}

	/** @test */
	public function format_feedback_content_preserves_special_characters_in_json(): void {
		$params  = array(
			'comparison' => '10 < 20 & 30 > 5',
			'formula'    => 'x < y',
		);
		$content = \BeastFeedbacks_Public::get_instance()->format_feedback_content(
			'TestUA',
			'127.0.0.1',
			'survey',
			$params
		);

		$decoded = json_decode( stripslashes( $content ), true );
		$this->assertIsArray( $decoded );
		$this->assertSame( $params, $decoded['post_params'] );
	}
}
