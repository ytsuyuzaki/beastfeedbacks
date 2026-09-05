<?php
/**
 * Benchmark for source filter object caching in BeastFeedbacks_Admin.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Benchmark_Source_Filter_Test extends BeastFeedbacks_TestCase {

	public function test_benchmark_add_source_filter_caching(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Create 20 parent pages with feedback posts.
		$parent_ids = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$parent_id    = $this->create_post(
				array(
					'post_title' => "Source Page {$i}",
					'post_type'  => 'page',
				)
			);
			$parent_ids[] = $parent_id;

			for ( $j = 0; $j < 5; $j++ ) {
				$this->create_post(
					array(
						'post_type'   => 'beastfeedbacks',
						'post_parent' => $parent_id,
						'post_status' => 'publish',
					)
				);
			}
		}

		set_current_screen( 'edit-beastfeedbacks' );

		// Case 1: Uncached call
		wp_cache_delete( 'source_filter_parent_ids', 'beastfeedbacks' );

		global $wpdb;
		$queries_before = $wpdb->num_queries;
		$start_time     = microtime( true );

		ob_start();
		$admin->add_source_filter();
		$html_uncached = ob_get_clean();

		$time_uncached    = microtime( true ) - $start_time;
		$queries_uncached = $wpdb->num_queries - $queries_before;

		// Case 2: Cached call (cache populated by first call)
		$queries_before = $wpdb->num_queries;
		$start_time     = microtime( true );

		ob_start();
		$admin->add_source_filter();
		$html_cached = ob_get_clean();

		$time_cached    = microtime( true ) - $start_time;
		$queries_cached = $wpdb->num_queries - $queries_before;

		fwrite( STDERR, "\n=== ADD SOURCE FILTER BENCHMARK (100 feedback posts, 20 sources) ===\n" );
		fwrite( STDERR, sprintf( "Uncached - Queries: %d, Time: %.6f s\n", $queries_uncached, $time_uncached ) );
		fwrite( STDERR, sprintf( "Cached   - Queries: %d, Time: %.6f s\n", $queries_cached, $time_cached ) );

		$this->assertSame( $html_uncached, $html_cached );
		$this->assertLessThan( $queries_uncached, $queries_cached, 'Cached call should execute fewer DB queries than uncached call' );
		$this->assertSame( 0, $queries_cached, 'Cached add_source_filter call should execute 0 additional DB queries' );
	}
}
