<?php
/**
 * Tests for BeastFeedbacks_Admin_List::manage_posts_columns().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_List_Manage_Posts_Columns_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function manage_posts_columns_returns_expected_columns(): void {
		$cols = \BeastFeedbacks_Admin_List::get_instance()->manage_posts_columns();
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
}
