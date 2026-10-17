<?php
/**
 * Test the extract_post_content_data method of BeastFeedbacks_Admin.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Extract_Post_Content_Data_Test extends BeastFeedbacks_TestCase {

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
		$admin = \BeastFeedbacks_Admin::get_instance();
		$json  = wp_json_encode(
			array(
				'type'        => 'like',
				'ip_address'  => '127.0.0.1',
				'user_agent'  => 'Test Agent',
				'post_params' => array( 'foo' => 'bar' ),
			)
		);

		$result = $admin->extract_post_content_data( $json );

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
		$admin  = \BeastFeedbacks_Admin::get_instance();
		$json   = wp_json_encode( array() );
		$result = $admin->extract_post_content_data( $json );

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
		$admin  = \BeastFeedbacks_Admin::get_instance();
		$json   = '{"foo": "bar"';
		$result = $admin->extract_post_content_data( $json );

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
		$admin  = \BeastFeedbacks_Admin::get_instance();
		$json   = wp_json_encode( 'string' );
		$result = $admin->extract_post_content_data( $json );

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
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Use Reflection to access and inspect the static $cache variable
		$reflection_method = new \ReflectionMethod( $admin, 'extract_post_content_data' );
		$reflection_method->setAccessible( true );

		// Force an eviction first to ensure a clean slate. We do this by continuously
		// adding new items until the cache drops down to 1 item (the one just added after eviction).
		$counter = 0;
		while ( true ) {
			$json = wp_json_encode( array( 'eviction_seed' => $counter ) );
			$admin->extract_post_content_data( $json );
			$static_vars = $reflection_method->getStaticVariables();
			if ( count( $static_vars['cache'] ) === 1 ) {
				break;
			}
			$counter++;
			if ( $counter > 200 ) {
				$this->fail( 'Cache failed to evict during setup phase.' );
			}
		}

		// At this point, the cache has exactly 1 item.
		// We insert 99 more items to bring the cache exactly to its threshold (100).
		for ( $i = 0; $i < 99; $i++ ) {
			$json = wp_json_encode( array( 'type' => "type_{$i}" ) );
			$admin->extract_post_content_data( $json );
		}

		// Cache should now have exactly 100 items.
		$static_vars = $reflection_method->getStaticVariables();
		$this->assertCount( 100, $static_vars['cache'], 'Cache should contain exactly 100 items before eviction.' );

		// Insert the 101st item. This should trigger cache eviction (resetting to empty) and then add the new item.
		$json_101 = wp_json_encode( array( 'type' => 'type_100' ) );
		$admin->extract_post_content_data( $json_101 );

		// Cache should now have exactly 1 item.
		$static_vars_after = $reflection_method->getStaticVariables();
		$this->assertCount( 1, $static_vars_after['cache'], 'Cache should be evicted and contain only 1 item.' );
		$this->assertArrayHasKey( $json_101, $static_vars_after['cache'], 'Cache should contain the newly added item.' );
	}
}
