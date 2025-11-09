<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Public_Test extends TestCase
{

	/** @var int[] 作成した投稿のIDを記録して後始末 */
	private $created_ids = array();

	public function set_up(): void
	{
		parent::set_up();
	}

	public function tear_down(): void
	{
		foreach (array_reverse($this->created_ids) as $pid) {
			if (get_post($pid)) {
				wp_delete_post($pid, true);
			}
		}
		$this->created_ids = array();

		// グローバルの後片付け
		unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
		$_POST = array();

		parent::tear_down();
	}

	/** @test */
	public function get_user_agent_returns_empty_when_not_set(): void
	{
		unset($_SERVER['HTTP_USER_AGENT']);
		$ua = \BeastFeedbacks_Public::get_instance()->get_user_agent();
		$this->assertSame('', $ua);
	}

	/** @test */
	public function get_user_agent_returns_sanitized_value_when_set(): void
	{
		$_SERVER['HTTP_USER_AGENT'] = "TestAgent/1.0\t";
		$ua = \BeastFeedbacks_Public::get_instance()->get_user_agent();
		$this->assertStringContainsString('TestAgent/1.0', $ua);
	}

	/** @test */
	public function get_ip_address_returns_value_when_set(): void
	{
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$ip = \BeastFeedbacks_Public::get_instance()->get_ip_address();
		$this->assertSame('127.0.0.1', $ip);
	}

	/**
	 * 投稿を作成し、ID を回収・記録するユーティリティ
	 *
	 * @param array $args wp_insert_post() の引数.
	 * @return int 作成した投稿ID
	 */
	private function create_post(array $args): int
	{
		$pid                 = wp_insert_post($args);
		$this->created_ids[] = $pid;
		return $pid;
	}

}
