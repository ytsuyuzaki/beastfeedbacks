<?php
/**
 * Tests for BeastFeedbacks_Public::get_ip_address().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Get_Ip_Address_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CLIENT_IP'] );
		remove_all_filters( 'beastfeedbacks_ip_address' );
		parent::tear_down();
	}

	/** @test */
	public function get_ip_address_returns_value_when_set(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$ip                     = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '127.0.0.1', $ip );
	}

	/** @test */
	public function get_ip_address_returns_valid_ipv6(): void {
		$_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
		$ip                     = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '2001:0db8:85a3:0000:0000:8a2e:0370:7334', $ip );
	}

	/** @test */
	public function get_ip_address_returns_empty_string_for_invalid_ip(): void {
		$_SERVER['REMOTE_ADDR'] = 'invalid_ip_string';
		$ip                     = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '', $ip );
	}

	/** @test */
	public function get_ip_address_ignores_spoofable_headers(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';
		$_SERVER['HTTP_CLIENT_IP']       = '198.51.100.2';
		$_SERVER['REMOTE_ADDR']          = '203.0.113.19';

		$ip = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '203.0.113.19', $ip );
	}

	/** @test */
	public function get_ip_address_allows_filtering_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		add_filter(
			'beastfeedbacks_ip_address',
			function () {
				return '198.51.100.50';
			}
		);

		$ip = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '198.51.100.50', $ip );
	}

	/** @test */
	public function get_ip_address_rejects_invalid_filtered_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		add_filter(
			'beastfeedbacks_ip_address',
			function () {
				return 'invalid_filtered_ip';
			}
		);

		$ip = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame( '', $ip );
	}
}
