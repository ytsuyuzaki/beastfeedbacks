<?php
/**
 * Tests for BeastFeedbacks_Admin::is_filter_nonce_verified().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Is_Filter_Nonce_Verified_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * Call private method is_filter_nonce_verified on BeastFeedbacks_Admin via reflection.
	 *
	 * @param BeastFeedbacks_Admin $admin Admin instance.
	 * @return bool
	 */
	private function call_is_filter_nonce_verified( BeastFeedbacks_Admin $admin ): bool {
		$reflection = new ReflectionClass( $admin );
		$method     = $reflection->getMethod( 'is_filter_nonce_verified' );
		$method->setAccessible( true );
		return $method->invoke( $admin );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_false_when_no_nonce_supplied(): void {
		$admin  = BeastFeedbacks_Admin::get_instance();
		$result = $this->call_is_filter_nonce_verified( $admin );
		$this->assertFalse( $result );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_true_for_valid_filter_nonce(): void {
		$admin = BeastFeedbacks_Admin::get_instance();

		$_REQUEST['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );

		$result = $this->call_is_filter_nonce_verified( $admin );
		$this->assertTrue( $result );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_true_for_valid_csv_export_nonce(): void {
		$admin = BeastFeedbacks_Admin::get_instance();

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'beastfeedbacks_csv_export' );

		$result = $this->call_is_filter_nonce_verified( $admin );
		$this->assertTrue( $result );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_false_for_invalid_nonce(): void {
		$admin = BeastFeedbacks_Admin::get_instance();

		$_REQUEST['_beastfeedbacks_nonce'] = 'invalid_nonce';
		$_REQUEST['_wpnonce']              = 'invalid_nonce';

		$result = $this->call_is_filter_nonce_verified( $admin );
		$this->assertFalse( $result );
	}
}
