<?php
/**
 * Init
 */
class Init_Test extends BeastFeedbacks_TestCase {
	/**
	 * Init
	 */
	public function test_init() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		\Brain\Monkey\Functions\expect( 'add_action' )->once()->with( 'admin_enqueue_scripts', array( $target, 'admin_enqueue_scripts' ) );
		\Brain\Monkey\Functions\expect( 'add_action' )->once()->with( 'admin_menu', array( $target, 'add_menu_page' ) );
		\Brain\Monkey\Functions\expect( 'add_filter' )->once()->with( 'bulk_actions-edit-beastfeedbacks', array( $target, 'admin_bulk_actions' ) );
		\Brain\Monkey\Functions\expect( 'add_filter' )->once()->with( 'views_edit-beastfeedbacks', array( $target, 'admin_view_tabs' ) );

		$target->init();
		$this->assertTrue( true );
	}
}
