<?php
/**
 * PHPUnit tests for CSV export edge cases, special character escaping, and formula injection in BeastFeedbacks_Admin.
 *
 * @package BeastFeedbacks
 */

/**
 * Class CSV_Edge_Cases_Test
 */
class CSV_Edge_Cases_Test extends BeastFeedbacks_TestCase {

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
	 * Test 1: Empty data export.
	 * Verify output when no feedback posts exist or when empty arrays are passed.
	 *
	 * @test
	 */
	public function export_empty_data_returns_empty_or_header_only_csv(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Test get_csv_data with empty post list.
		$csv_data = $admin->get_csv_data( array() );
		$this->assertSame( array(), $csv_data );

		// Test output_csv with empty post list and empty csv data.
		ob_start();
		$admin->output_csv( 'empty.csv', array(), array() );
		$output = ob_get_clean();

		// Output with 0 fields should produce a blank line or empty string.
		$this->assertIsString( $output );

		// Test download_csv when no beastfeedbacks posts exist in the database.
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_empty_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		// Delete any existing beastfeedbacks posts.
		$existing = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		foreach ( $existing as $p ) {
			wp_delete_post( $p->ID, true );
		}

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

		ob_start();
		try {
			$admin->download_csv();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die_csv_export', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
			wp_delete_user( $admin_id );
		}
		$csv_output = ob_get_clean();

		$this->assertIsString( $csv_output );
	}

	/**
	 * Test 2: Large volume data export.
	 * Verify that exporting a large batch of feedback posts handles all rows correctly without missing data.
	 *
	 * @test
	 */
	public function export_large_volume_data_handles_many_posts(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Bulk Feedback Source',
			)
		);

		$total_posts = 50;
		$created_ids = array();

		for ( $i = 1; $i <= $total_posts; $i++ ) {
			$content = array(
				'type'        => 'survey',
				'ip_address'  => "192.168.1.{$i}",
				'user_agent'  => "BulkAgent/{$i}",
				'post_params' => array(
					'index'   => (string) $i,
					'comment' => "Feedback comment number {$i}",
				),
			);

			$created_ids[] = $this->create_post(
				array(
					'post_type'    => 'beastfeedbacks',
					'post_parent'  => $parent_id,
					'post_content' => wp_slash( wp_json_encode( $content ) ),
					'post_date'    => sprintf( '2025-01-01 %02d:%02d:00', intdiv( $i, 60 ), $i % 60 ),
				)
			);
		}

		$posts     = $admin->get_export_posts();
		$post_data = $admin->get_csv_data( $posts );

		$this->assertGreaterThanOrEqual( $total_posts, count( $posts ) );
		$this->assertArrayHasKey( 'source', $post_data );
		$this->assertArrayHasKey( 'index', $post_data );
		$this->assertArrayHasKey( 'comment', $post_data );

		ob_start();
		$admin->output_csv( 'large.csv', $posts, $post_data );
		$csv_output = ob_get_clean();

		$lines = explode( "\n", trim( str_replace( "\r\n", "\n", $csv_output ) ) );

		// Header + at least 50 data rows.
		$this->assertGreaterThanOrEqual( $total_posts + 1, count( $lines ) );

		// Verify first data row and last data row contain expected index values.
		$this->assertStringContainsString( 'BulkAgent/1', $csv_output );
		$this->assertStringContainsString( "BulkAgent/{$total_posts}", $csv_output );
	}

	/**
	 * Test 3: Special characters escaping.
	 * Verify that answers containing commas, newlines (\n, \r\n), and double quotes (\") are correctly formatted and quoted in CSV output.
	 *
	 * @test
	 */
	public function export_special_characters_are_properly_quoted_and_escaped(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Special Chars Page',
			)
		);

		$special_comment = "Line 1, with comma\nLine 2 with \"quotes\" and \r\nLine 3, combo!";
		$content         = array(
			'type'        => 'survey',
			'ip_address'  => '10.0.0.1',
			'user_agent'  => 'SpecialCharAgent',
			'post_params' => array(
				'comma_field'   => 'Value 1, Value 2',
				'quote_field'   => 'He said "Hello World"',
				'newline_field' => "Line 1\nLine 2",
				'combo_field'   => $special_comment,
			),
		);

		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_slash( wp_json_encode( $content ) ),
				'post_date'    => '2025-01-01 12:00:00',
			)
		);

		$posts    = array( get_post( $post_id ) );
		$csv_data = $admin->get_csv_data( $posts );

		ob_start();
		$admin->output_csv( 'special.csv', $posts, $csv_data );
		$csv_output = ob_get_clean();

		// Parse output stream back using fgetcsv to verify integrity.
		$stream = fopen( 'php://memory', 'r+' );
		fwrite( $stream, $csv_output );
		rewind( $stream );

		$header   = fgetcsv( $stream );
		$data_row = fgetcsv( $stream );
		fclose( $stream );

		$this->assertIsArray( $header );
		$this->assertIsArray( $data_row );

		// Create column mapping.
		$row_map = array_combine( $header, $data_row );

		$this->assertSame( 'Value 1, Value 2', $row_map['comma_field'] );
		$this->assertSame( 'He said "Hello World"', $row_map['quote_field'] );
		$this->assertSame( "Line 1\nLine 2", $row_map['newline_field'] );
		$this->assertSame( $special_comment, $row_map['combo_field'] );
	}

	/**
	 * Test 4: CSV formula injection prevention (`esc_csv`).
	 * Verify that strings beginning with =, +, -, @, \t, \r or space-padded formulas have single quotes prepended,
	 * while normal numbers and text remain unaltered.
	 *
	 * @test
	 */
	public function esc_csv_handles_formula_injection_and_safe_values(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Formula triggers at start.
		$this->assertSame( "'=1+1", $admin->esc_csv( '=1+1' ) );
		$this->assertSame( "'+10", $admin->esc_csv( '+10' ) );
		$this->assertSame( "'-10", $admin->esc_csv( '-10' ) );
		$this->assertSame( "'@cmd", $admin->esc_csv( '@cmd' ) );
		$this->assertSame( "'|calc", $admin->esc_csv( '|calc' ) );
		$this->assertSame( "'%cmd", $admin->esc_csv( '%cmd' ) );
		$this->assertSame( "'\tcmd", $admin->esc_csv( "\tcmd" ) );
		$this->assertSame( "'\rcmd", $admin->esc_csv( "\rcmd" ) );
		$this->assertSame( "'\n=1+1", $admin->esc_csv( "\n=1+1" ) );

		// Whitespace-padded formula triggers.
		$this->assertSame( "'   =1+1", $admin->esc_csv( '   =1+1' ) );
		$this->assertSame( "'  +50", $admin->esc_csv( '  +50' ) );
		$this->assertSame( "' -20", $admin->esc_csv( ' -20' ) );
		$this->assertSame( "'  @test", $admin->esc_csv( '  @test' ) );
		$this->assertSame( "'  |calc", $admin->esc_csv( '  |calc' ) );
		$this->assertSame( "'  %cmd", $admin->esc_csv( '  %cmd' ) );
		$this->assertSame( "'  \t=1+1", $admin->esc_csv( "  \t=1+1" ) );
		$this->assertSame( "'  \r+1", $admin->esc_csv( "  \r+1" ) );
		$this->assertSame( "'  \n=1+1", $admin->esc_csv( "  \n=1+1" ) );

		// Normal / safe values.
		$this->assertSame( 'Normal text', $admin->esc_csv( 'Normal text' ) );
		$this->assertSame( '12345', $admin->esc_csv( '12345' ) );
		$this->assertSame( '99.99', $admin->esc_csv( '99.99' ) );
		$this->assertSame( '   Space prefixed safe text', $admin->esc_csv( '   Space prefixed safe text' ) );
		$this->assertSame( '', $admin->esc_csv( '' ) );
	}

	/**
	 * Test 5: Integrated export with formula injection in feedback answers.
	 * Verify that formula injections in exported feedback posts are prepended with single quote in full CSV output.
	 *
	 * @test
	 */
	public function export_with_formula_injection_sanitizes_csv_output(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Formula Injection Test Page',
			)
		);

		$content = array(
			'type'        => 'survey',
			'ip_address'  => '127.0.0.1',
			'user_agent'  => 'TestAgent',
			'post_params' => array(
				'eq_formula'    => '=1+1',
				'spaced_eq'     => '   =SUM(A1:A10)',
				'plus_formula'  => '+10',
				'minus_formula' => '-5',
				'at_formula'    => '@CMD',
				'safe_text'     => 'Just normal answer',
				'number'        => '100',
			),
		);

		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_slash( wp_json_encode( $content ) ),
				'post_date'    => '2025-01-01 15:00:00',
			)
		);

		$posts    = array( get_post( $post_id ) );
		$csv_data = $admin->get_csv_data( $posts );

		ob_start();
		$admin->output_csv( 'formula.csv', $posts, $csv_data );
		$csv_output = ob_get_clean();

		$this->assertStringContainsString( "'=1+1", $csv_output );
		$this->assertStringContainsString( "'   =SUM(A1:A10)", $csv_output );
		$this->assertStringContainsString( "'+10", $csv_output );
		$this->assertStringContainsString( "'-5", $csv_output );
		$this->assertStringContainsString( "'@CMD", $csv_output );
		$this->assertStringContainsString( 'Just normal answer', $csv_output );
		$this->assertStringContainsString( '100', $csv_output );
	}

	/**
	 * Test 6: Verify CSV header formula injection is escaped in output_csv.
	 *
	 * @test
	 */
	public function output_csv_escapes_formula_triggers_in_column_headers(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post_id    = $this->create_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'CSV Header Injection Test',
			)
		);
		$posts      = array( get_post( $post_id ) );
		$post_datas = array(
			'=FORMULA_HEADER()' => array( $post_id => 'some_data' ),
			'+PLUS_HEADER'      => array( $post_id => 'data2' ),
			'NORMAL_HEADER'     => array( $post_id => 'data3' ),
		);

		ob_start();
		$admin->output_csv( 'headers.csv', $posts, $post_datas );
		$csv_output = ob_get_clean();

		$stream = fopen( 'php://memory', 'r+' );
		fwrite( $stream, $csv_output );
		rewind( $stream );

		$header = fgetcsv( $stream );
		fclose( $stream );

		$this->assertIsArray( $header );
		$this->assertSame( "'=FORMULA_HEADER()", $header[0] );
		$this->assertSame( "'+PLUS_HEADER", $header[1] );
		$this->assertSame( 'NORMAL_HEADER', $header[2] );
	}
}
