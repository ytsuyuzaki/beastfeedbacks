<?php
/**
 * Tests for BeastFeedbacks_Public::get_ip_address().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Get_Ip_Address_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		unset( $_SERVER['REMOTE_ADDR'] );
		parent::tear_down();
	}

	/** @test */
	public function get_ip_address_returns_value_when_set(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$ip                     = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '127.0.0.1', $ip );
	}
}
