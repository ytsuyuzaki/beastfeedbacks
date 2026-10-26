<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service::is_rate_limited() delegation.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Is_Rate_Limited_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Feedback_Service
	 */
	private $service;

	protected function set_up(): void {
		parent::set_up();
		$this->service = \BeastFeedbacks_Feedback_Service::get_instance();
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
	}

	protected function tear_down(): void {
		delete_transient( 'bf_rl_' . md5( '127.0.0.1' ) );
		parent::tear_down();
	}

	/** @test */
	public function is_rate_limited_delegates_to_rate_limiter(): void {
		$ip = '127.0.0.1';

		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertFalse( $this->service->is_rate_limited( $ip ), "Request $i should not be limited" );
		}

		$this->assertTrue( $this->service->is_rate_limited( $ip ), '11th request should be limited' );
	}
}
