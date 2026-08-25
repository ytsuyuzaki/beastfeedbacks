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

	/** @var int[] 作成した投稿のIDを記録して後始末 */
	private $created_ids = array();

	protected function tear_down(): void {
		foreach ( array_reverse( $this->created_ids ) as $pid ) {
			if ( get_post( $pid ) ) {
				wp_delete_post( $pid, true );
			}
		}
		$this->created_ids = array();

		wp_dequeue_script( BEASTFEEDBACKS_DOMAIN );
		wp_deregister_script( BEASTFEEDBACKS_DOMAIN );
		wp_dequeue_style( BEASTFEEDBACKS_DOMAIN );
		wp_deregister_style( BEASTFEEDBACKS_DOMAIN );
		remove_all_actions( 'admin_enqueue_scripts' );
		remove_all_actions( 'pre_get_posts' );
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'], $GLOBALS['post'] );
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * 投稿を作成し、ID を回収・記録するユーティリティ
	 *
	 * @param array $args wp_insert_post() の引数.
	 * @return int 作成した投稿ID
	 */
	private function create_post( array $args ): int {
		$pid                 = wp_insert_post( $args );
		$this->created_ids[] = $pid;
		return $pid;
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
	public function init_registers_admin_enqueue_scripts_action(): void {
		$instance = \BeastFeedbacks_Admin::get_instance();
		$instance->init();

		$this->assertSame(
			10,
			has_action( 'admin_enqueue_scripts', array( $instance, 'admin_enqueue_scripts' ) )
		);
	}

	/** @test */
	public function admin_enqueue_scripts_does_not_enqueue_assets_when_not_target_screen(): void {
		set_current_screen( 'edit-post' );

		\BeastFeedbacks_Admin::get_instance()->admin_enqueue_scripts();

		$this->assertFalse( wp_script_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );
		$this->assertFalse( wp_style_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );
	}

	/** @test */
	public function admin_enqueue_scripts_enqueues_js_and_css_on_target_screen(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		\BeastFeedbacks_Admin::get_instance()->admin_enqueue_scripts();

		$this->assertTrue( wp_script_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );
		$this->assertTrue( wp_style_is( BEASTFEEDBACKS_DOMAIN, 'enqueued' ) );

		global $wp_scripts, $wp_styles;

		$script = $wp_scripts->registered[ BEASTFEEDBACKS_DOMAIN ];
		$this->assertSame( BEASTFEEDBACKS_URL . 'public/js/beastfeedbacks-admin.js', $script->src );
		$this->assertSame( BEASTFEEDBACKS_VERSION, $script->ver );
		$this->assertSame( array(), $script->deps );

		$style = $wp_styles->registered[ BEASTFEEDBACKS_DOMAIN ];
		$this->assertSame( BEASTFEEDBACKS_URL . 'public/css/beastfeedbacks-admin.css', $style->src );
		$this->assertSame( BEASTFEEDBACKS_VERSION, $style->ver );
		$this->assertSame( array(), $style->deps );
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
		set_current_screen( 'edit-post' );
		$in  = array(
			'edit'  => '編集',
			'trash' => 'ゴミ箱',
		);
		$out = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertSame( $in, $out );

		set_current_screen( 'edit-beastfeedbacks' );
		$out2 = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertArrayNotHasKey( 'edit', $out2 );
		$this->assertArrayHasKey( 'trash', $out2 );
	}

	/** @test */
	public function admin_view_tabs_unsets_publish_on_target_screen(): void {
		set_current_screen( 'edit-post' );
		$views = array(
			'all'     => 'All',
			'publish' => 'Published',
		);
		$this->assertSame( $views, \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views ) );

		set_current_screen( 'edit-beastfeedbacks' );
		$out   = \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views );
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
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'survey';
		$q                             = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'survey', $mq[0]['value'] );
	}

	/** @test */
	public function type_filter_result_ignores_when_nonce_invalid_or_missing(): void {
		$_GET['beastfeedbacks_type'] = 'survey';
		$q                           = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );

		$_GET['_beastfeedbacks_nonce'] = 'invalid_nonce';
		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function type_filter_result_ignores_when_other_post_type(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'survey';
		$q                             = $this->fakeQuery( array( 'post_type' => 'post' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_sets_post_parent_when_param_present(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
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
	public function source_filter_result_ignores_when_nonce_invalid_or_missing(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q                                = $this->fakeQuery(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => '',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );
		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );

		$_GET['_beastfeedbacks_nonce'] = 'invalid_nonce';
		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );
		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_ignores_when_fields_is_id_parent(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
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
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_type_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_type_filter_renders_nonce_field_and_select_on_target_screen(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_type_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="_beastfeedbacks_nonce"', $html );
		$this->assertStringContainsString( '<select name="beastfeedbacks_type">', $html );
	}

	/** @test */
	public function add_source_filter_renders_options_on_target_screen(): void {
		$parent_id = wp_insert_post(
			array(
				'post_title'  => 'Test Parent Page',
				'post_status' => 'publish',
				'post_type'   => 'page',
			)
		);

		wp_insert_post(
			array(
				'post_title'  => 'Feedback 1',
				'post_status' => 'publish',
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		set_current_screen( 'edit-beastfeedbacks' );

		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="beastfeedbacks_parent_id"', $html );
		$this->assertStringContainsString( 'value="' . $parent_id . '"', $html );
	}

	/** @test */
	public function add_source_filter_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_export_button_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
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

	/**
	 * Test untrash_beastfeedbacks_status_handler returns previous status when post type is beastfeedbacks and previous status is publish.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_returns_previous_status_when_beastfeedbacks_and_previous_status_is_publish(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Test BeastFeedback',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'publish' );
		$this->assertSame( 'publish', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test untrash_beastfeedbacks_status_handler returns publish when post type is beastfeedbacks and previous status is not publish.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_returns_publish_when_beastfeedbacks_and_previous_status_is_not_publish(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'trash',
				'post_title'  => 'Test BeastFeedback',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'draft' );
		$this->assertSame( 'publish', $result );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test untrash_beastfeedbacks_status_handler returns current status when post type is not beastfeedbacks.
	 *
	 * @test
	 */
	public function untrash_beastfeedbacks_status_handler_returns_current_status_when_not_beastfeedbacks(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'trash',
				'post_title'  => 'Test Standard Post',
			)
		);

		$result = \BeastFeedbacks_Admin::get_instance()->untrash_beastfeedbacks_status_handler( 'draft', $post_id, 'draft' );
		$this->assertSame( 'draft', $result );

		wp_delete_post( $post_id, true );
	}

	/** @test */
	public function manage_posts_custom_column_returns_early_for_unsupported_column(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'unsupported_column', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function manage_posts_custom_column_handles_unknown_column(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();
		ob_start();
		$admin->manage_posts_custom_column( 'unknown_column', 123 );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_date_for_beastfeedbacks_date(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_date'   => '2025-01-15 10:20:30',
			)
		);

		$post            = get_post( $post_id );
		$GLOBALS['post'] = $post;

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_date', $post_id );
		$output = ob_get_clean();

		$expected_date = date_i18n( 'Y/m/d', get_the_time( 'U' ) );
		$this->assertSame( $expected_date, $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_type_for_beastfeedbacks_type(): void {
		$admin   = \BeastFeedbacks_Admin::get_instance();
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
			)
		);
		add_post_meta( $post_id, 'beastfeedbacks_type', 'survey' );

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_type', $post_id );
		$output = ob_get_clean();

		$this->assertSame( 'survey', $output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_source_link_for_beastfeedbacks_source(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Parent post
		$parent_id = $this->create_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'parent post',
			)
		);

		// Child feedback post
		$post_id = $this->create_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', $post_id );
		$output = ob_get_clean();

		$form_url   = get_permalink( $parent_id );
		$parsed_url = wp_parse_url( $form_url );
		$expected   = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $form_url ),
			esc_html( $parsed_url['path'] )
		);

		$this->assertSame( $expected, $output );

		// Invalid / non-existent post ID where $post is null
		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_source', 999999 );
		$no_parent_output = ob_get_clean();

		$this->assertSame( '', $no_parent_output );
	}

	/** @test */
	public function manage_posts_custom_column_outputs_response_data_for_vote_and_survey(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		// Case 1: Non-array or invalid JSON content
		$invalid_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => 'invalid json content',
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $invalid_post_id );
		$invalid_output = ob_get_clean();
		$this->assertSame( '', $invalid_output );

		// Case 2: Vote response
		$vote_content = array(
			'type'        => 'vote',
			'post_params' => array(
				'selected' => 'Option 1',
			),
			'ip_address'  => '192.168.1.1',
			'user_agent'  => 'Test User Agent',
		);
		$vote_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $vote_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $vote_post_id );
		$vote_output = ob_get_clean();

		$this->assertStringContainsString( 'Option 1', $vote_output );
		$this->assertStringContainsString( 'IP_Address', $vote_output );
		$this->assertStringContainsString( '192.168.1.1', $vote_output );
		$this->assertStringContainsString( 'UserAgent', $vote_output );
		$this->assertStringContainsString( 'Test User Agent', $vote_output );

		// Case 3: Survey response with scalar and array values
		$survey_content = array(
			'type'        => 'survey',
			'post_params' => array(
				'Question 1' => 'Answer 1',
				'Question 2' => array( 'Choice A', 'Choice B' ),
			),
			'ip_address'  => '10.0.0.1',
		);
		$survey_post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $survey_content ),
			)
		);

		ob_start();
		$admin->manage_posts_custom_column( 'beastfeedbacks_response', $survey_post_id );
		$survey_output = ob_get_clean();

		$this->assertStringContainsString( 'Question 1', $survey_output );
		$this->assertStringContainsString( 'Answer 1', $survey_output );
		$this->assertStringContainsString( 'Question 2', $survey_output );
		$this->assertStringContainsString( 'Choice A', $survey_output );
		$this->assertStringContainsString( 'Choice B', $survey_output );
		$this->assertStringContainsString( 'Choice A<br />', $survey_output );
		$this->assertStringContainsString( '10.0.0.1', $survey_output );
		$this->assertStringNotContainsString( 'UserAgent', $survey_output );
	}

	/** @test */
	public function get_csv_data_formats_posts_correctly(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post1 = (object) array(
			'ID'           => 10,
			'post_parent'  => 0,
			'post_date'    => '2025-01-01 10:00:00',
			'post_content' => json_encode(
				array(
					'type'        => 'survey',
					'ip_address'  => '127.0.0.1',
					'user_agent'  => 'TestAgent',
					'post_params' => array(
						'q1' => 'Answer 1',
						'q2' => array( 'Opt A', 'Opt B' ),
					),
				)
			),
		);

		$csv_data = $admin->get_csv_data( array( $post1 ) );

		$this->assertArrayHasKey( 'source', $csv_data );
		$this->assertArrayHasKey( 'date', $csv_data );
		$this->assertArrayHasKey( 'type', $csv_data );
		$this->assertArrayHasKey( 'ip_address', $csv_data );
		$this->assertArrayHasKey( 'user_agent', $csv_data );
		$this->assertArrayHasKey( 'q1', $csv_data );
		$this->assertArrayHasKey( 'q2', $csv_data );

		$this->assertSame( '', $csv_data['source'][10] );
		$this->assertSame( '2025-01-01 10:00:00', $csv_data['date'][10] );
		$this->assertSame( 'survey', $csv_data['type'][10] );
		$this->assertSame( '127.0.0.1', $csv_data['ip_address'][10] );
		$this->assertSame( 'TestAgent', $csv_data['user_agent'][10] );
		$this->assertSame( 'Answer 1', $csv_data['q1'][10] );
		$this->assertSame( 'Opt A,Opt B', $csv_data['q2'][10] );
	}
}
