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
	public function post_mutations_invalidate_source_filter_cache(): void {
		\BeastFeedbacks_Admin::get_instance()->init();

		$parent_id = $this->create_post(
			array(
				'post_title' => 'Parent Page',
				'post_type'  => 'page',
			)
		);

		// 1. Create a feedback post
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

		// 2. Trashing feedback post
		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		wp_trash_post( $feedback_id );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		// 3. Untrashing feedback post
		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		wp_untrash_post( $feedback_id );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		// 4. Deleting feedback post
		wp_cache_set( 'source_filter_parent_ids', array( $parent_id ), 'beastfeedbacks' );
		wp_delete_post( $feedback_id, true );
		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}

	/** @test */
	public function clean_post_cache_does_not_evict_source_filter_cache(): void {
		\BeastFeedbacks_Admin::get_instance()->init();

		$feedback_id = $this->create_post(
			array(
				'post_title' => 'Feedback Post',
				'post_type'  => 'beastfeedbacks',
			)
		);

		wp_cache_set( 'source_filter_parent_ids', array( 10, 20 ), 'beastfeedbacks' );

		// Simulating clean_post_cache() as invoked during stream_csv() memory cleanup.
		clean_post_cache( $feedback_id );

		$this->assertSame( array( 10, 20 ), wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );
	}
}
