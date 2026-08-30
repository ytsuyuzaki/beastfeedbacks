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

		// Posts 4 to 500: Fill first chunk to test crossing 500-item chunk boundary.
		for ( $i = 4; $i <= 500; $i++ ) {
			$this->create_post(
				array(
					'post_type'    => 'beastfeedbacks',
					'post_parent'  => 0,
					'post_content' => wp_slash(
						wp_json_encode(
							array(
								'type'        => 'like',
								'ip_address'  => "192.0.2.{$i}",
								'user_agent'  => "DummyAgent{$i}",
								'post_params' => array(),
							)
						)
					),
					'post_date'    => '2025-01-04 00:00:00',
				)
			);
		}

		// Post 501: Second chunk post with unique field 'chunk2_field' to verify multi-chunk header aggregation.
		$content501 = array(
			'type'        => 'survey',
			'ip_address'  => '192.0.2.200',
			'user_agent'  => 'Chunk2Agent',
			'post_params' => array(
				'chunk2_field' => 'Chunk 2 Value',
			),
		);
		$this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_slash( wp_json_encode( $content501 ) ),
				'post_date'    => '2025-01-05 10:00:00',
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
		$rows   = array();
		while ( ( $row = fgetcsv( $stream ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$rows[] = $row;
		}
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$this->assertIsArray( $header );
		$this->assertCount( 501, $rows );

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
		$this->assertContains( 'chunk2_field', $header );

		// Verify row 1 mapping (Survey post).
		$map1 = array_combine( $header, $rows[0] );
		$this->assertSame( 'survey', $map1['type'] );
		$this->assertSame( '192.0.2.10', $map1['ip_address'] );
		$this->assertSame( 'StreamAgent1', $map1['user_agent'] );
		$this->assertSame( 'Answer 1', $map1['q1'] );
		$this->assertSame( 'tag1,tag2', $map1['tags'] );
		$this->assertSame( "'=SUM(1,1)", $map1['formula'] );
		$this->assertSame( '', $map1['chunk2_field'] );

		// Verify row 2 mapping (Vote post).
		$map2 = array_combine( $header, $rows[1] );
		$this->assertSame( 'vote', $map2['type'] );
		$this->assertSame( '192.0.2.11', $map2['ip_address'] );
		$this->assertSame( 'StreamAgent2', $map2['user_agent'] );
		$this->assertSame( 'Option B', $map2['q2'] );
		$this->assertSame( 'Yes', $map2['selected'] );
		$this->assertSame( '', $map2['chunk2_field'] );

		// Verify row 3 mapping (Invalid JSON post).
		$map3 = array_combine( $header, $rows[2] );
		$this->assertSame( '', $map3['source'] );
		$this->assertSame( '', $map3['type'] );
		$this->assertSame( '', $map3['ip_address'] );
		$this->assertSame( '2025-01-03 14:00:00', $map3['date'] );
		$this->assertSame( '', $map3['chunk2_field'] );

		// Verify row 501 mapping (Post 501 in chunk 2).
		$map501         = array_combine( $header, $rows[500] );
		$permalink_data = $admin->get_parent_permalink_data( $parent_id );
		$this->assertSame( $permalink_data['path'], $map501['source'] );
		$this->assertSame( 'survey', $map501['type'] );
		$this->assertSame( '192.0.2.200', $map501['ip_address'] );
		$this->assertSame( 'Chunk2Agent', $map501['user_agent'] );
		$this->assertSame( 'Chunk 2 Value', $map501['chunk2_field'] );
		$this->assertSame( '', $map501['q1'] );
	}
}
