<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service::is_rate_limited.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Is_Rate_Limited_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Feedback_Service
	 */
	private $service;

	/**
	 * @var int
	 */
	private $admin_id = 0;

	protected function set_up(): void {
		parent::set_up();
		$this->service = \BeastFeedbacks_Feedback_Service::get_instance();
	}

	protected function tear_down(): void {
		// Clean up transients created during tests.
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
		delete_transient( 'bf_rl_' . md5( 'unknown' ) );

		remove_all_filters( 'beastfeedbacks_rate_limit_max_requests' );
		remove_all_filters( 'beastfeedbacks_pre_is_rate_limited' );

		wp_set_current_user( 0 ); // Reset user just in case

		if ( $this->admin_id > 0 ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $this->admin_id );
			$this->admin_id = 0;
		}

		parent::tear_down();
	}

	/** @test */
	public function returns_false_for_admin_users(): void {
		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'testadmin',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin_id );

		$this->assertFalse( $this->service->is_rate_limited( '127.0.0.1' ) );
	}

	/** @test */
	public function respects_pre_is_rate_limited_filter(): void {
		add_filter( 'beastfeedbacks_pre_is_rate_limited', '__return_true' );
		$this->assertTrue( $this->service->is_rate_limited( '127.0.0.1' ) );
	}

	/** @test */
	public function rate_limits_after_max_requests(): void {
		$ip = '127.0.0.1';

		// First 10 requests should not be limited.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( $ip ), "Request $i should not be limited" );
		}

		// 11th request should be limited.
		$this->assertTrue( $this->service->is_rate_limited( $ip ), '11th request should be limited' );
	}

	/** @test */
	public function handles_empty_ip_address_as_unknown(): void {
		$ip = '';

		// 10 requests should pass
		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( $ip ) );
		}

		// 11th should fail
		$this->assertTrue( $this->service->is_rate_limited( $ip ) );
	}

	/** @test */
	public function resets_count_after_window_expires(): void {
		$ip = '127.0.0.1';

		// Create a transient that is already expired
		$key   = 'bf_rl_' . md5( $ip );
		$entry = array(
			'count'    => 10,
			'reset_at' => time() - 10, // 10 seconds in the past
		);
		set_transient( $key, $entry, 60 );

		// Because it's expired, it should reset and allow the request.
		$this->assertFalse( $this->service->is_rate_limited( $ip ) );

		// Verify it was reset to 1
		$new_entry = get_transient( $key );
		$this->assertSame( 1, $new_entry['count'] );
	}

	/** @test */
	public function respects_custom_max_requests_filter(): void {
		$ip = '127.0.0.1';

		add_filter(
			'beastfeedbacks_rate_limit_max_requests',
			function() {
				return 2;
			}
		);

		$this->assertFalse( $this->service->is_rate_limited( $ip ) ); // count = 1
		$this->assertFalse( $this->service->is_rate_limited( $ip ) ); // count = 2
		$this->assertTrue( $this->service->is_rate_limited( $ip ) );  // count = 3 (limited)
	}
}
