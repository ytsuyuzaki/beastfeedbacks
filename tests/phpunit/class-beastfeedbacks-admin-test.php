<?php

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class BeastFeedbacks_Admin_Test extends TestCase {

	protected function set_up(): void {
		parent::set_up();

		// プラグイン定数が未定義ならダミー定義
		if ( ! defined( 'BEASTFEEDBACKS_DOMAIN' ) ) {
			define( 'BEASTFEEDBACKS_DOMAIN', 'beastfeedbacks' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_URL' ) ) {
			define( 'BEASTFEEDBACKS_URL', 'https://example.com/wp-content/plugins/beastfeedbacks/' );
		}
		if ( ! defined( 'BEASTFEEDBACKS_VERSION' ) ) {
			define( 'BEASTFEEDBACKS_VERSION', '0.1.0-test' );
		}
	}

	protected function tear_down(): void {
		unset( $GLOBALS['current_screen'], $GLOBALS['post'] );
		parent::tear_down();
	}

	/** @test */
	public function add_menu_page_registers_menu_and_post_type(): void {
		global $menu;
		$menu_before = $menu ?? array();

		\BeastFeedbacks_Admin::get_instance()->add_menu_page();

		// CPT registration state check
		$post_type_obj = get_post_type_object( 'beastfeedbacks' );
		$this->assertNotNull( $post_type_obj, 'beastfeedbacks post type should be registered' );
		$this->assertFalse( $post_type_obj->public );
		$this->assertTrue( $post_type_obj->show_ui );
		$this->assertFalse( $post_type_obj->show_in_menu );
		$this->assertFalse( $post_type_obj->show_in_admin_bar );
		$this->assertFalse( $post_type_obj->show_in_rest );
		$this->assertSame( 'Beastfeedbacks', $post_type_obj->labels->name );
		$this->assertSame( 'do_not_allow', $post_type_obj->cap->create_posts );

		// Global $menu state check
		$found_menu = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'edit.php?post_type=beastfeedbacks' === $item[2] ) {
					$found_menu = true;
					$this->assertSame( 'BeastFeedbacks', $item[0] );
					$this->assertSame( 'edit_pages', $item[1] );
					$this->assertSame( 'dashicons-feedback', $item[6] ?? '' );
					break;
				}
			}
		}
		$this->assertTrue( $found_menu, 'Admin menu item for beastfeedbacks should be added to $menu' );
	}

	/** @test */
	public function get_instance_returns_singleton(): void {
		$a = \BeastFeedbacks_Admin::get_instance();
		$b = \BeastFeedbacks_Admin::get_instance();

		$this->assertInstanceOf( \BeastFeedbacks_Admin::class, $a );
		$this->assertSame( $a, $b );
	}

	/** @test */
	public function manage_posts_columns_returns_expected_columns(): void {
		$cols = \BeastFeedbacks_Admin::get_instance()->manage_posts_columns();
		$this->assertSame(
			array(
				'cb',
				'beastfeedbacks_source',
				'beastfeedbacks_type',
				'beastfeedbacks_date',
				'beastfeedbacks_response',
			),
			array_keys( $cols )
		);
	}

	/** @test */
	public function admin_bulk_actions_unsets_edit_only_on_target_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		$in                        = array(
			'edit'  => '編集',
			'trash' => 'ゴミ箱',
		);
		$out                       = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertSame( $in, $out );

		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-beastfeedbacks' );
		$out2                      = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertArrayNotHasKey( 'edit', $out2 );
		$this->assertArrayHasKey( 'trash', $out2 );
	}

	/** @test */
	public function admin_view_tabs_unsets_publish_on_target_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		$views                     = array(
			'all'     => 'All',
			'publish' => 'Published',
		);
		$this->assertSame( $views, \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views ) );

		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-beastfeedbacks' );
		$out                       = \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views );
		$this->assertArrayNotHasKey( 'publish', $out );
		$this->assertArrayHasKey( 'all', $out );
	}

	/** @test */
	public function manage_post_row_actions_unsets_edit_when_beastfeedbacks_and_published(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'beastfeedbacks',
			'post_status' => 'publish',
		);
		$in              = array(
			'edit'                 => 'Edit',
			'inline hide-if-no-js' => 'Quick Edit',
			'view'                 => 'View',
		);
		$out             = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertArrayNotHasKey( 'edit', $out );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $out );
		$this->assertArrayHasKey( 'view', $out );
	}

	/** フェイク WP_Query 相当 */
	private function fakeQuery( array $vars = array() ) {
		return new class( $vars ) {
			public $query_vars = array();
			public function __construct( $vars ) {
				$this->query_vars = $vars; }
			public function get( $k ) {
				return $this->query_vars[ $k ] ?? null; }
			public function set( $k, $v ) {
				$this->query_vars[ $k ] = $v; }
		};
	}

	/** @test */
	public function type_filter_result_sets_meta_query_when_param_present(): void {
		$_GET['beastfeedbacks_type'] = 'survey'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q                           = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'survey', $mq[0]['value'] );
	}

	/** @test */
	public function type_filter_result_ignores_when_other_post_type(): void {
		$_GET['beastfeedbacks_type'] = 'survey';
		$q                           = $this->fakeQuery( array( 'post_type' => 'post' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_sets_post_parent_when_param_present(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q                                = $this->fakeQuery(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => '',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertSame( 55, $q->query_vars['post_parent'] );
	}

	/** @test */
	public function source_filter_result_ignores_when_fields_is_id_parent(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q                                = $this->fakeQuery(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => 'id=>parent',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function add_type_filter_has_no_output_on_other_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_type_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_source_filter_has_no_output_on_other_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_export_button_has_no_output_on_other_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_export_button();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function esc_csv_prefixes_when_dangerous_first_char(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$this->assertSame( "'=SUM(A1:A2)", $admin->esc_csv( '=SUM(A1:A2)' ) );
		$this->assertSame( "'+1+2", $admin->esc_csv( '+1+2' ) );
		$this->assertSame( "'-1", $admin->esc_csv( '-1' ) );
		$this->assertSame( "'@cmd", $admin->esc_csv( '@cmd' ) );

		$this->assertSame( 'safe', $admin->esc_csv( 'safe' ) );
		$this->assertSame( '  space', $admin->esc_csv( '  space' ) ); // 先頭がスペースならそのまま
	}

	/** @test */
	public function download_csv_fails_without_valid_nonce(): void {
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
		}
	}

	/** @test */
	public function download_csv_outputs_csv_with_feedback_data(): void {
		// Parent post for permalink source
		$parent_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
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
		$post_id1 = wp_insert_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
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
		$post_id2 = wp_insert_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
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
		}
		$csv_output = ob_get_clean();

		// Clean up created posts
		wp_delete_post( $post_id1, true );
		wp_delete_post( $post_id2, true );
		wp_delete_post( $parent_id, true );

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
		$this->assertStringContainsString( "'=SUM(1,2)", $lines[1] );

		// Check Row 2 (Vote feedback)
		$this->assertStringContainsString( 'vote', $lines[2] );
		$this->assertStringContainsString( '192.0.2.2', $lines[2] );
		$this->assertStringContainsString( 'AgentTwo', $lines[2] );
		$this->assertStringContainsString( 'medium', $lines[2] );
		$this->assertStringContainsString( 'Option A', $lines[2] );
	}

	/** @test */
	public function download_csv_handles_no_posts(): void {
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
		}
		$csv_output = ob_get_clean();

		$this->assertIsString( $csv_output );
	}

	/** @test */
	public function download_csv_handles_invalid_json_content(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
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
		}
		$csv_output = ob_get_clean();

		wp_delete_post( $post_id, true );

		$lines = explode( "\n", trim( str_replace( "\r\n", "\n", $csv_output ) ) );
		$this->assertGreaterThanOrEqual( 2, count( $lines ) );
		$this->assertStringContainsString( 'source,date,type,ip_address,user_agent', $lines[0] );
	}
}
