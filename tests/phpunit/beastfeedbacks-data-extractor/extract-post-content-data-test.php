<?php
/**
 * Test the extract_post_content_data method of BeastFeedbacks_Data_Extractor.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Data_Extractor_Extract_Post_Content_Data_Test extends BeastFeedbacks_TestCase {

	/**
	 * Setup for tests.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Test valid JSON string with all expected fields.
	 */
	public function test_extract_post_content_data_valid_json() {
		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$json      = wp_json_encode(
			array(
				'type'        => 'like',
				'ip_address'  => '127.0.0.1',
				'user_agent'  => 'Test Agent',
				'post_params' => array( 'foo' => 'bar' ),
			)
		);

		$result = $extractor->extract_post_content_data( $json );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( 'like', $result['type'] );
		$this->assertSame( '127.0.0.1', $result['ip_address'] );
		$this->assertSame( 'Test Agent', $result['user_agent'] );
		$this->assertSame( array( 'foo' => 'bar' ), $result['post_params'] );
		$this->assertTrue( $result['has_ip_address'] );
		$this->assertTrue( $result['has_user_agent'] );
	}

	/**
	 * Test missing fields in valid JSON object.
	 */
	public function test_extract_post_content_data_missing_fields() {
		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$json      = wp_json_encode( array() );
		$result    = $extractor->extract_post_content_data( $json );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '', $result['type'] );
		$this->assertSame( '', $result['ip_address'] );
		$this->assertSame( '', $result['user_agent'] );
		$this->assertSame( array(), $result['post_params'] );
		$this->assertFalse( $result['has_ip_address'] );
		$this->assertFalse( $result['has_user_agent'] );
	}

	/**
	 * Test malformed invalid JSON string.
	 */
	public function test_extract_post_content_data_invalid_json() {
		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$json      = '{"foo": "bar"';
		$result    = $extractor->extract_post_content_data( $json );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( '', $result['type'] );
		$this->assertSame( '', $result['ip_address'] );
		$this->assertSame( '', $result['user_agent'] );
		$this->assertSame( array(), $result['post_params'] );
		$this->assertFalse( $result['has_ip_address'] );
		$this->assertFalse( $result['has_user_agent'] );
	}

	/**
	 * Test valid JSON that decodes to non-array (e.g. string).
	 */
	public function test_extract_post_content_data_non_array_json() {
		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$json      = wp_json_encode( 'string' );
		$result    = $extractor->extract_post_content_data( $json );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( '', $result['type'] );
		$this->assertSame( '', $result['ip_address'] );
		$this->assertSame( '', $result['user_agent'] );
		$this->assertSame( array(), $result['post_params'] );
		$this->assertFalse( $result['has_ip_address'] );
		$this->assertFalse( $result['has_user_agent'] );
	}

	/**
	 * Test cache eviction bounds and behavior.
	 */
	public function test_extract_post_content_data_caching_eviction() {
		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();

		// Use Reflection to access and inspect the static $cache variable.
		$reflection_method = new \ReflectionMethod( $extractor, 'extract_post_content_data' );
		$reflection_method->setAccessible( true );

		// Insert 100 distinct items to fill the cache to its 100-item bound, pushing out any prior items.
		$first_json = '';
		for ( $i = 0; $i < 100; $i++ ) {
			$json = wp_json_encode( array( 'eviction_seed' => "seed_{$i}" ) );
			if ( 0 === $i ) {
				$first_json = $json;
			}
			$extractor->extract_post_content_data( $json );
		}

		// Cache should now have exactly 100 items, starting with $first_json.
		$static_vars = $reflection_method->getStaticVariables();
		$this->assertCount( 100, $static_vars['cache'], 'Cache should contain exactly 100 items before eviction.' );
		$this->assertArrayHasKey( $first_json, $static_vars['cache'], 'Cache should contain the oldest item before eviction.' );

		// Insert the 101st item. This should trigger FIFO eviction of the oldest item ($first_json) and add the new item.
		$json_101 = wp_json_encode( array( 'eviction_seed' => 'seed_100' ) );
		$extractor->extract_post_content_data( $json_101 );

		// Cache should still have 100 items, without $first_json, and with $json_101.
		$static_vars_after = $reflection_method->getStaticVariables();
		$this->assertCount( 100, $static_vars_after['cache'], 'Cache should remain bounded at 100 items.' );
		$this->assertArrayNotHasKey( $first_json, $static_vars_after['cache'], 'Oldest item should be evicted.' );
		$this->assertArrayHasKey( $json_101, $static_vars_after['cache'], 'Cache should contain the newly added item.' );
	}
}
