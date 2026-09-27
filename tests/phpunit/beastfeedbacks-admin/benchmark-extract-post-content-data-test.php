<?php
/**
 * Benchmark test for extract_post_content_data memory and execution time.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Extract_Post_Content_Data_Benchmark_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function benchmark_extract_post_content_data_caching(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Create sample JSON strings
		$sample_json_strings = array();
		for ( $i = 1; $i <= 50; $i++ ) {
			$sample_json_strings[] = wp_json_encode(
				array(
					'type'        => 'survey',
					'ip_address'  => "192.168.1.{$i}",
					'user_agent'  => "BenchmarkAgent/{$i}",
					'post_params' => array(
						'question_1' => "Answer number {$i} with some long text content to simulate realistic feedback data.",
						'question_2' => array( 'Option A', 'Option B', "Choice {$i}" ),
						'rating'     => (string) ( $i % 5 + 1 ),
					),
				)
			);
		}

		$reflection = new ReflectionMethod( $admin, 'extract_post_content_data' );
		$reflection->setAccessible( true );

		// Verify correctness of cached responses
		$first_res  = $reflection->invoke( $admin, $sample_json_strings[0] );
		$second_res = $reflection->invoke( $admin, $sample_json_strings[0] );
		$this->assertSame( $first_res, $second_res );
		$this->assertTrue( $first_res['is_valid'] );
		$this->assertSame( 'survey', $first_res['type'] );

		// Verify handling of invalid JSON
		$invalid_res = $reflection->invoke( $admin, 'invalid_json_string' );
		$this->assertFalse( $invalid_res['is_valid'] );

		// Run 100,000 extractions across 50 JSON payloads
		$iterations = 100000;
		$start_time = microtime( true );

		for ( $k = 0; $k < $iterations; $k++ ) {
			$json = $sample_json_strings[ $k % 50 ];
			$res  = $reflection->invoke( $admin, $json );
		}

		$time_taken = microtime( true ) - $start_time;

		fwrite( STDERR, "\n=== EXTRACT POST CONTENT DATA BENCHMARK ({$iterations} calls across 50 distinct JSONs) ===\n" );
		fwrite( STDERR, sprintf( "Execution time: %.6f s\n", $time_taken ) );

		$this->assertGreaterThan( 0, $time_taken );
	}
}
