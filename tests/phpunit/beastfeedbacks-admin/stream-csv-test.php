<?php
/**
 * Tests for BeastFeedbacks_Admin::stream_csv().
 *
 * @package BeastFeedbacks
 */

/**
 * Tests for BeastFeedbacks_Admin::stream_csv().
 */
class BeastFeedbacks_Admin_Stream_Csv_Test extends BeastFeedbacks_TestCase {

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
	 * Delete all existing beastfeedbacks posts in database.
	 */
	private function delete_all_feedback_posts(): void {
		$existing = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			)
		);
		foreach ( $existing as $p ) {
			wp_delete_post( $p->ID, true );
		}
	}

	/**
	 * Test stream_csv with empty feedback posts.
	 *
	 * @test
	 */
	public function stream_csv_handles_empty_posts(): void {
		$this->delete_all_feedback_posts();

		$admin    = \BeastFeedbacks_Admin::get_instance();
		$filename = 'empty-export.csv';

		ob_start();
		$admin->stream_csv( $filename );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * Test stream_csv output with chunking, field collection, parent permalink resolution, and escaping.
	 *
	 * @test
	 */
	public function stream_csv_outputs_csv_data_with_chunking_headers_and_escaping(): void {
		$this->delete_all_feedback_posts();

		$admin = \BeastFeedbacks_Admin::get_instance();

		// Create parent post for permalink resolution.
		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Stream Source Page',
			)
		);

		// Post 1: Survey with formula injection, array value, and custom field 'q1'.
		$content1 = array(
			'type'        => 'survey',
			'ip_address'  => '192.0.2.10',
			'user_agent'  => 'StreamAgent1',
			'post_params' => array(
				'q1'      => 'Answer 1',
				'tags'    => array( 'tag1', 'tag2' ),
				'formula' => '=SUM(1,1)',
			),
		);
		$this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_slash( wp_json_encode( $content1 ) ),
				'post_date'    => '2025-01-01 10:00:00',
			)
		);

		// Post 2: Vote with custom field 'q2'.
		$content2 = array(
			'type'        => 'vote',
			'ip_address'  => '192.0.2.11',
			'user_agent'  => 'StreamAgent2',
			'post_params' => array(
				'q2'       => 'Option B',
				'selected' => 'Yes',
			),
		);
		$this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_slash( wp_json_encode( $content2 ) ),
				'post_date'    => '2025-01-02 12:00:00',
			)
		);

		// Post 3: Invalid JSON content fallback.
		$this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => 0,
				'post_content' => 'invalid-json-string',
				'post_date'    => '2025-01-03 14:00:00',
			)
		);

		ob_start();
		$admin->stream_csv( 'stream-test.csv' );
		$csv_output = ob_get_clean();

		$this->assertNotEmpty( $csv_output );

		// Parse output stream back via fgetcsv to verify full structure.
		$stream = fopen( 'php://memory', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $stream, $csv_output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $stream );

		$header = fgetcsv( $stream );
		$row1   = fgetcsv( $stream );
		$row2   = fgetcsv( $stream );
		$row3   = fgetcsv( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertIsArray( $header );
		$this->assertIsArray( $row1 );
		$this->assertIsArray( $row2 );
		$this->assertIsArray( $row3 );

		// Check default and dynamic field headers collected across all chunks.
		$this->assertContains( 'source', $header );
		$this->assertContains( 'date', $header );
		$this->assertContains( 'type', $header );
		$this->assertContains( 'ip_address', $header );
		$this->assertContains( 'user_agent', $header );
		$this->assertContains( 'q1', $header );
		$this->assertContains( 'tags', $header );
		$this->assertContains( 'formula', $header );
		$this->assertContains( 'q2', $header );
		$this->assertContains( 'selected', $header );

		// Verify row 1 mapping (Survey post).
		$map1 = array_combine( $header, $row1 );
		$this->assertSame( 'survey', $map1['type'] );
		$this->assertSame( '192.0.2.10', $map1['ip_address'] );
		$this->assertSame( 'StreamAgent1', $map1['user_agent'] );
		$this->assertSame( 'Answer 1', $map1['q1'] );
		$this->assertSame( 'tag1,tag2', $map1['tags'] );
		$this->assertSame( "'=SUM(1,1)", $map1['formula'] );

		// Verify row 2 mapping (Vote post).
		$map2 = array_combine( $header, $row2 );
		$this->assertSame( 'vote', $map2['type'] );
		$this->assertSame( '192.0.2.11', $map2['ip_address'] );
		$this->assertSame( 'StreamAgent2', $map2['user_agent'] );
		$this->assertSame( 'Option B', $map2['q2'] );
		$this->assertSame( 'Yes', $map2['selected'] );

		// Verify row 3 mapping (Invalid JSON post).
		$map3 = array_combine( $header, $row3 );
		$this->assertSame( '', $map3['source'] );
		$this->assertSame( '', $map3['type'] );
		$this->assertSame( '', $map3['ip_address'] );
		$this->assertSame( '2025-01-03 14:00:00', $map3['date'] );
	}
}
