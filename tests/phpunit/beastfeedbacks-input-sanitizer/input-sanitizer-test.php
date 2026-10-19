<?php

class BeastFeedbacks_Input_Sanitizer_Test extends \BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Input_Sanitizer
	 */
	private $sanitizer;

	public function set_up(): void {
		parent::set_up();
		$this->sanitizer = \BeastFeedbacks_Input_Sanitizer::get_instance();
	}

	public function test_extract_post_params_filters_ignore_keys(): void {
		$post_data = array(
			'id'                  => '123',
			'beastfeedbacks_type' => 'survey',
			'action'              => 'test_action',
			'_wp_http_referer'    => '/test',
			'_wpnonce'            => '12345',
			'valid_key'           => 'valid_value',
		);
		$ignore_keys = array(
			'id'                  => true,
			'beastfeedbacks_type' => true,
			'action'              => true,
			'_wp_http_referer'    => true,
			'_wpnonce'            => true,
		);

		$result = $this->sanitizer->extract_post_params( $post_data, $ignore_keys );

		$this->assertArrayHasKey( 'valid_key', $result );
		$this->assertArrayNotHasKey( 'id', $result );
		$this->assertArrayNotHasKey( 'beastfeedbacks_type', $result );
		$this->assertArrayNotHasKey( 'action', $result );
		$this->assertArrayNotHasKey( '_wp_http_referer', $result );
		$this->assertArrayNotHasKey( '_wpnonce', $result );
	}

	public function test_extract_post_params_truncates_and_sanitizes_keys(): void {
		$very_long_key = str_repeat( 'a', 250 );
		$expected_key  = str_repeat( 'a', 100 ); // Max 100 chars after truncation

		$post_data = array(
			$very_long_key          => 'value1',
			"key_with_\x00_control" => 'value2',
			'key_with_<script>'     => 'value3',
		);

		$result = $this->sanitizer->extract_post_params( $post_data );

		$this->assertArrayHasKey( $expected_key, $result );
		$this->assertArrayHasKey( 'key_with__control', $result );
		$this->assertArrayHasKey( 'key_with_', $result );
	}

	public function test_extract_post_params_enforces_max_params_limit(): void {
		$post_data = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$post_data[ "key_$i" ] = "value_$i";
		}

		$result = $this->sanitizer->extract_post_params( $post_data );

		$this->assertCount( 50, $result ); // Default max is 50
	}

	public function test_sanitize_param_value_handles_strings(): void {
		$this->assertSame( 'clean string', $this->sanitizer->sanitize_param_value( 'clean string' ) );
		$this->assertSame( 'clean', $this->sanitizer->sanitize_param_value( "cl\x00ean" ) );
		$this->assertSame( 'clean', $this->sanitizer->sanitize_param_value( 'clean<script>alert(1)</script>' ) );
		$this->assertSame( 'line1 line2', $this->sanitizer->sanitize_param_value( "line1\nline2" ) ); // sanitize_text_field replaces newline with space
	}

	public function test_sanitize_param_value_truncates_long_strings(): void {
		$long_string = str_repeat( 'a', 2500 );
		$result      = $this->sanitizer->sanitize_param_value( $long_string );
		$this->assertSame( 2000, mb_strlen( $result ) );
	}

	public function test_sanitize_param_value_handles_arrays(): void {
		$input = array(
			'key1' => 'value1',
			'key2' => array( 'nested1', 'nested2' ),
			'key3' => (object) array( 'prop' => 'val' ), // Objects inside arrays should be rejected
		);

		$result = $this->sanitizer->sanitize_param_value( $input );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'key1', $result );
		$this->assertSame( 'value1', $result['key1'] );

		// Nested arrays/objects are rejected and return empty string
		$this->assertArrayHasKey( 'key2', $result );
		$this->assertSame( '', $result['key2'] );

		$this->assertArrayHasKey( 'key3', $result );
		$this->assertSame( '', $result['key3'] );
	}

	public function test_sanitize_param_value_enforces_array_item_limit(): void {
		$input = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$input[ "key_$i" ] = "value_$i";
		}

		$result = $this->sanitizer->sanitize_param_value( $input );

		$this->assertIsArray( $result );
		$this->assertCount( 50, $result );
	}

	public function test_sanitize_param_value_rejects_objects(): void {
		$obj = (object) array( 'key' => 'value' );
		$this->assertSame( '', $this->sanitizer->sanitize_param_value( $obj ) );
	}
}
