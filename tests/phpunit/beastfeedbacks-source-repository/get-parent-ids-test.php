<?php
/**
 * Tests for BeastFeedbacks_Source_Repository::get_parent_ids().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Source_Repository_Get_Parent_Ids_Test extends BeastFeedbacks_TestCase {

	protected function tear_down(): void {
		wp_cache_delete( 'source_filter_parent_ids', 'beastfeedbacks' );
		parent::tear_down();
	}

	/** @test */
	public function get_parent_ids_returns_array_of_parent_ids(): void {
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

		$result = \BeastFeedbacks_Source_Repository::get_instance()->get_parent_ids();

		$this->assertIsArray( $result );
		$this->assertContains( (int) $parent_id, $result );
	}

	/** @test */
	public function get_parent_ids_uses_and_sets_cache(): void {
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

		$this->assertFalse( wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' ) );

		$result = \BeastFeedbacks_Source_Repository::get_instance()->get_parent_ids();
		$this->assertIsArray( $result );

		$cached = wp_cache_get( 'source_filter_parent_ids', 'beastfeedbacks' );
		$this->assertIsArray( $cached );
		$this->assertContains( (string) $parent_id, $cached );
	}
}
