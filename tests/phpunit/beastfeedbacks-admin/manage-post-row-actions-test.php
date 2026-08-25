<?php
/**
 * Tests for BeastFeedbacks_Admin::manage_post_row_actions().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Manage_Post_Row_Actions_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/** @test */
	public function manage_post_row_actions_unsets_edit_when_beastfeedbacks_and_published(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'beastfeedbacks',
			'post_status' => 'publish',
		);
		$in              = array(
			'edit'                 => 'Edit',
			'inline hide-if-no-js' => 'Quick Edit',
			'view'                 => 'View',
		);
		$out             = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertArrayNotHasKey( 'edit', $out );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $out );
		$this->assertArrayHasKey( 'view', $out );
	}
}
