<?php
/**
 * Tests for BeastFeedbacks_Admin::add_source_filter().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Add_Source_Filter_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function add_source_filter_renders_options_on_target_screen(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Test Parent Page',
				'post_type'  => 'page',
			)
		);

		$this->create_post(
			array(
				'post_title'  => 'Feedback 1',
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		set_current_screen( 'edit-beastfeedbacks' );

		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="beastfeedbacks_parent_id"', $html );
		$this->assertStringContainsString( 'value="' . $parent_id . '"', $html );
	}

	/** @test */
	public function add_source_filter_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}
}
