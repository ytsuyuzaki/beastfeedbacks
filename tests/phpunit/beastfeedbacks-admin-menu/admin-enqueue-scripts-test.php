<?php
/**
 * admin_enqueue_scripts
 */
class Admin_Enqueue_Scripts_Test extends BeastFeedbacks_TestCase {
	/**
	 * Tear down
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_admin_enqueue_scripts() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		$screen_mock     = new stdClass();
		$screen_mock->id = 'edit-beastfeedbacks';
		\Brain\Monkey\Functions\expect( 'get_current_screen' )->once()->andReturn( $screen_mock );

		\Brain\Monkey\Functions\expect( 'wp_enqueue_script' )->once()->with(
			BEASTFEEDBACKS_DOMAIN,
			BEASTFEEDBACKS_URL . 'public/js/beastfeedbacks-admin.js',
			array(),
			BEASTFEEDBACKS_VERSION,
			true
		);

		\Brain\Monkey\Functions\expect( 'wp_enqueue_style' )->once()->with(
			BEASTFEEDBACKS_DOMAIN,
			BEASTFEEDBACKS_URL . 'public/css/beastfeedbacks-admin.css',
			array(),
			BEASTFEEDBACKS_VERSION
		);

		$target->admin_enqueue_scripts();
		$this->assertTrue( true );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_admin_enqueue_scripts_other_screen() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		$screen_mock     = new stdClass();
		$screen_mock->id = 'post';
		\Brain\Monkey\Functions\expect( 'get_current_screen' )->once()->andReturn( $screen_mock );

		\Brain\Monkey\Functions\expect( 'wp_enqueue_script' )->never();
		\Brain\Monkey\Functions\expect( 'wp_enqueue_style' )->never();

		$target->admin_enqueue_scripts();
		$this->assertTrue( true );
	}
}
