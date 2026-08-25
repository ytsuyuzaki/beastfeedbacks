<?php
/**
 * Tests for BeastFeedbacks::init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Init_Test extends BeastFeedbacks_TestCase {

	/**
	 * Verify init loads dependencies and registers admin, public, and block hooks when is_admin returns true.
	 *
	 * @test
	 */
	public function init_registers_admin_public_and_block_hooks_when_is_admin_is_true(): void {
		$GLOBALS['current_screen'] = new class() {
			public function in_admin() {
				return true;
			}
		};

		\BeastFeedbacks::get_instance()->init();

		$this->assertNotFalse(
			has_action( 'admin_enqueue_scripts', array( \BeastFeedbacks_Admin::get_instance(), 'admin_enqueue_scripts' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_register_beastfeedbacks_form', array( \BeastFeedbacks_Public::get_instance(), 'register_beastfeedbacks_form' ) )
		);
		$this->assertNotFalse(
			has_action( 'init', array( \BeastFeedbacks_Block::get_instance(), 'init_blocks' ) )
		);
	}

	/**
	 * Verify init registers public and block hooks but skips admin initialization when is_admin returns false.
	 *
	 * @test
	 */
	public function init_registers_public_and_block_hooks_but_not_admin_when_is_admin_is_false(): void {
		$GLOBALS['current_screen'] = new class() {
			public function in_admin() {
				return false;
			}
		};

		// Clean up any actions added in previous tests/runs.
		remove_all_actions( 'admin_enqueue_scripts' );
		remove_all_actions( 'wp_ajax_register_beastfeedbacks_form' );
		remove_all_actions( 'init' );

		\BeastFeedbacks::get_instance()->init();

		$this->assertFalse(
			has_action( 'admin_enqueue_scripts', array( \BeastFeedbacks_Admin::get_instance(), 'admin_enqueue_scripts' ) )
		);
		$this->assertNotFalse(
			has_action( 'wp_ajax_register_beastfeedbacks_form', array( \BeastFeedbacks_Public::get_instance(), 'register_beastfeedbacks_form' ) )
		);
		$this->assertNotFalse(
			has_action( 'init', array( \BeastFeedbacks_Block::get_instance(), 'init_blocks' ) )
		);
	}
}
