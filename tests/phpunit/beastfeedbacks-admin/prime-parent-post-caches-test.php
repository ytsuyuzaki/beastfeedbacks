<?php
/**
 * Tests for BeastFeedbacks_Admin::prime_parent_post_caches().
 *
 * @package BeastFeedbacks
 */

/**
 * Tests for BeastFeedbacks_Admin::prime_parent_post_caches().
 */
class BeastFeedbacks_Admin_Prime_Parent_Post_Caches_Test extends BeastFeedbacks_TestCase {

	/**
	 * Clean up environment after each test.
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * Create fake query helper object.
	 *
	 * @param array $vars Query vars.
	 * @return object
	 */
	private function fake_query( array $vars = array() ) {
		return new class( $vars ) {
			/**
			 * Query vars.
			 *
			 * @var array
			 */
			public $query_vars = array();

			/**
			 * Constructor.
			 *
			 * @param array $vars Query vars.
			 */
			public function __construct( $vars ) {
				$this->query_vars = $vars;
			}

			/**
			 * Get query var.
			 *
			 * @param string $k Key.
			 * @return mixed
			 */
			public function get( $k ) {
				return $this->query_vars[ $k ] ?? null;
			}

			/**
			 * Set query var.
			 *
			 * @param string $k Key.
			 * @param mixed  $v Value.
			 * @return void
			 */
			public function set( $k, $v ) {
				$this->query_vars[ $k ] = $v;
			}
		};
	}

	/**
	 * Test that posts are returned unmodified when not in admin context.
	 *
	 * @test
	 */
	public function returns_posts_unmodified_when_not_in_admin(): void {
		set_current_screen( 'front' );

		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Page',
				'post_type'  => 'page',
			)
		);

		$feedback_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		clean_post_cache( $parent_id );
		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );

		$posts = array( get_post( $feedback_id ) );
		$query = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		$result = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query );

		$this->assertSame( $posts, $result );
		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );
	}

	/**
	 * Test that inputs are returned unmodified when posts parameter is empty or not an array.
	 *
	 * @test
	 */
	public function returns_posts_unmodified_when_posts_is_empty_or_not_array(): void {
		set_current_screen( 'edit-beastfeedbacks' );
		$query = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		$this->assertSame( array(), \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( array(), $query ) );
		$this->assertNull( \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( null, $query ) );
		$this->assertFalse( \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( false, $query ) );
		$this->assertSame( 'invalid_posts', \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( 'invalid_posts', $query ) );
	}

	/**
	 * Test that posts are returned unmodified when query post_type is not beastfeedbacks.
	 *
	 * @test
	 */
	public function returns_posts_unmodified_when_query_post_type_is_not_beastfeedbacks(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$parent_id   = $this->create_post( array( 'post_type' => 'page' ) );
		$feedback_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		clean_post_cache( $parent_id );
		$posts = array( get_post( $feedback_id ) );

		// String non-matching post type.
		$query_post = $this->fake_query( array( 'post_type' => 'post' ) );
		$result     = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query_post );
		$this->assertSame( $posts, $result );
		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );

		// Array non-matching post types.
		$query_array = $this->fake_query( array( 'post_type' => array( 'post', 'page' ) ) );
		$result_arr  = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query_array );
		$this->assertSame( $posts, $result_arr );
		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );

		// Missing post_type query var.
		$query_empty = $this->fake_query( array() );
		$result_emp  = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query_empty );
		$this->assertSame( $posts, $result_emp );
		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );
	}

	/**
	 * Test that caches are primed when query post_type is beastfeedbacks string.
	 *
	 * @test
	 */
	public function primes_caches_when_query_post_type_is_beastfeedbacks_string(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$parent_id   = $this->create_post( array( 'post_type' => 'page' ) );
		$feedback_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);
		add_post_meta( $feedback_id, 'beastfeedbacks_type', 'survey' );

		clean_post_cache( $parent_id );
		wp_cache_delete( $feedback_id, 'post_meta' );

		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );
		$this->assertFalse( wp_cache_get( $feedback_id, 'post_meta' ) );

		$posts = array( get_post( $feedback_id ) );
		$query = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		$result = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query );

		$this->assertSame( $posts, $result );
		$this->assertNotFalse( wp_cache_get( $parent_id, 'posts' ) );
		$this->assertNotFalse( wp_cache_get( $feedback_id, 'post_meta' ) );
	}

	/**
	 * Test that caches are primed when query post_type is an array containing beastfeedbacks.
	 *
	 * @test
	 */
	public function primes_caches_when_query_post_type_is_array_containing_beastfeedbacks(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$parent_id   = $this->create_post( array( 'post_type' => 'page' ) );
		$feedback_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		clean_post_cache( $parent_id );
		wp_cache_delete( $feedback_id, 'post_meta' );

		$posts = array( get_post( $feedback_id ) );
		$query = $this->fake_query( array( 'post_type' => array( 'beastfeedbacks', 'page' ) ) );

		$result = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query );

		$this->assertSame( $posts, $result );
		$this->assertNotFalse( wp_cache_get( $parent_id, 'posts' ) );
		$this->assertNotFalse( wp_cache_get( $feedback_id, 'post_meta' ) );
	}

	/**
	 * Test handling posts with zero or missing parent ID.
	 *
	 * @test
	 */
	public function handles_posts_with_zero_or_no_parent_id_gracefully(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$feedback_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => 0,
			)
		);

		wp_cache_delete( $feedback_id, 'post_meta' );

		$posts = array( get_post( $feedback_id ) );
		$query = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		$result = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query );

		$this->assertSame( $posts, $result );
		$this->assertNotFalse( wp_cache_get( $feedback_id, 'post_meta' ) );
	}

	/**
	 * Test handling duplicate parent IDs in posts array.
	 *
	 * @test
	 */
	public function handles_duplicate_parent_ids_without_error(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$parent_id = $this->create_post( array( 'post_type' => 'page' ) );

		$feedback_id_1 = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);
		$feedback_id_2 = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		clean_post_cache( $parent_id );
		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );

		$posts = array( get_post( $feedback_id_1 ), get_post( $feedback_id_2 ) );
		$query = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		$result = \BeastFeedbacks_Admin::get_instance()->prime_parent_post_caches( $posts, $query );

		$this->assertSame( $posts, $result );
		$this->assertNotFalse( wp_cache_get( $parent_id, 'posts' ) );
	}
}
