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
	public function type_filter_result_preserves_existing_meta_query(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'survey';
		$_REQUEST                      = $_GET;

		$existing_mq = array(
			array(
				'key'     => 'some_custom_key',
				'value'   => 'custom_value',
				'compare' => '=',
			),
		);

		$q = $this->fake_query(
			array(
				'post_type'  => 'beastfeedbacks',
				'meta_query' => $existing_mq,
			)
		);

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayHasKey( 'meta_query', $q->query_vars );
		$mq = $q->query_vars['meta_query'];
		$this->assertCount( 2, $mq );
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'survey', $mq[0]['value'] );
		$this->assertSame( $existing_mq, $mq[1] );
	}

	/** @test */
	public function type_filter_result_supports_all_valid_types(): void {
		foreach ( \BeastFeedbacks_Block::TYPES as $type ) {
			$_GET                          = array();
			$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
			$_GET['beastfeedbacks_type']   = $type;
			$_REQUEST                      = $_GET;

			$q = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

			\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

			$this->assertArrayHasKey( 'meta_query', $q->query_vars );
			$mq = $q->query_vars['meta_query'];
			$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
			$this->assertSame( $type, $mq[0]['value'] );
		}
	}

	/** @test */
	public function type_filter_result_ignores_when_type_empty(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = '';
		$_REQUEST                      = $_GET;

		$q = $this->fake_query( array( 'post_type' => 'beastfeedbacks' ) );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$this->assertArrayNotHasKey( 'meta_query', $q->query_vars );
	}

	/** @test */
	public function type_filter_result_works_with_real_wp_query(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'like';
		$_REQUEST                      = $_GET;

		$q = new \WP_Query();
		$q->set( 'post_type', 'beastfeedbacks' );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$mq = $q->get( 'meta_query' );
		$this->assertIsArray( $mq );
		$this->assertSame( 'beastfeedbacks_type', $mq[0]['key'] );
		$this->assertSame( 'like', $mq[0]['value'] );
	}

	/** @test */
	public function type_filter_result_filters_actual_posts_in_wp_query(): void {
		$like_id   = $this->create_like_post();
		$vote_id   = $this->create_vote_post();
		$survey_id = $this->create_survey_post();

		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = 'vote';
		$_REQUEST                      = $_GET;

		$q = new \WP_Query();
		$q->set( 'post_type', 'beastfeedbacks' );
		$q->set( 'posts_per_page', -1 );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$posts     = $q->get_posts();
		$found_ids = wp_list_pluck( $posts, 'ID' );

		$this->assertContains( $vote_id, $found_ids );
		$this->assertNotContains( $like_id, $found_ids );
		$this->assertNotContains( $survey_id, $found_ids );
	}

	/** @test */
	public function type_filter_result_ignores_when_type_is_array(): void {
		$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']   = array( 'vote' );
		$_REQUEST                      = $_GET;

		$q = new \WP_Query();
		$q->set( 'post_type', 'beastfeedbacks' );
		$q->set( 'posts_per_page', -1 );

		\BeastFeedbacks_Admin::get_instance()->type_filter_result( $q );

		$mq = $q->get( 'meta_query' );
		$this->assertEmpty( $mq );
	}

	/** @test */
	public function type_filter_result_filters_get_posts_when_hooked_to_pre_get_posts(): void {
		$like_id   = $this->create_like_post();
		$vote_id   = $this->create_vote_post();
		$survey_id = $this->create_survey_post();

		add_action( 'pre_get_posts', array( \BeastFeedbacks_Admin::get_instance(), 'type_filter_result' ) );

		try {
			$_GET['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
			$_GET['beastfeedbacks_type']   = 'survey';
			$_REQUEST                      = $_GET;

			$results = get_posts(
				array(
					'post_type'        => 'beastfeedbacks',
					'suppress_filters' => false,
					'fields'           => 'ids',
				)
			);

			$this->assertContains( $survey_id, $results );
			$this->assertNotContains( $like_id, $results );
			$this->assertNotContains( $vote_id, $results );
		} finally {
			remove_action( 'pre_get_posts', array( \BeastFeedbacks_Admin::get_instance(), 'type_filter_result' ) );
		}
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
	public function source_filter_result_mutates_query_state_based_on_request_values(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '88';
		$_REQUEST                         = $_GET;

		$q = new \WP_Query();
		$q->set( 'post_type', 'beastfeedbacks' );

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertSame( 88, $q->query_vars['post_parent'] );
	}

	/** @test */
	public function source_filter_result_ignores_when_post_type_not_set(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = '55';
		$_REQUEST                         = $_GET;
		$q                                = $this->fake_query( array() ); // post_type is omitted.

		\BeastFeedbacks_Admin::get_instance()->source_filter_result( $q );

		$this->assertArrayNotHasKey( 'post_parent', $q->query_vars );
	}

	/** @test */
	public function source_filter_result_ignores_array_parent_id(): void {
		$_GET['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_parent_id'] = array( '55' );
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
