<?php
/**
 * Tests for BeastFeedbacks_Public::extract_post_params().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Extract_Post_Params_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function extract_post_params_filters_and_sanitizes_input(): void {
		$raw_data = array(
			'id'                  => '123',
			'beastfeedbacks_type' => 'survey',
			'action'              => 'register_beastfeedbacks_form',
			'_wp_http_referer'    => '/test',
			'_wpnonce'            => 'nonce123',
			'comment'             => ' Great service! <script>alert(1)</script> ',
			'tags'                => array( 'fast', 'helpful<script>' ),
		);

		$result = \BeastFeedbacks_Public::get_instance()->extract_post_params( $raw_data );

		$this->assertArrayNotHasKey( 'id', $result );
		$this->assertArrayNotHasKey( 'beastfeedbacks_type', $result );
		$this->assertArrayNotHasKey( 'action', $result );
		$this->assertArrayNotHasKey( '_wp_http_referer', $result );
		$this->assertArrayNotHasKey( '_wpnonce', $result );
		$this->assertSame( 'Great service!', $result['comment'] );
		$this->assertSame( array( 'fast', 'helpful' ), $result['tags'] );
	}
}
