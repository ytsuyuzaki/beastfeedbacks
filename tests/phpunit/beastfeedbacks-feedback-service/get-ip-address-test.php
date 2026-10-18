<?php
/**
 * Tests for BeastFeedbacks_Feedback_Service::get_ip_address().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Service_Get_Ip_Address_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Feedback_Service
	 */
	private $service;

	/**
	 * @var array|null
	 */
	private $original_server;

	protected function set_up(): void {
		parent::set_up();
		$this->service         = BeastFeedbacks_Feedback_Service::get_instance();
		$this->original_server = $_SERVER;
	}

	protected function tear_down(): void {
		$_SERVER = $this->original_server;
		remove_all_filters( 'beastfeedbacks_ip_address' );
		parent::tear_down();
	}

	/** @test */
	public function get_ip_address_returns_valid_ipv4(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
		$this->assertSame( '192.168.1.1', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_returns_valid_ipv6(): void {
		$_SERVER['REMOTE_ADDR'] = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
		$this->assertSame( '2001:0db8:85a3:0000:0000:8a2e:0370:7334', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_returns_empty_for_invalid_ip(): void {
		$_SERVER['REMOTE_ADDR'] = 'invalid-ip';
		$this->assertSame( '', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_returns_empty_for_out_of_range_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '256.256.256.256';
		$this->assertSame( '', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_returns_empty_when_not_set(): void {
		unset( $_SERVER['REMOTE_ADDR'] );
		$this->assertSame( '', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_returns_empty_for_non_scalar(): void {
		$_SERVER['REMOTE_ADDR'] = array( '192.168.1.1' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->assertSame( '', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_ignores_spoofable_headers(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';
		$_SERVER['HTTP_CLIENT_IP']       = '198.51.100.2';
		$_SERVER['REMOTE_ADDR']          = '203.0.113.19';

		$this->assertSame( '203.0.113.19', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_applies_filter_valid_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';

		add_filter(
			'beastfeedbacks_ip_address',
			function () {
				return '10.0.0.1';
			}
		);

		$this->assertSame( '10.0.0.1', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_ignores_filter_invalid_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';

		add_filter(
			'beastfeedbacks_ip_address',
			function () {
				return 'invalid-filtered-ip';
			}
		);

		$this->assertSame( '', $this->service->get_ip_address() );
	}

	/** @test */
	public function get_ip_address_returns_empty_string_when_filtered_ip_is_non_scalar(): void {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		add_filter(
			'beastfeedbacks_ip_address',
			function () {
				return array( '198.51.100.50' );
			}
		);

		$this->assertSame( '', $this->service->get_ip_address() );
	}
}
