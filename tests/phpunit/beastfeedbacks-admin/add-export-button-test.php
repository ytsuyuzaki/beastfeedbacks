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
		wp_set_current_user( 0 );
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
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

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

		wp_delete_user( $admin_id );
	}

	/** @test */
	public function add_export_button_has_no_output_for_editor_role(): void {
		$editor_id = wp_insert_user(
			array(
				'user_login' => 'test_editor_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'editor',
			)
		);
		wp_set_current_user( $editor_id );

		set_current_screen( 'edit-beastfeedbacks' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();

		$this->assertSame( '', $html );

		wp_delete_user( $editor_id );
	}
}
