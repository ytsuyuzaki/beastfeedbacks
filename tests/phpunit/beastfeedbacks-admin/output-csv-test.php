<?php
/**
 * Tests for BeastFeedbacks_Admin::output_csv().
 *
 * @package BeastFeedbacks
 */

/**
 * Tests for BeastFeedbacks_Admin::output_csv().
 */
class BeastFeedbacks_Admin_Output_Csv_Test extends BeastFeedbacks_TestCase {

	/**
	 * Clean up request globals and user context after each test.
	 */
	protected function tear_down(): void {
		$_REQUEST = array();
		$_GET     = array();
		$_POST    = array();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test output_csv happy path with formatted posts and post_datas map.
	 *
	 * @test
	 */
	public function output_csv_exports_valid_headers_and_row_data(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$parent_id = $this->create_post( array( 'post_type' => 'page' ) );

		$post1 = get_post( $this->create_survey_post( $parent_id, array( 'q1' => 'Answer 1' ) ) );
		$post2 = get_post( $this->create_vote_post( $parent_id, 'Choice A' ) );

		$post_datas = array(
			'source'   => array(
				$post1->ID => '/test-page',
				$post2->ID => '/test-page',
			),
			'type'     => array(
				$post1->ID => 'survey',
				$post2->ID => 'vote',
			),
			'q1'       => array(
				$post1->ID => 'Answer 1',
				$post2->ID => '',
			),
			'selected' => array(
				$post1->ID => '',
				$post2->ID => 'Choice A',
			),
		);

		ob_start();
		$admin->output_csv( 'export-test.csv', array( $post1, $post2 ), $post_datas );
		$csv_output = ob_get_clean();

		$stream = fopen( 'php://memory', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $stream, $csv_output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $stream );

		$header = fgetcsv( $stream );
		$row1   = fgetcsv( $stream );
		$row2   = fgetcsv( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertSame( array( 'source', 'type', 'q1', 'selected' ), $header );

		$map1 = array_combine( $header, $row1 );
		$this->assertSame( '/test-page', $map1['source'] );
		$this->assertSame( 'survey', $map1['type'] );
		$this->assertSame( 'Answer 1', $map1['q1'] );
		$this->assertSame( '', $map1['selected'] );

		$map2 = array_combine( $header, $row2 );
		$this->assertSame( '/test-page', $map2['source'] );
		$this->assertSame( 'vote', $map2['type'] );
		$this->assertSame( '', $map2['q1'] );
		$this->assertSame( 'Choice A', $map2['selected'] );
	}

	/**
	 * Test output_csv defaults missing post ID entries to empty string.
	 *
	 * @test
	 */
	public function output_csv_handles_missing_post_keys_gracefully(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post1 = get_post( $this->create_like_post() );
		$post2 = get_post( $this->create_like_post() );

		// $post_datas missing entries for $post2->ID in field 'extra_col'.
		$post_datas = array(
			'type'      => array(
				$post1->ID => 'like',
				$post2->ID => 'like',
			),
			'extra_col' => array(
				$post1->ID => 'Value 1',
				// $post2->ID is intentionally absent.
			),
		);

		ob_start();
		$admin->output_csv( 'sparse-export.csv', array( $post1, $post2 ), $post_datas );
		$csv_output = ob_get_clean();

		$stream = fopen( 'php://memory', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $stream, $csv_output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $stream );

		$header = fgetcsv( $stream );
		$row1   = fgetcsv( $stream );
		$row2   = fgetcsv( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertSame( array( 'type', 'extra_col' ), $header );

		$map1 = array_combine( $header, $row1 );
		$this->assertSame( 'like', $map1['type'] );
		$this->assertSame( 'Value 1', $map1['extra_col'] );

		$map2 = array_combine( $header, $row2 );
		$this->assertSame( 'like', $map2['type'] );
		$this->assertSame( '', $map2['extra_col'] );
	}

	/**
	 * Test output_csv escapes formula triggers in headers and cell values.
	 *
	 * @test
	 */
	public function output_csv_escapes_formula_triggers_in_headers_and_data(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post = get_post( $this->create_like_post() );

		$post_datas = array(
			'=FORMULA_HEADER' => array(
				$post->ID => '=1+1',
			),
			'+PLUS_HEADER'    => array(
				$post->ID => '+50',
			),
			'@AT_HEADER'      => array(
				$post->ID => '@cmd',
			),
		);

		ob_start();
		$admin->output_csv( 'esc-export.csv', array( $post ), $post_datas );
		$csv_output = ob_get_clean();

		$stream = fopen( 'php://memory', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $stream, $csv_output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $stream );

		$header = fgetcsv( $stream );
		$row    = fgetcsv( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertSame( "' =FORMULA_HEADER", $header[0] );
		$this->assertSame( "' +PLUS_HEADER", $header[1] );
		$this->assertSame( "' @AT_HEADER", $header[2] );

		$this->assertSame( "' =1+1", $row[0] );
		$this->assertSame( "' +50", $row[1] );
		$this->assertSame( "' @cmd", $row[2] );
	}

	/**
	 * Test output_csv edge cases with empty arrays.
	 *
	 * @test
	 */
	public function output_csv_handles_empty_posts_and_empty_datas(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Case 1: Empty posts and empty datas.
		ob_start();
		$admin->output_csv( 'empty.csv', array(), array() );
		$output1 = ob_get_clean();
		$this->assertSame( "\n", str_replace( "\r\n", "\n", $output1 ) );

		// Case 2: Empty posts with non-empty datas (headers-only).
		$post_datas = array(
			'col1' => array(),
			'col2' => array(),
		);

		ob_start();
		$admin->output_csv( 'headers-only.csv', array(), $post_datas );
		$output2 = ob_get_clean();

		$lines = explode( "\n", trim( str_replace( "\r\n", "\n", $output2 ) ) );
		$this->assertCount( 1, $lines );
		$this->assertSame( 'col1,col2', $lines[0] );
	}

	/**
	 * Test output_csv formats array and mixed scalar values correctly via esc_csv.
	 *
	 * @test
	 */
	public function output_csv_formats_array_and_mixed_types_in_post_datas(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post = get_post( $this->create_like_post() );

		$post_datas = array(
			'array_col' => array(
				$post->ID => array( 'opt1', 'opt2' ),
			),
			'num_col'   => array(
				$post->ID => 12345,
			),
			'float_col' => array(
				$post->ID => 99.9,
			),
		);

		ob_start();
		$admin->output_csv( 'mixed.csv', array( $post ), $post_datas );
		$csv_output = ob_get_clean();

		$stream = fopen( 'php://memory', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $stream, $csv_output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $stream );

		$header = fgetcsv( $stream );
		$row    = fgetcsv( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$map = array_combine( $header, $row );
		$this->assertSame( 'opt1,opt2', $map['array_col'] );
		$this->assertSame( '12345', $map['num_col'] );
		$this->assertSame( '99.9', $map['float_col'] );
	}
}
