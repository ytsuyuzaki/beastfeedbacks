<?php
/**
 * Tests for BeastFeedbacks_Admin_List_Filter.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_List_Filter_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		unset( $_REQUEST['_beastfeedbacks_nonce'] );
		unset( $_REQUEST['_wpnonce'] );
		unset( $_GET['_beastfeedbacks_nonce'] );
		unset( $_GET['_wpnonce'] );
		unset( $_GET['beastfeedbacks_type'] );
		unset( $_REQUEST['beastfeedbacks_type'] );
		unset( $_GET['beastfeedbacks_parent_id'] );
		unset( $_REQUEST['beastfeedbacks_parent_id'] );
		parent::tear_down();
	}

	/** @test */
	public function is_filter_nonce_verified_returns_true_with_valid_filter_nonce(): void {
		$_REQUEST['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$this->assertTrue( \BeastFeedbacks_Admin_List_Filter::get_instance()->is_filter_nonce_verified() );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_true_with_valid_csv_export_nonce(): void {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$this->assertTrue( \BeastFeedbacks_Admin_List_Filter::get_instance()->is_filter_nonce_verified() );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_false_with_invalid_nonce(): void {
		$_REQUEST['_beastfeedbacks_nonce'] = 'invalid_nonce';
		$this->assertFalse( \BeastFeedbacks_Admin_List_Filter::get_instance()->is_filter_nonce_verified() );
	}

	/** @test */
	public function is_filter_nonce_verified_returns_false_when_nonce_is_array(): void {
		$_REQUEST['_beastfeedbacks_nonce'] = array( 'invalid_nonce' );
		$this->assertFalse( \BeastFeedbacks_Admin_List_Filter::get_instance()->is_filter_nonce_verified() );
	}

	/** @test */
	public function add_type_filter_renders_options_on_target_screen(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		ob_start();
		\BeastFeedbacks_Admin_List_Filter::get_instance()->add_type_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="beastfeedbacks_type"', $html );
		foreach ( \BeastFeedbacks_Block::TYPES as $type ) {
			$this->assertStringContainsString( 'value="' . $type . '"', $html );
		}
	}

	/** @test */
	public function add_type_filter_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin_List_Filter::get_instance()->add_type_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function add_type_filter_selects_active_filter(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$_REQUEST['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_GET['beastfeedbacks_type']       = 'survey';

		ob_start();
		\BeastFeedbacks_Admin_List_Filter::get_instance()->add_type_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( '<option value="survey"', $html );
		$this->assertMatchesRegularExpression( '/<option value="survey"[^>]*selected[^>]*>/', $html );
	}

	/** @test */
	public function add_source_filter_renders_options_on_target_screen(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'Test Parent Page',
				'post_type'  => 'page',
			)
		);

		$this->create_post(
			array(
				'post_title'  => 'Feedback 1',
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		set_current_screen( 'edit-beastfeedbacks' );

		ob_start();
		\BeastFeedbacks_Admin_List_Filter::get_instance()->add_source_filter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="beastfeedbacks_parent_id"', $html );
		$this->assertStringContainsString( 'value="' . $parent_id . '"', $html );
	}

	/** @test */
	public function add_source_filter_has_no_output_on_other_screen(): void {
		set_current_screen( 'edit-post' );
		ob_start();
		\BeastFeedbacks_Admin_List_Filter::get_instance()->add_source_filter();
		$html = ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** @test */
	public function post_mutations_invalidate_source_filter_cache(): void {
		\BeastFeedbacks_Admin_List_Filter::get_instance()->init();

		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Page',
				'post_type'  => 'page',
			)
		);

		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		$this->assertNotFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		$feedback_id = $this->create_post(
			array(
				'post_title'  => 'Feedback Post',
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		wp_trash_post( $feedback_id );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		wp_untrash_post( $feedback_id );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		wp_delete_post( $feedback_id, true );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/** @test */
	public function type_filter_result_modifies_query(): void {
		$_REQUEST['_beastfeedbacks_nonce'] = wp_create_nonce( 'beastfeedbacks_filter' );
		$_REQUEST['beastfeedbacks_type']   = 'survey';

		$query = new WP_Query();
		$query->query_vars['post_type'] = 'beastfeedbacks';

		\BeastFeedbacks_Admin_List_Filter::get_instance()->type_filter_result( $query );

		$meta_query = $query->get( 'meta_query' );
		$this->assertNotEmpty( $meta_query );
		$this->assertSame( 'beastfeedbacks_type', $meta_query[0]['key'] );
		$this->assertSame( 'survey', $meta_query[0]['value'] );
	}

	/** @test */
	public function source_filter_result_modifies_query(): void {
		$_REQUEST['_beastfeedbacks_nonce']    = wp_create_nonce( 'beastfeedbacks_filter' );
		$_REQUEST['beastfeedbacks_parent_id'] = 123;

		$query = new WP_Query();
		$query->query_vars['post_type'] = 'beastfeedbacks';

		\BeastFeedbacks_Admin_List_Filter::get_instance()->source_filter_result( $query );

		$this->assertSame( 123, $query->query_vars['post_parent'] );
	}

	/** @test */
	public function prime_parent_post_caches_primes_cache(): void {
		set_current_screen( 'edit-beastfeedbacks' );

		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Page',
				'post_type'  => 'page',
			)
		);

		$feedback_id = $this->create_post(
			array(
				'post_title'  => 'Feedback Post',
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $parent_id,
			)
		);

		$posts = array( get_post( $feedback_id ) );

		$query = new WP_Query();
		$query->query_vars['post_type'] = 'beastfeedbacks';

		clean_post_cache( $parent_id );

		$this->assertFalse( wp_cache_get( $parent_id, 'posts' ) );

		\BeastFeedbacks_Admin_List_Filter::get_instance()->prime_parent_post_caches( $posts, $query );

		$this->assertNotFalse( wp_cache_get( $parent_id, 'posts' ) );
	}
}
