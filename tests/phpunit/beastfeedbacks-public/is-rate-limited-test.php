<?php
/**
 * Tests for BeastFeedbacks_Public::is_rate_limited().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Is_Rate_Limited_Test extends BeastFeedbacks_TestCase {

	/**
	 * Setup before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		// Clean up transients for tests.
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
		delete_transient( 'bf_rl_' . md5( 'unknown' ) );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
		delete_transient( 'bf_rl_' . md5( 'unknown' ) );

		// Clear current user
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/** @test */
	public function is_rate_limited_returns_false_for_admin_users(): void {
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		$instance = \BeastFeedbacks_Public::get_instance();

		// Simulate hitting the limit.
		for ( $i = 0; $i < 15; $i++ ) {
			$this->assertFalse( $instance->is_rate_limited( '127.0.0.1' ) );
		}
	}

	/** @test */
	public function is_rate_limited_respects_pre_filter(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		add_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_true' );

		$this->assertTrue( $instance->is_rate_limited( '127.0.0.1' ) );

		remove_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_true' );
	}

	/** @test */
	public function is_rate_limited_allows_requests_under_limit(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		// Default limit is 10. First 10 requests should be allowed.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $instance->is_rate_limited( '127.0.0.1' ) );
		}
	}

	/** @test */
	public function is_rate_limited_blocks_requests_over_limit(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		// 10 requests allowed
		for ( $i = 0; $i < 10; $i++ ) {
			$instance->is_rate_limited( '127.0.0.1' );
		}

		// 11th request should be blocked
		$this->assertTrue( $instance->is_rate_limited( '127.0.0.1' ) );
	}

	/** @test */
	public function is_rate_limited_respects_custom_max_requests(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		add_filter( 'beastfeedbacks_rate_limit_max_requests', function() {
			return 3;
		});

		// 3 requests allowed
		$this->assertFalse( $instance->is_rate_limited( '127.0.0.1' ) );
		$this->assertFalse( $instance->is_rate_limited( '127.0.0.1' ) );
		$this->assertFalse( $instance->is_rate_limited( '127.0.0.1' ) );

		// 4th request blocked
		$this->assertTrue( $instance->is_rate_limited( '127.0.0.1' ) );
	}

	/** @test */
	public function is_rate_limited_handles_empty_ip(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		add_filter( 'beastfeedbacks_rate_limit_max_requests', function() {
			return 2;
		});

		$this->assertFalse( $instance->is_rate_limited( '' ) );
		$this->assertFalse( $instance->is_rate_limited( '' ) );
		$this->assertTrue( $instance->is_rate_limited( '' ) );
	}
}
