<?php
/**
 * Tests for BeastFeedbacks_Admin::add_menu_page().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Add_Menu_Page_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function add_menu_page_registers_menu_and_post_type(): void {
		global $menu;

		\BeastFeedbacks_Admin::get_instance()->add_menu_page();

		// CPT registration state check
		$post_type_obj = get_post_type_object( 'beastfeedbacks' );
		$this->assertNotNull( $post_type_obj, 'beastfeedbacks post type should be registered' );
		$this->assertFalse( $post_type_obj->public );
		$this->assertTrue( $post_type_obj->show_ui );
		$this->assertFalse( $post_type_obj->show_in_menu );
		$this->assertFalse( $post_type_obj->show_in_admin_bar );
		$this->assertFalse( $post_type_obj->show_in_rest );
		$this->assertSame( 'Beastfeedbacks', $post_type_obj->labels->name );
		$this->assertSame( 'do_not_allow', $post_type_obj->cap->create_posts );

		// Global $menu state check
		$found_menu = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'edit.php?post_type=beastfeedbacks' === $item[2] ) {
					$found_menu = true;
					$this->assertSame( 'BeastFeedbacks', $item[0] );
					$this->assertSame( 'edit_pages', $item[1] );
					$this->assertSame( 'dashicons-feedback', $item[6] ?? '' );
					break;
				}
			}
		}
		$this->assertTrue( $found_menu, 'Admin menu item for beastfeedbacks should be added to $menu' );
	}
}
