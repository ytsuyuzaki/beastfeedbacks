<?php
/**
 * Tests for BeastFeedbacks_Admin::admin_view_tabs().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Admin_View_Tabs_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function admin_view_tabs_unsets_publish_on_target_screen(): void {
		set_current_screen( 'edit-post' );
		$views = array(
			'all'     => 'All',
			'publish' => 'Published',
		);
		$this->assertSame( $views, \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views ) );

		set_current_screen( 'edit-beastfeedbacks' );
		$out = \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views );
		$this->assertArrayNotHasKey( 'publish', $out );
		$this->assertArrayHasKey( 'all', $out );
	}
}
