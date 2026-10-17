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

		// Insert 150 items to ensure eviction limit (100) handles it gracefully without fatal errors.
		for ( $i = 0; $i < 150; $i++ ) {
			$json = wp_json_encode(
				array(
					'type' => "type_{$i}",
				)
			);
			$result = $admin->extract_post_content_data( $json );
			$this->assertSame( "type_{$i}", $result['type'] );
			$this->assertTrue( $result['is_valid'] );
		}
	}
}
