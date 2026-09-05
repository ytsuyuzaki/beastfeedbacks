<?php
/**
 * Tests for BeastFeedbacks_Admin::get_parent_permalink_data().
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin::get_parent_permalink_data().
 */
class BeastFeedbacks_Admin_Get_Parent_Permalink_Data_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test retrieving URL and path for a valid post ID.
	 *
	 * @test
	 */
	public function get_parent_permalink_data_returns_url_and_path_for_valid_post(): void {
		$post_id = $this->create_post(
			array(
				'post_title' => 'Parent Sample Post',
				'post_type'  => 'post',
				'post_name'  => 'parent-sample-post',
			)
		);

		$admin = \BeastFeedbacks_Admin::get_instance();
		$data  = $admin->get_parent_permalink_data( $post_id );

		$expected_url        = get_permalink( $post_id );
		$expected_parsed_url = wp_parse_url( $expected_url );
		$expected_path       = esc_html( isset( $expected_parsed_url['path'] ) ? $expected_parsed_url['path'] : '' );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'url', $data );
		$this->assertArrayHasKey( 'path', $data );
		$this->assertSame( $expected_url, $data['url'] );
		$this->assertSame( $expected_path, $data['path'] );
	}

	/**
	 * Test static caching behavior for repeated calls with the same parent ID.
	 *
	 * @test
	 */
	public function get_parent_permalink_data_caches_result_statically(): void {
		$post_id = $this->create_post(
			array(
				'post_title' => 'Cache Test Post',
				'post_type'  => 'post',
			)
		);

		$admin  = \BeastFeedbacks_Admin::get_instance();
		$first  = $admin->get_parent_permalink_data( $post_id );
		$second = $admin->get_parent_permalink_data( $post_id );

		$this->assertSame( $first, $second );
	}

	/**
	 * Test that caching stores separate results for different parent post IDs.
	 *
	 * @test
	 */
	public function get_parent_permalink_data_caches_separately_for_different_post_ids(): void {
		$post_id_a = $this->create_post(
			array(
				'post_title' => 'Post A',
				'post_type'  => 'post',
				'post_name'  => 'post-a',
			)
		);
		$post_id_b = $this->create_post(
			array(
				'post_title' => 'Post B',
				'post_type'  => 'post',
				'post_name'  => 'post-b',
			)
		);

		$admin  = \BeastFeedbacks_Admin::get_instance();
		$data_a = $admin->get_parent_permalink_data( $post_id_a );
		$data_b = $admin->get_parent_permalink_data( $post_id_b );

		$this->assertNotEquals( $post_id_a, $post_id_b );
		$this->assertSame( get_permalink( $post_id_a ), $data_a['url'] );
		$this->assertSame( get_permalink( $post_id_b ), $data_b['url'] );
	}

	/**
	 * Test handling of non-existent post ID.
	 *
	 * @test
	 */
	public function get_parent_permalink_data_handles_non_existent_post_id(): void {
		$invalid_id = 99999999;
		$admin      = \BeastFeedbacks_Admin::get_instance();
		$data       = $admin->get_parent_permalink_data( $invalid_id );

		$expected_url        = get_permalink( $invalid_id );
		$expected_parsed_url = wp_parse_url( $expected_url );
		$expected_path       = esc_html( isset( $expected_parsed_url['path'] ) ? $expected_parsed_url['path'] : '' );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'url', $data );
		$this->assertArrayHasKey( 'path', $data );
		$this->assertSame( $expected_url, $data['url'] );
		$this->assertSame( $expected_path, $data['path'] );
	}

	/**
	 * Test handling of permalink URLs that have no path component.
	 *
	 * @test
	 */
	public function get_parent_permalink_data_handles_url_without_path(): void {
		$post_id = $this->create_post(
			array(
				'post_title' => 'URL Without Path',
				'post_type'  => 'post',
			)
		);

		$filter = static function () {
			return 'http://example.com';
		};

		add_filter( 'post_link', $filter );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$data  = $admin->get_parent_permalink_data( $post_id );

		remove_filter( 'post_link', $filter );

		$this->assertSame( 'http://example.com', $data['url'] );
		$this->assertSame( '', $data['path'] );
	}
}
