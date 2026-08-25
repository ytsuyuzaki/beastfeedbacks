<?php
/**
 * Tests for BeastFeedbacks_Public::get_user_agent().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Get_User_Agent_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		unset( $_SERVER['HTTP_USER_AGENT'] );
		parent::tear_down();
	}

	/** @test */
	public function get_user_agent_returns_empty_when_not_set(): void {
		unset( $_SERVER['HTTP_USER_AGENT'] );
		$ua = \BeastFeedbacks_Public::get_instance()->get_user_agent();
		$this->assertSame( '', $ua );
	}

	/** @test */
	public function get_user_agent_returns_sanitized_value_when_set(): void {
		$_SERVER['HTTP_USER_AGENT'] = "TestAgent/1.0\t";
		$ua                         = \BeastFeedbacks_Public::get_instance()->get_user_agent();
		$this->assertStringContainsString( 'TestAgent/1.0', $ua );
	}
}
