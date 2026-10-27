<?php
/**
 * Tests for BeastFeedbacks_Rate_Limiter::is_rate_limited().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Rate_Limiter_Is_Rate_Limited_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Rate_Limiter
	 */
	private $service;

	/**
	 * @var int
	 */
	private $admin_id = 0;

	protected function set_up(): void {
		parent::set_up();
		$this->service = \BeastFeedbacks_Rate_Limiter::get_instance();
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
		delete_transient( 'bf_rl_' . md5( 'unknown' ) );
		delete_transient( 'bf_rl_' . md5( '198.51.100.99' ) );
		delete_transient( 'bf_rl_' . md5( '198.51.100.50' ) );
		delete_transient( 'bf_rl_' . md5( '198.51.100.77' ) );
	}

	protected function tear_down(): void {
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
		delete_transient( 'bf_rl_' . md5( 'unknown' ) );
		delete_transient( 'bf_rl_' . md5( '198.51.100.99' ) );
		delete_transient( 'bf_rl_' . md5( '198.51.100.50' ) );
		delete_transient( 'bf_rl_' . md5( '198.51.100.77' ) );

		remove_all_filters( 'beastfeedbacks_rate_limit_max_requests' );
		remove_all_filters( 'beastfeedbacks_pre_is_rate_limited' );

		wp_set_current_user( 0 );

		if ( $this->admin_id > 0 ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $this->admin_id );
			$this->admin_id = 0;
		}

		parent::tear_down();
	}

	/** @test */
	public function is_rate_limited_returns_false_for_admin_users(): void {
		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin_id );

		for ( $i = 0; $i < 15; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( '127.0.0.1' ) );
		}
	}

	/** @test */
	public function is_rate_limited_respects_pre_filter(): void {
		add_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_true' );
		$this->assertTrue( $this->service->is_rate_limited( '127.0.0.1' ) );
	}

	/** @test */
	public function is_rate_limited_allows_requests_under_limit(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( '127.0.0.1' ) );
		}
	}

	/** @test */
	public function is_rate_limited_blocks_requests_over_limit(): void {
		$ip = '127.0.0.1';

		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( $ip ), "Request $i should not be limited" );
		}

		$this->assertTrue( $this->service->is_rate_limited( $ip ), '11th request should be limited' );
	}

	/** @test */
	public function handles_empty_ip_address_as_unknown(): void {
		$ip = '';

		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		}

		$this->assertTrue( $this->service->is_rate_limited( $ip ) );
	}

	/** @test */
	public function resets_count_after_window_expires(): void {
		$ip = '127.0.0.1';

		$key   = 'bf_rl_' . md5( $ip );
		$entry = array(
			'count'    => 10,
			'reset_at' => time() - 10,
		);
		set_transient( $key, $entry, 60 );

		$this->assertFalse( $this->service->is_rate_limited( $ip ) );

		$new_entry = get_transient( $key );
		$this->assertSame( 1, $new_entry['count'] );
	}

	/** @test */
	public function is_rate_limited_respects_custom_max_requests(): void {
		$ip = '127.0.0.1';

		add_filter(
			'beastfeedbacks_rate_limit_max_requests',
			function () {
				return 2;
			}
		);

		$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		$this->assertTrue( $this->service->is_rate_limited( $ip ) );
	}

	/**
	 * Verify that rate limit window reset_at is anchored from the first request.
	 */
	public function test_is_rate_limited_anchors_reset_window(): void {
		$ip  = '198.51.100.99';
		$key = 'bf_rl_' . md5( $ip );

		$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		$entry = get_transient( $key );
		$this->assertIsArray( $entry );
		$this->assertSame( 1, $entry['count'] );
		$initial_reset_at = $entry['reset_at'];

		// Subsequent calls should increment count but keep the same reset_at.
		$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		$entry = get_transient( $key );
		$this->assertSame( 2, $entry['count'] );
		$this->assertSame( $initial_reset_at, $entry['reset_at'] );
	}

	/**
	 * Verify that is_rate_limited bypasses rate limiting for administrators.
	 */
	public function test_is_rate_limited_bypasses_for_admins(): void {
		$user_id = $this->create_user( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( $this->service->is_rate_limited( '198.51.100.50' ) );

		// Even when transient exceeds max requests, admin is not rate limited.
		$key = 'bf_rl_' . md5( '198.51.100.50' );
		set_transient(
			$key,
			array(
				'count'    => 100,
				'reset_at' => time() + 60,
			),
			60
		);

		$this->assertFalse( $this->service->is_rate_limited( '198.51.100.50' ) );
	}

	/**
	 * Verify that pre_is_rate_limited filter can override rate limit check.
	 */
	public function test_is_rate_limited_filter_override(): void {
		$ip = '198.51.100.77';

		add_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_true' );
		$this->assertTrue( $this->service->is_rate_limited( $ip ) );
		remove_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_true' );

		add_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_false' );
		$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		remove_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_false' );
	}
}
