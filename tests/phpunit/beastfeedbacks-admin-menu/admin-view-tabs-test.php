<?php
/**
 * admin_view_tabs
 */
class Admin_View_Tabs_Test extends BeastFeedbacks_TestCase {
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
	public function test_admin_view_tabs() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		global $current_screen;
		$current_screen     = new stdClass();
		$current_screen->id = 'edit-beastfeedbacks';

		$views = array(
			'all'     => 'All',
			'publish' => 'Publish',
			'trash'   => 'Trash',
		);

		$result = $target->admin_view_tabs( $views );

		$this->assertArrayNotHasKey( 'publish', $result );
		$this->assertArrayHasKey( 'all', $result );
		$this->assertArrayHasKey( 'trash', $result );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_admin_view_tabs_other_screen() {
		$target = BeastFeedbacks_Admin_Menu::get_instance();

		global $current_screen;
		$current_screen     = new stdClass();
		$current_screen->id = 'post';

		$views = array(
			'all'     => 'All',
			'publish' => 'Publish',
			'trash'   => 'Trash',
		);

		$result = $target->admin_view_tabs( $views );

		$this->assertArrayHasKey( 'publish', $result );
		$this->assertArrayHasKey( 'all', $result );
		$this->assertArrayHasKey( 'trash', $result );
	}
}
