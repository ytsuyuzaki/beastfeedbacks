<?php
/**
 * Tests for BeastFeedbacks_Admin::add_type_filter().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Add_Type_Filter_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function add_type_filter_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_type_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_type_filter_renders_nonce_field_and_select_on_target_screen(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_type_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="_beastfeedbacks_nonce"', $html );
		$this->assertStringContainsString( '<select name="beastfeedbacks_type">', $html );
	}
}
