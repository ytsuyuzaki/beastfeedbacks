<?php
/**
 * Tests for BeastFeedbacks_Admin::verify_admin_nonce().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Verify_Admin_Nonce_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * Call private method verify_admin_nonce on BeastFeedbacks_Admin via reflection.
	 *
	 * @param BeastFeedbacks_Admin $admin Admin instance.
	 * @return bool
	 */
	private function call_verify_admin_nonce( BeastFeedbacks_Admin $admin ): bool {
		$reflection = new ReflectionClass( $admin );
		$method     = $reflection->getMethod( 'verify_admin_nonce' );
		$method->setAccessible( true );
		return $method->invoke( $admin );
	}

	/** @test */
	public function verify_admin_nonce_returns_false_when_no_nonce_supplied(): void {
		$admin  = BeastFeedbacks_Admin::get_instance();
		$result = $this->call_verify_admin_nonce( $admin );
		$this->assertFalse( $result );
	}

	/** @test */
	public function verify_admin_nonce_returns_true_for_valid_filter_nonce(): void {
		$admin = BeastFeedbacks_Admin::get_instance();

		$_REQUEST['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );

		$result = $this->call_verify_admin_nonce( $admin );
		$this->assertTrue( $result );
	}

	/** @test */
	public function verify_admin_nonce_returns_true_for_valid_csv_export_nonce(): void {
		$admin = BeastFeedbacks_Admin::get_instance();

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'beastfeedbacks_csv_export' );

		$result = $this->call_verify_admin_nonce( $admin );
		$this->assertTrue( $result );
	}

	/** @test */
	public function verify_admin_nonce_returns_false_for_invalid_nonce(): void {
		$admin = BeastFeedbacks_Admin::get_instance();

		$_REQUEST['_beastfeedbacks_nonce'] = 'invalid_nonce';
		$_REQUEST['_wpnonce']              = 'invalid_nonce';

		$result = $this->call_verify_admin_nonce( $admin );
		$this->assertFalse( $result );
	}
}
