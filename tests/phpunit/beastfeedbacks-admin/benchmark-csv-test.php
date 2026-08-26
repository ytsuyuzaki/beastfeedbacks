<?php
/**
 * Benchmark test for CSV export memory and execution time.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_CSV_Benchmark_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function benchmark_download_csv_memory_and_time(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Benchmark Parent Page',
			)
		);

		// Seed 500 posts with JSON payload
		$num_posts = 500;
		for ( $i = 1; $i <= $num_posts; $i++ ) {
			$content = array(
				'type'        => 'survey',
				'ip_address'  => "192.168.1.{$i}",
				'user_agent'  => "BenchmarkAgent/{$i}",
				'post_params' => array(
					'question_1' => "Answer number {$i} with some long text content to simulate realistic feedback data.",
					'question_2' => array( 'Option A', 'Option B', "Choice {$i}" ),
					'rating'     => (string) ( $i % 5 + 1 ),
				),
			);

			$this->create_post(
				array(
					'post_type'    => 'beastfeedbacks',
					'post_parent'  => $parent_id,
					'post_content' => wp_slash( wp_json_encode( $content ) ),
					'post_date'    => '2025-01-01 10:00:00',
				)
			);
		}

		$admin_id = wp_insert_user(
			array(
				'user_login' => 'bench_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		$nonce                = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$_REQUEST['_wpnonce'] = $nonce;
		$_GET['_wpnonce']     = $nonce;

		$die_handler = static function () {
			return static function () {
				throw new RuntimeException( 'wp_die_csv_export' );
			};
		};

		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );

		gc_collect_cycles();
		$mem_start  = memory_get_usage();
		$peak_start = memory_get_peak_usage();
		$time_start = microtime( true );

		ob_start();
		try {
			$admin->download_csv();
		} catch ( RuntimeException $e ) {
			// Expected wp_die
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
		}
		$csv_output = ob_get_clean();

		$time_end  = microtime( true );
		$peak_end  = memory_get_peak_usage();

		$time_sec = round( $time_end - $time_start, 4 );
		$peak_mb  = round( ( $peak_end - $peak_start ) / 1024 / 1024, 2 );

		fwrite( STDERR, "\n=== CSV EXPORT BENCHMARK ({$num_posts} posts) ===\n" );
		fwrite( STDERR, "Execution time: {$time_sec} s\n" );
		fwrite( STDERR, "Peak memory allocated during export: {$peak_mb} MB\n" );
		fwrite( STDERR, "CSV byte length: " . strlen( $csv_output ) . " bytes\n" );

		$this->assertGreaterThan( 0, strlen( $csv_output ) );
	}
}
