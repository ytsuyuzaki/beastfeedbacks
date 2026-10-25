<?php
/**
 * Tests for BeastFeedbacks_Admin::get_parent_permalink_data().
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Data_Extractor::get_parent_permalink_data().
 */
class BeastFeedbacks_Data_Extractor_Get_Parent_Permalink_Data_Test extends BeastFeedbacks_TestCase {

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

		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$data      = $extractor->get_parent_permalink_data( $post_id );

		$expected_url        = get_permalink( $post_id );
		$expected_parsed_url = wp_parse_url( $expected_url );
		$expected_path       = isset( $expected_parsed_url['path'] ) ? $expected_parsed_url['path'] : '';

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'url', $data );
		$this->assertArrayHasKey( 'path', $data );
		$this->assertSame( $expected_url, $data['url'] );
		$this->assertSame( $expected_path, $data['path'] );
	}

	/**
	 * Test static caching behavior for repeated calls with the same parent ID by observing cache hits.
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

		$filter_call_count = 0;
		$filter            = static function ( $permalink ) use ( &$filter_call_count ) {
			$filter_call_count++;
			return $permalink;
		};

		add_filter( 'post_link', $filter );

		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$first     = $extractor->get_parent_permalink_data( $post_id );

		$this->assertSame( 1, $filter_call_count, 'post_link filter should be called once on initial cache miss.' );

		$second = $extractor->get_parent_permalink_data( $post_id );

		$this->assertSame( 1, $filter_call_count, 'post_link filter should not be called on static cache hit.' );

		remove_filter( 'post_link', $filter );

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

		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$data_a    = $extractor->get_parent_permalink_data( $post_id_a );
		$data_b    = $extractor->get_parent_permalink_data( $post_id_b );

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
		$extractor  = \BeastFeedbacks_Data_Extractor::get_instance();
		$data       = $extractor->get_parent_permalink_data( $invalid_id );

		$expected_url        = get_permalink( $invalid_id );
		$expected_parsed_url = wp_parse_url( $expected_url );
		$expected_path       = isset( $expected_parsed_url['path'] ) ? $expected_parsed_url['path'] : '';

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

		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();
		$data      = $extractor->get_parent_permalink_data( $post_id );

		remove_filter( 'post_link', $filter );

		$this->assertSame( 'http://example.com', $data['url'] );
		$this->assertSame( '', $data['path'] );
	}

	/**
	 * Test FIFO eviction strategy when static cache exceeds bound of 100 items.
	 *
	 * @test
	 */
	public function get_parent_permalink_data_evicts_oldest_item_fifo_when_cache_limit_reached(): void {
		$extractor = \BeastFeedbacks_Data_Extractor::get_instance();

		$post_ids = array();
		for ( $i = 1; $i <= 101; $i++ ) {
			$post_ids[ $i ] = $this->create_post(
				array(
					'post_title' => 'FIFO Test Post ' . $i,
					'post_type'  => 'post',
				)
			);
		}

		// Populate cache with items 1 to 100.
		for ( $i = 1; $i <= 100; $i++ ) {
			$extractor->get_parent_permalink_data( $post_ids[ $i ] );
		}

		// Now add the 101st item, which triggers FIFO eviction of item 1.
		$extractor->get_parent_permalink_data( $post_ids[101] );

		$filter_call_count = 0;
		$filter            = static function ( $permalink ) use ( &$filter_call_count ) {
			$filter_call_count++;
			return $permalink;
		};

		add_filter( 'post_link', $filter );

		// Item 2 should still be in cache (no filter call).
		$extractor->get_parent_permalink_data( $post_ids[2] );
		$this->assertSame( 0, $filter_call_count, 'Item 2 should remain cached after FIFO eviction.' );

		// Item 1 was evicted, so querying it triggers a filter call.
		$extractor->get_parent_permalink_data( $post_ids[1] );
		$this->assertSame( 1, $filter_call_count, 'Item 1 should have been evicted FIFO and require re-fetching.' );

		remove_filter( 'post_link', $filter );
	}
}
