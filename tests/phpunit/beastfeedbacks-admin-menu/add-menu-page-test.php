<?php
/**
 * add_menu_page
 */
class Add_Menu_Page_Test extends BeastFeedbacks_TestCase {
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
	public function test_add_menu_page() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		\Brain\Monkey\Functions\expect( 'add_menu_page' )->once()->with(
			'BeastFeedbacks',
			'BeastFeedbacks',
			'edit_pages',
			'edit.php?post_type=beastfeedbacks',
			'',
			'dashicons-feedback'
		);

		\Brain\Monkey\Functions\expect( 'register_post_type' )->once()->with(
			'beastfeedbacks',
			\Mockery::type( 'array' )
		);

		$target->add_menu_page();
		$this->assertTrue( true );
	}
}
