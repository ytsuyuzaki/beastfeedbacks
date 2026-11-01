<?php
/**
 * admin_bulk_actions
 */
class Admin_Bulk_Actions_Test extends BeastFeedbacks_TestCase {
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
	public function test_admin_bulk_actions() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		global $current_screen;
		$current_screen     = new stdClass();
		$current_screen->id = 'edit-beastfeedbacks';

		$actions = array(
			'edit'  => 'Edit',
			'trash' => 'Trash',
		);

		$result = $target->admin_bulk_actions( $actions );

		$this->assertArrayNotHasKey( 'edit', $result );
		$this->assertArrayHasKey( 'trash', $result );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_admin_bulk_actions_other_screen() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		global $current_screen;
		$current_screen     = new stdClass();
		$current_screen->id = 'post';

		$actions = array(
			'edit'  => 'Edit',
			'trash' => 'Trash',
		);

		$result = $target->admin_bulk_actions( $actions );

		$this->assertArrayHasKey( 'edit', $result );
		$this->assertArrayHasKey( 'trash', $result );
	}
}
