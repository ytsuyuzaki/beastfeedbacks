<?php
/**
 * Tests for BeastFeedbacks_Admin::download_csv() and BeastFeedbacks_Admin::esc_csv().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Download_Csv_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		$_REQUEST = array();
		$_GET     = array();
		$_POST    = array();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/** @test */
	public function esc_csv_prefixes_when_dangerous_first_char(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$this->assertSame( "' =SUM(A1:A2)", $admin->esc_csv( '=SUM(A1:A2)' ) );
		$this->assertSame( "' +1+2", $admin->esc_csv( '+1+2' ) );
		$this->assertSame( "' -1", $admin->esc_csv( '-1' ) );
		$this->assertSame( "' @cmd", $admin->esc_csv( '@cmd' ) );
		$this->assertSame( "' \tcmd", $admin->esc_csv( "\tcmd" ) );
		$this->assertSame( "' \rcmd", $admin->esc_csv( "\rcmd" ) );
		$this->assertSame( "'  =SUM(A1:A2)", $admin->esc_csv( ' =SUM(A1:A2)' ) );
		$this->assertSame( "'   +1+2", $admin->esc_csv( '  +1+2' ) );

		$this->assertSame( 'safe', $admin->esc_csv( 'safe' ) );
		$this->assertSame( '  space', $admin->esc_csv( '  space' ) );
	}

	/** @test */
	public function output_csv_executes_header_setting_without_error(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$posts      = array();
		$post_datas = array(
			'source' => array(),
			'type'   => array(),
		);
		$filename   = 'test-export-2025-01-01.csv';

		ob_start();
		$admin->output_csv( $filename, $posts, $post_datas );
		$csv_output = ob_get_clean();

		$this->assertStringContainsString( 'source,type', $csv_output );
	}

	/** @test */
	public function download_csv_fails_without_edit_pages_capability(): void {
		$subscriber_id = wp_insert_user(
			array(
				'user_login' => 'test_subscriber_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $subscriber_id );

		$nonce                = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$_REQUEST['_wpnonce'] = $nonce;
		$_GET['_wpnonce']     = $nonce;

		$die_handler = static function () {
			return static function ( $message = '', $title = '', $args = array() ) {
				$code = is_array( $args ) && isset( $args['response'] ) ? $args['response'] : 500;
				throw new RuntimeException( 'wp_die_permission_denied_' . $code );
			};
		};

		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );

		try {
			\BeastFeedbacks_Admin::get_instance()->download_csv();
			$this->fail( 'download_csv did not die when user lacked edit_pages capability' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die_permission_denied_403', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
			wp_delete_user( $subscriber_id );
			wp_set_current_user( 0 );
		}
	}

	/** @test */
	public function download_csv_fails_without_valid_nonce(): void {
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		$_REQUEST = array();
		$_GET     = array();
		$_POST    = array();

		$die_handler = static function () {
			return static function () {
				throw new RuntimeException( 'wp_die_nonce_invalid' );
			};
		};

		add_filter( 'wp_die_ajax_handler', $die_handler );
		add_filter( 'wp_die_handler', $die_handler );

		try {
			\BeastFeedbacks_Admin::get_instance()->download_csv();
			$this->fail( 'download_csv did not die when nonce was missing' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die_nonce_invalid', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
			wp_delete_user( $admin_id );
			wp_set_current_user( 0 );
		}
	}

	/** @test */
	public function download_csv_outputs_csv_with_feedback_data(): void {
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		// Parent post for permalink source
		$parent_id = $this->create_post(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Export Parent Page',
			)
		);

		// Post 1: Survey with array params and formula injection input
		$content1 = array(
			'type'        => 'survey',
			'ip_address'  => '192.0.2.1',
			'user_agent'  => 'AgentOne',
			'post_params' => array(
				'satisfaction' => 'high',
				'features'     => array( 'fast', 'reliable' ),
				'formula'      => '=SUM(1,2)',
			),
		);
		$post_id1 = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_json_encode( $content1 ),
				'post_date'    => '2025-01-01 10:00:00',
			)
		);

		// Post 2: Vote with different params
		$content2 = array(
			'type'        => 'vote',
			'ip_address'  => '192.0.2.2',
			'user_agent'  => 'AgentTwo',
			'post_params' => array(
				'satisfaction' => 'medium',
				'selected'     => 'Option A',
			),
		);
		$post_id2 = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => wp_json_encode( $content2 ),
				'post_date'    => '2025-01-02 10:00:00',
			)
		);

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
			\BeastFeedbacks_Admin::get_instance()->download_csv();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die_csv_export', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
			wp_delete_user( $admin_id );
		}
		$csv_output = ob_get_clean();

		$lines = explode( "\n", trim( str_replace( "\r\n", "\n", $csv_output ) ) );
		$this->assertGreaterThanOrEqual( 3, count( $lines ) );

		// Check header row contains expected field names
		$header = $lines[0];
		$this->assertStringContainsString( 'source', $header );
		$this->assertStringContainsString( 'date', $header );
		$this->assertStringContainsString( 'type', $header );
		$this->assertStringContainsString( 'ip_address', $header );
		$this->assertStringContainsString( 'user_agent', $header );
		$this->assertStringContainsString( 'satisfaction', $header );
		$this->assertStringContainsString( 'features', $header );
		$this->assertStringContainsString( 'formula', $header );

		// Check Row 1 (Survey feedback)
		$this->assertStringContainsString( 'survey', $lines[1] );
		$this->assertStringContainsString( '192.0.2.1', $lines[1] );
		$this->assertStringContainsString( 'AgentOne', $lines[1] );
		$this->assertStringContainsString( 'high', $lines[1] );
		$this->assertStringContainsString( 'fast,reliable', $lines[1] );
		$this->assertStringContainsString( "' =SUM(1,2)", $lines[1] );

		// Check Row 2 (Vote feedback)
		$this->assertStringContainsString( 'vote', $lines[2] );
		$this->assertStringContainsString( '192.0.2.2', $lines[2] );
		$this->assertStringContainsString( 'AgentTwo', $lines[2] );
		$this->assertStringContainsString( 'medium', $lines[2] );
		$this->assertStringContainsString( 'Option A', $lines[2] );
	}

	/** @test */
	public function download_csv_handles_no_posts(): void {
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		// Delete any existing beastfeedbacks posts to ensure empty list
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
			\BeastFeedbacks_Admin::get_instance()->download_csv();
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

	/** @test */
	public function get_export_posts_respects_type_and_source_filters(): void {
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		$parent1 = $this->create_post( array( 'post_type' => 'page' ) );
		$parent2 = $this->create_post( array( 'post_type' => 'page' ) );

		// Post 1: type survey, parent1
		$post1 = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent1,
				'post_content' => wp_json_encode( array( 'type' => 'survey' ) ),
			)
		);
		add_post_meta( $post1, 'beastfeedbacks_type', 'survey' );

		// Post 2: type vote, parent1
		$post2 = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent1,
				'post_content' => wp_json_encode( array( 'type' => 'vote' ) ),
			)
		);
		add_post_meta( $post2, 'beastfeedbacks_type', 'vote' );

		// Post 3: type survey, parent2
		$post3 = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent2,
				'post_content' => wp_json_encode( array( 'type' => 'survey' ) ),
			)
		);
		add_post_meta( $post3, 'beastfeedbacks_type', 'survey' );

		$admin = \BeastFeedbacks_Admin::get_instance();
		$admin->init();
		$nonce = wp_create_nonce( 'beastfeedbacks_csv_export' );

		// 1. Filter by type 'vote'
		$_POST                             = array(
			'_wpnonce'           => $nonce,
			'beastfeedbacks_type' => 'vote',
		);
		$_REQUEST                          = $_POST;
		$posts                             = $admin->get_export_posts();
		$post_ids                          = wp_list_pluck( $posts, 'ID' );
		$this->assertContains( $post2, $post_ids );
		$this->assertNotContains( $post1, $post_ids );
		$this->assertNotContains( $post3, $post_ids );

		// 2. Filter by source (parent1)
		$_POST                             = array(
			'_wpnonce'                => $nonce,
			'beastfeedbacks_parent_id' => $parent1,
		);
		$_REQUEST                          = $_POST;
		$posts                             = $admin->get_export_posts();
		$post_ids                          = wp_list_pluck( $posts, 'ID' );
		$this->assertContains( $post1, $post_ids );
		$this->assertContains( $post2, $post_ids );
		$this->assertNotContains( $post3, $post_ids );

		// 3. Filter by type 'survey' AND parent1
		$_POST                             = array(
			'_wpnonce'                => $nonce,
			'beastfeedbacks_type'      => 'survey',
			'beastfeedbacks_parent_id' => $parent1,
		);
		$_REQUEST                          = $_POST;
		$posts                             = $admin->get_export_posts();
		$post_ids                          = wp_list_pluck( $posts, 'ID' );
		$this->assertContains( $post1, $post_ids );
		$this->assertNotContains( $post2, $post_ids );
		$this->assertNotContains( $post3, $post_ids );

		wp_delete_user( $admin_id );
	}

	/** @test */
	public function download_csv_handles_invalid_json_content(): void {
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin_' . uniqid(),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $admin_id );

		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_content' => 'invalid-non-json-content',
				'post_date'    => '2025-01-03 10:00:00',
			)
		);

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
			\BeastFeedbacks_Admin::get_instance()->download_csv();
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'wp_die_csv_export', $e->getMessage() );
		} finally {
			remove_filter( 'wp_die_ajax_handler', $die_handler );
			remove_filter( 'wp_die_handler', $die_handler );
			wp_delete_user( $admin_id );
		}
		$csv_output = ob_get_clean();

		$lines = explode( "\n", trim( str_replace( "\r\n", "\n", $csv_output ) ) );
		$this->assertGreaterThanOrEqual( 2, count( $lines ) );
		$this->assertStringContainsString( 'source,date,type,ip_address,user_agent', $lines[0] );
	}
}
