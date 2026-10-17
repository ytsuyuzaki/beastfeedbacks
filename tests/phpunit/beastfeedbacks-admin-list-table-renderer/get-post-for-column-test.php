<?php
/**
 * Class Get_Post_For_Column_Test
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin_List_Table_Renderer::get_post_for_column()
 */
class Get_Post_For_Column_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Admin_List_Table_Renderer
	 */
	private $renderer;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->renderer = BeastFeedbacks_Admin_List_Table_Renderer::get_instance();
	}

	/**
	 * Test that it returns a WP_Post object for a valid post ID.
	 */
	public function test_returns_wp_post_for_valid_id() {
		$post_id = wp_insert_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'Test Feedback for Column',
			)
		);

		$result = $this->renderer->get_post_for_column( $post_id );

		$this->assertInstanceOf( WP_Post::class, $result );
		$this->assertEquals( $post_id, $result->ID );
	}

	/**
	 * Test that it returns null for an invalid post ID.
	 */
	public function test_returns_null_for_invalid_id() {
		$invalid_id = 999999;

		// Ensure this post actually doesn't exist
		$this->assertNull( get_post( $invalid_id ) );

		$result = $this->renderer->get_post_for_column( $invalid_id );

		$this->assertNull( $result );
	}
}
