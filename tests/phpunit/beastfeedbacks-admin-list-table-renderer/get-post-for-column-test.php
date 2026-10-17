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
	 * @var mixed Backup of global $post.
	 */
	private $backup_post;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->renderer = BeastFeedbacks_Admin_List_Table_Renderer::get_instance();

		if ( array_key_exists( 'post', $GLOBALS ) ) {
			$this->backup_post = $GLOBALS['post'];
		}
	}

	/**
	 * Tear down after each test.
	 */
	protected function tear_down(): void {
		if ( null !== $this->backup_post ) {
			$GLOBALS['post'] = $this->backup_post;
		} else {
			unset( $GLOBALS['post'] );
		}

		parent::tear_down();
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

	/**
	 * Test that it reuses the global $post object if it matches the requested ID.
	 */
	public function test_reuses_global_post_when_available() {
		$post_id = wp_insert_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'Test Feedback for Column Global',
			)
		);

		$global_post = get_post( $post_id );
		// Add a custom property to distinguish this exact instance
		$global_post->custom_prop_for_test = 'yes';

		$GLOBALS['post'] = $global_post;

		$result = $this->renderer->get_post_for_column( $post_id );

		$this->assertInstanceOf( WP_Post::class, $result );
		$this->assertEquals( $post_id, $result->ID );
		$this->assertSame( $global_post, $result, 'The method should return the exact instance from $GLOBALS["post"].' );
		$this->assertTrue( isset( $result->custom_prop_for_test ) && 'yes' === $result->custom_prop_for_test );
	}

	/**
	 * Test that it fetches the post if the global $post ID does not match.
	 */
	public function test_ignores_global_post_if_id_mismatches() {
		$global_post_id = wp_insert_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'Global Post',
			)
		);

		$target_post_id = wp_insert_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'Target Post',
			)
		);

		$GLOBALS['post'] = get_post( $global_post_id );

		$result = $this->renderer->get_post_for_column( $target_post_id );

		$this->assertInstanceOf( WP_Post::class, $result );
		$this->assertEquals( $target_post_id, $result->ID );
		$this->assertNotSame( $GLOBALS['post'], $result );
	}
}
