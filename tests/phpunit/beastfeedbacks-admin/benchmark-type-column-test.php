<?php
/**
 * Benchmark for post meta caching in BeastFeedbacks_Admin list table columns.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Benchmark_Type_Column_Test extends BeastFeedbacks_TestCase {

	public function test_benchmark_render_type_column_meta_queries(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post_ids = array();
		for ( $i = 0; $i < 50; $i++ ) {
			$id = $this->create_post(
				array(
					'post_type'   => 'beastfeedbacks',
					'post_status' => 'publish',
				)
			);
			add_post_meta( $id, 'beastfeedbacks_type', 'survey' );
			$post_ids[] = $id;
		}

		// Case 1: Without meta caching (simulating uncached meta retrieval by clearing cache)
		foreach ( $post_ids as $id ) {
			wp_cache_delete( $id, 'post_meta' );
		}

		global $wpdb;
		$queries_before = $wpdb->num_queries;
		$start_time = microtime( true );

		// Simulate rendering column without update_post_meta_cache
		foreach ( $post_ids as $id ) {
			// Clear cache for each loop to simulate separate get_post_meta DB calls if meta cache wasn't bulk loaded
			wp_cache_delete( $id, 'post_meta' );
			ob_start();
			$admin->manage_posts_custom_column( 'beastfeedbacks_type', $id );
			ob_end_clean();
		}

		$time_uncached = microtime( true ) - $start_time;
		$queries_uncached = $wpdb->num_queries - $queries_before;

		// Case 2: With update_postmeta_cache / bulk pre-fetching
		$posts = array_map( 'get_post', $post_ids );
		update_postmeta_cache( $post_ids );

		$queries_before = $wpdb->num_queries;
		$start_time = microtime( true );

		foreach ( $post_ids as $id ) {
			ob_start();
			$admin->manage_posts_custom_column( 'beastfeedbacks_type', $id );
			ob_end_clean();
		}

		$time_cached = microtime( true ) - $start_time;
		$queries_cached = $wpdb->num_queries - $queries_before;

		echo "\n=== RENDER TYPE COLUMN BENCHMARK (50 posts) ===\n";
		echo sprintf( "Uncached - Queries: %d, Time: %.6f s\n", $queries_uncached, $time_uncached );
		echo sprintf( "Cached   - Queries: %d, Time: %.6f s\n", $queries_cached, $time_cached );

		$this->assertSame( 0, $queries_cached, 'Cached column rendering should require 0 additional DB queries' );
	}
}
