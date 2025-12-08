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
		$in  = array( 'edit' => '編集', 'trash' => 'ゴミ箱' );
		$out = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertSame( $in, $out );

		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-beastfeedbacks' );
		$out2 = \BeastFeedbacks_Admin::get_instance()->admin_bulk_actions( $in );
		$this->assertArrayNotHasKey( 'edit', $out2 );
		$this->assertArrayHasKey( 'trash', $out2 );
	}

	/** @test */
	public function admin_view_tabs_unsets_publish_on_target_screen(): void {
		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-post' );
		$views = array( 'all' => 'All', 'publish' => 'Published' );
		$this->assertSame( $views, \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views ) );

		$GLOBALS['current_screen'] = (object) array( 'id' => 'edit-beastfeedbacks' );
		$out = \BeastFeedbacks_Admin::get_instance()->admin_view_tabs( $views );
		$this->assertArrayNotHasKey( 'publish', $out );
		$this->assertArrayHasKey( 'all', $out );
	}

	/** @test */
	public function manage_post_row_actions_unsets_edit_when_beastfeedbacks_and_published(): void {
		$GLOBALS['post'] = (object) array(
			'post_type'   => 'beastfeedbacks',
			'post_status' => 'publish',
		);
		$in  = array( 'edit' => 'Edit', 'inline hide-if-no-js' => 'Quick Edit', 'view' => 'View' );
		$out = \BeastFeedbacks_Admin::get_instance()->manage_post_row_actions( $in );

		$this->assertArrayNotHasKey( 'edit', $out );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $out );
		$this->assertArrayHasKey( 'view', $out );
	}

	/** フェイク WP_Query 相当 */
	private function fakeQuery( array $vars = array() ) {
		return new class( $vars ) {
			public $query_vars = array();
			public function __construct( $vars ) { $this->query_vars = $vars; }
			public function get( $k ) { return $this->query_vars[ $k ] ?? null; }
			public function set( $k, $v ) { $this->query_vars[ $k ] = $v; }
		};
	}

	/** @test */
	public function type_filter_result_sets_meta_query_when_param_present(): void {
		$_GET['beastfeedbacks_type'] = 'survey'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'survey', $mq[0]['value'] );
	}

	/** @test */
	public function type_filter_result_ignores_when_other_post_type(): void {
		$_GET['beastfeedbacks_type'] = 'survey';
		$q = $this->fakeQuery( array( 'post_type' => 'post' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_sets_post_parent_when_param_present(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks', 'fields' => '' ) );

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertSame( 55, $q->query_vars['post_parent'] );
	}

	/** @test */
	public function source_filter_result_ignores_when_fields_is_id_parent(): void {
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q = $this->fakeQuery( array( 'post_type' => 'beastfeedbacks', 'fields' => 'id=>parent' ) );

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
		$this->assertSame( "'+1+2",        $admin->esc_csv( '+1+2' ) );
		$this->assertSame( "'-1",          $admin->esc_csv( '-1' ) );
		$this->assertSame( "'@cmd",        $admin->esc_csv( '@cmd' ) );

		$this->assertSame( 'safe',         $admin->esc_csv( 'safe' ) );
		$this->assertSame( '  space',      $admin->esc_csv( '  space' ) ); // 先頭がスペースならそのまま
	}
}
