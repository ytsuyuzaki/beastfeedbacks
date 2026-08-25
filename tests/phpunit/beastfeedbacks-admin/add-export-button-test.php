<?php
/**
 * Tests for BeastFeedbacks_Admin::add_export_button().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Add_Export_Button_Test extends BeastFeedbacks_TestCase {

	/**
	 * Tear down test context.
	 */
	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function add_export_button_has_no_output_when_current_screen_is_null(): void {
		unset( $GLOBALS['current_screen'] );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_export_button_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();
		$this->assertSame( '', $html );

		set_current_screen( 'dashboard' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_export_button_outputs_button_html_on_beastfeedbacks_screen(): void {
		set_current_screen( 'edit-beastfeedbacks' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<button', $html );
		$this->assertStringContainsString( 'class="button button-primary beastfeedbacks-export-btn"', $html );
		$this->assertStringContainsString( 'data-endpoint=', $html );
		$this->assertStringContainsString( 'data-action="beastfeedbacks_export"', $html );
		$this->assertStringContainsString( 'data-nonce=', $html );
		$this->assertStringContainsString( 'Export', $html );
	}
}
