<?php
/**
 * Tests for BeastFeedbacks_Admin::add_export_button().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Add_Export_Button_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function add_export_button_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}
}
