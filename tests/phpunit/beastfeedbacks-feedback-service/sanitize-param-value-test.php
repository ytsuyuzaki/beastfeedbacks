<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service::sanitize_param_value().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Sanitize_Param_Value_Test extends BeastFeedbacks_TestCase {

	/**
	 * Service instance.
	 *
	 * @var BeastFeedbacks_Feedback_Service
	 */
	private $service;

	/**
	 * Set up test environment.
	 */
	protected function set_up(): void {
		parent::set_up();
		$this->service = BeastFeedbacks_Feedback_Service::get_instance();
	}

	/** @test */
	public function it_preserves_normal_strings(): void {
		$value = 'Normal string value';
		$this->assertSame( $value, $this->service->sanitize_param_value( $value ) );
	}

	/** @test */
	public function it_unslashes_strings(): void {
		$value = 'O\'Reilly';
		$slashed = wp_slash( $value );
		$this->assertSame( $value, $this->service->sanitize_param_value( $slashed ) );
	}

	/** @test */
	public function it_strips_control_characters_except_allowed(): void {
		// \x00 (NUL), \x08 (Backspace), \x0B (Vertical Tab) should be stripped.
		// \x09 (\t), \x0A (\n), \x0D (\r) are allowed but sanitize_text_field might strip/convert them.
		// Wait, sanitize_text_field strips line breaks and tabs?
		// Actually sanitize_text_field replaces \r\n with \n, and then \n with space, etc.
		// But let's check what preg_replace strips.
		$value = "Hello\x00World\x08!\x0B";
		$expected = 'HelloWorld!'; // Since sanitize_text_field will keep normal characters.
		$this->assertSame( $expected, $this->service->sanitize_param_value( $value ) );
	}

	/** @test */
	public function it_truncates_strings_exceeding_2000_characters(): void {
		$value = str_repeat( 'a', 2500 );
		$expected = str_repeat( 'a', 2000 );
		$this->assertSame( $expected, $this->service->sanitize_param_value( $value ) );
	}

	/** @test */
	public function it_returns_empty_string_for_objects(): void {
		$obj = new stdClass();
		$obj->foo = 'bar';
		$this->assertSame( '', $this->service->sanitize_param_value( $obj ) );
	}

	/** @test */
	public function it_sanitizes_array_keys_and_values(): void {
		$input = array(
			"key\x001" => 'value1',
			'key2'     => 'O\'Reilly',
		);
		$expected = array(
			'key1' => 'value1',
			'key2' => 'O\'Reilly',
		);
		$this->assertSame( $expected, $this->service->sanitize_param_value( $input ) );
	}

	/** @test */
	public function it_truncates_arrays_with_more_than_50_items(): void {
		$input = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$input[ 'key' . $i ] = 'value' . $i;
		}

		$result = $this->service->sanitize_param_value( $input );

		$this->assertIsArray( $result );
		$this->assertCount( 50, $result );

		// The 50th item should be present, but the 51st should not.
		$this->assertArrayHasKey( 'key49', $result );
		$this->assertArrayNotHasKey( 'key50', $result );
	}

	/** @test */
	public function it_rejects_arrays_containing_nested_arrays_or_objects(): void {
		// Deep parsing DoS prevention: returns empty string if an item is array/object.
		$input_with_array = array(
			'key1' => 'value1',
			'key2' => array( 'nested' => 'value' ),
		);
		$this->assertSame( '', $this->service->sanitize_param_value( $input_with_array ) );

		$input_with_object = array(
			'key1' => 'value1',
			'key2' => new stdClass(),
		);
		$this->assertSame( '', $this->service->sanitize_param_value( $input_with_object ) );
	}

	/** @test */
	public function it_skips_array_items_with_empty_keys_after_sanitization(): void {
		$input = array(
			'valid_key' => 'value1',
			"\x00\x08"  => 'value2', // Key becomes empty after sanitization.
			''          => 'value3', // Empty string key.
		);

		$expected = array(
			'valid_key' => 'value1',
		);

		// Note: sanitize_text_field("\x00\x08") returns an empty string.
		$this->assertSame( $expected, $this->service->sanitize_param_value( $input ) );
	}

	/** @test */
	public function it_truncates_array_keys_exceeding_100_characters(): void {
		$long_key = str_repeat( 'k', 150 );
		$input = array(
			$long_key => 'value1',
		);
		$expected_key = str_repeat( 'k', 100 );
		$expected = array(
			$expected_key => 'value1',
		);

		$this->assertSame( $expected, $this->service->sanitize_param_value( $input ) );
	}

	/** @test */
	public function it_decodes_special_characters_in_values(): void {
		// sanitize_text_field converts & to &amp;, but wp_specialchars_decode with ENT_QUOTES brings it back.
		// Wait, let's see what happens to HTML tags.
		// sanitize_text_field strips HTML tags.
		$value = '<strong>Hello</strong> & World "test" \'test\'';

		// The exact behavior depends on wp_specialchars_decode( sanitize_text_field( $value ), ENT_QUOTES );
		$expected = 'Hello & World "test" \'test\'';

		$this->assertSame( $expected, $this->service->sanitize_param_value( $value ) );
	}
}
