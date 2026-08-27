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

	/** @test */
	public function extract_post_params_sanitizes_keys_and_handles_nested_arrays(): void {
		$raw_data = array(
			'<script>alert("key")</script>' => 'value1',
			'Field <b>Name</b>'             => 'value2',
			'nested_array'                  => array( 'level1' => array( 'level2' ) ),
		);

		$result = \BeastFeedbacks_Public::get_instance()->extract_post_params( $raw_data );

		$this->assertArrayNotHasKey( '<script>alert("key")</script>', $result );
		$this->assertArrayHasKey( 'Field Name', $result );
		$this->assertSame( 'value2', $result['Field Name'] );
		$this->assertSame( array( 'level1' => '' ), $result['nested_array'] );
	}

	/** @test */
	public function extract_post_params_enforces_max_parameters_and_length_limits(): void {
		$raw_data = array();

		// Oversized key (150 chars).
		$long_key              = str_repeat( 'k', 150 );
		$raw_data[ $long_key ] = 'value_for_long_key';

		// Oversized string value (2500 chars).
		$long_val             = str_repeat( 'v', 2500 );
		$raw_data['long_val'] = $long_val;

		// Oversized array item (2500 chars).
		$raw_data['array_val'] = array( str_repeat( 'a', 2500 ) );

		// Create additional parameters so total parameters exceed parameter count cap (max 50).
		for ( $i = 1; $i <= 60; $i++ ) {
			$raw_data[ "key_{$i}" ] = "val_{$i}";
		}

		$result = \BeastFeedbacks_Public::get_instance()->extract_post_params( $raw_data );

		// Max parameters should be 50.
		$this->assertCount( 50, $result );

		// Test key truncation (max 100 chars).
		$truncated_key = str_repeat( 'k', 100 );
		$this->assertArrayHasKey( $truncated_key, $result );

		// Test value truncation (max 2000 chars).
		$this->assertArrayHasKey( 'long_val', $result );
		$this->assertSame( 2000, strlen( $result['long_val'] ) );

		$this->assertArrayHasKey( 'array_val', $result );
		$this->assertSame( 2000, strlen( $result['array_val'][0] ) );
	}
}
