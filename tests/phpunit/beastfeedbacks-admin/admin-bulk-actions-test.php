<?php
/**
 * Tests for BeastFeedbacks_Admin::admin_bulk_actions().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Admin_Bulk_Actions_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function admin_bulk_actions_unsets_edit_only_on_target_screen(): void {
		set_current_screen( 'edit-post' );
		$in  = array(
			'edit'  => '編集',
			'trash' => 'ゴミ箱',
		);
		$out = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertSame( $in, $out );

		set_current_screen( 'edit-beastfeedbacks' );
		$out2 = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertArrayNotHasKey( 'edit', $out2 );
		$this->assertArrayHasKey( 'trash', $out2 );
	}
}
