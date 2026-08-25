<?php
/**
 * Tests for BeastFeedbacks_Admin::manage_post_row_actions().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Manage_Post_Row_Actions_Test extends BeastFeedbacks_TestCase {

	/**
	 * Tear down test context.
	 */
	protected function tear_down(): void {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/** @test */
	public function manage_post_row_actions_removes_edit_quick_edit_and_view_for_published_beastfeedbacks(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'beastfeedbacks',
			'post_status' => 'publish',
		);
		$in              = array(
			'edit'                 => '<a href="#">Edit</a>',
			'inline hide-if-no-js' => '<a href="#">Quick Edit</a>',
			'view'                 => '<a href="#">View</a>',
			'trash'                => '<a href="#">Trash</a>',
		);
		$out             = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertArrayNotHasKey( 'edit', $out );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $out );
		$this->assertArrayNotHasKey( 'view', $out );
		$this->assertArrayHasKey( 'trash', $out );
		$this->assertSame( '<a href="#">Trash</a>', $out['trash'] );
	}

	/** @test */
	public function manage_post_row_actions_preserves_actions_for_trashed_beastfeedbacks(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'beastfeedbacks',
			'post_status' => 'trash',
		);
		$in              = array(
			'untrash' => '<a href="#">Restore</a>',
			'delete'  => '<a href="#">Delete Permanently</a>',
		);
		$out             = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertSame( $in, $out );
	}

	/** @test */
	public function manage_post_row_actions_preserves_actions_for_other_post_types(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'post',
			'post_status' => 'publish',
		);
		$in              = array(
			'edit'                 => '<a href="#">Edit</a>',
			'inline hide-if-no-js' => '<a href="#">Quick Edit</a>',
			'view'                 => '<a href="#">View</a>',
			'trash'                => '<a href="#">Trash</a>',
		);
		$out             = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertSame( $in, $out );

		$GLOBALS['post'] = (object) array(
			'post_type'   => 'page',
			'post_status' => 'publish',
		);
		$out_page        = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertSame( $in, $out_page );
	}
}
