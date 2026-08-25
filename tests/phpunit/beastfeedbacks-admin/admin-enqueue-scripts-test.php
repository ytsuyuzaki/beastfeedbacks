<?php
/**
 * Tests for BeastFeedbacks_Admin::admin_enqueue_scripts().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Admin_Enqueue_Scripts_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		wp_dequeue_script( BEASTFEEDBACKS_DOMAIN );
		wp_deregister_script( BEASTFEEDBACKS_DOMAIN );
		wp_dequeue_style( BEASTFEEDBACKS_DOMAIN );
		wp_deregister_style( BEASTFEEDBACKS_DOMAIN );
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/** @test */
	public function admin_enqueue_scripts_does_not_enqueue_assets_when_not_target_screen(): void {
		set_current_screen( 'edit-post' );

		\BeastFeedbacks_Admin::get_instance()->admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );
		$this->assertFalse( wp_style_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );
	}

	/** @test */
	public function admin_enqueue_scripts_enqueues_js_and_css_on_target_screen(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		\BeastFeedbacks_Admin::get_instance()->admin_enqueue_scripts();

		$this->assertTrue( wp_script_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );
		$this->assertTrue( wp_style_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );

		global $wp_scripts, $wp_styles;

		$script = $wp_scripts->registered[ BEASTFEEDBACKS_DOMAIN ];
		$this->assertSame( BEASTFEEDBACKS_URL . 'public/js/beastfeedbacks-admin.js', $script->src );
		$this->assertSame( BEASTFEEDBACKS_VERSION, $script->ver );
		$this->assertSame( array(), $script->deps );

		$style = $wp_styles->registered[ BEASTFEEDBACKS_DOMAIN ];
		$this->assertSame( BEASTFEEDBACKS_URL . 'public/css/beastfeedbacks-admin.css', $style->src );
		$this->assertSame( BEASTFEEDBACKS_VERSION, $style->ver );
		$this->assertSame( array(), $style->deps );
	}
}
