<?php
/**
 * Tests for BeastFeedbacks_Admin::add_source_filter().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Add_Source_Filter_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		set_current_screen( 'front' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
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
	public function add_source_filter_populates_and_uses_object_cache(): void {
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
		wp_cache_delete( 'source_filter_parent_ids', 'beastfeedbacks' );

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		ob_start();
		\BeastFeedbacks_Admin::get_instance()->add_source_filter();
		ob_end_clean();

		$cached = wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' );
		$this->assertIsArray( $cached );
		$this->assertContains( (string) $parent_id, $cached );
	}

	/** @test */
	public function clear_source_filter_cache_invalidates_cache_for_beastfeedbacks_posts(): void {
		\BeastFeedbacks_Admin::get_instance()->init();

		wp_cache_set( 'source_filter_parent_ids', array( 1, 2, 3 ), 'beastfeedbacks' );
		$this->assertNotFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		$page_id = $this->create_post(
			array(
				'post_title' => 'Sample Page',
				'post_type'  => 'page',
			)
		);

		// Non-beastfeedbacks post cache clean should NOT invalidate source filter cache.
		clean_post_cache( $page_id );
		$this->assertNotFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		$feedback_id = $this->create_post(
			array(
				'post_title'  => 'Feedback 2',
				'post_type'   => 'beastfeedbacks',
				'post_parent' => $page_id,
			)
		);

		// Repopulate cache to verify clean_post_cache invalidates it.
		wp_cache_set( 'source_filter_parent_ids', array( 1, 2, 3 ), 'beastfeedbacks' );
		$this->assertNotFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		// Beastfeedbacks post cache clean SHOULD invalidate source filter cache.
		clean_post_cache( $feedback_id );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}
}
