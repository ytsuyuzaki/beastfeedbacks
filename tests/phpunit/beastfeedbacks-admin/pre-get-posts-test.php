<?php
/**
 * Tests for BeastFeedbacks_Admin filtering on pre_get_posts (type_filter_result and source_filter_result).
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Pre_Get_Posts_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * フェイク WP_Query 相当
	 *
	 * @param array $vars Query vars.
	 * @return object
	 */
	private function fake_query( array $vars = array() ) {
		return new class( $vars ) {
			public $query_vars = array();
			public function __construct( $vars ) {
				$this->query_vars = $vars;
			}
			public function get( $k ) {
				return $this->query_vars[ $k ] ?? null;
			}
			public function set( $k, $v ) {
				$this->query_vars[ $k ] = $v;
			}
		};
	}

	/** @test */
	public function type_filter_result_sets_meta_query_when_param_present(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'survey';
		$_REQUEST                      = $_GET;
		$q                             = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'survey', $mq[0]['value'] );
	}

	/** @test */
	public function type_filter_result_ignores_when_nonce_invalid_or_missing(): void {
		$_GET['beastfeedbacks_type'] = 'survey';
		$q                           = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );

		$_GET['_beastfeedbacks_nonce'] = 'invalid_nonce';
		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function type_filter_result_ignores_when_type_invalid(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'invalid_type';
		$_REQUEST                      = $_GET;
		$q                             = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function type_filter_result_ignores_when_other_post_type(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'survey';
		$q                             = $this->fake_query( array( 'post_type' => 'post' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );
		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_sets_post_parent_when_param_present(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '55';
		$_REQUEST                         = $_GET;
		$q                                = $this->fake_query(
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
		$q                                = $this->fake_query(
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
	public function type_filter_result_supports_post_request_and_export_nonce(): void {
		$_POST['_wpnonce']           = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$_POST['beastfeedbacks_type'] = 'vote';
		$_REQUEST                   = array_merge( $_GET, $_POST );

		$q = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'vote', $mq[0]['value'] );
	}

	/** @test */
	public function source_filter_result_supports_post_request_and_export_nonce(): void {
		$_POST['_wpnonce']                 = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$_POST['beastfeedbacks_parent_id'] = '102';
		$_REQUEST                         = array_merge( $_GET, $_POST );

		$q = $this->fake_query(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => '',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertSame( 102, $q->query_vars['post_parent'] );
	}

	/** @test */
	public function source_filter_result_sanitizes_non_numeric_parent_id(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = 'invalid-55-abc';
		$_REQUEST                         = $_GET;
		$q                                = $this->fake_query(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => '',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_ignores_when_fields_is_id_parent(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '55';
		$q                                = $this->fake_query(
			array(
				'post_type' => 'beastfeedbacks',
				'fields'    => 'id=>parent',
			)
		);

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_ignores_when_other_post_type(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '55';
		$_REQUEST                         = $_GET;
		$q                                = $this->fake_query( array( 'post_type' => 'post' ) );

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_ignores_when_parent_id_is_zero_or_missing(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '0';
		$_REQUEST                         = $_GET;
		$q                                = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_with_real_wp_query_instance(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '77';
		$_REQUEST                         = $_GET;

		$query                          = new \WP_Query();
		$query->query_vars['post_type'] = 'beastfeedbacks';
		$query->query_vars['fields']    = 'ids';

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $query );

		$this->assertSame( 77, $query->get( 'post_parent' ) );
	}

	/** @test */
	public function source_filter_result_filters_get_posts_when_hooked_to_pre_get_posts(): void {
		$parent_a = $this->create_post();
		$parent_b = $this->create_post();

		$fb_a = $this->create_like_post( $parent_a );
		$fb_b = $this->create_like_post( $parent_b );

		add_action( 'pre_get_posts', array( \BeastFeedbacks_Admin::get_instance(), 'source_filter_result' ) );

		try {
			$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
			$_GET['beastfeedbacks_parent_id'] = (string) $parent_a;
			$_REQUEST                         = $_GET;

			$results = get_posts(
				array(
					'post_type'        => 'beastfeedbacks',
					'suppress_filters' => false,
					'fields'           => 'ids',
				)
			);

			$this->assertContains( $fb_a, $results );
			$this->assertNotContains( $fb_b, $results );
		} finally {
			remove_action( 'pre_get_posts', array( \BeastFeedbacks_Admin::get_instance(), 'source_filter_result' ) );
		}
	}
}
