<?php
/**
 * Tests for BeastFeedbacks_Block::block_categories_all().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Block_Categories_All_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function block_categories_all_adds_category_when_context_has_post(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		$cats_in       = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
				'icon'  => null,
			),
		);
		$context       = new stdClass();
		$context->post = (object) array( 'ID' => 123 ); // ポストあり

		$cats_out = $instance->block_categories_all( $cats_in, $context );

		$this->assertCount( 2, $cats_out );
		$last = end( $cats_out );
		$this->assertSame( 'beastfeedbacks', $last['slug'] );
		$this->assertSame( 'BeastFeedbacks', $last['title'] );
		$this->assertArrayHasKey( 'icon', $last );
		$this->assertNull( $last['icon'] );
	}

	/** @test */
	public function block_categories_all_does_not_add_when_no_post_in_context(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		$cats_in = array(
			array(
				'slug'  => 'text',
				'title' => 'Text',
				'icon'  => null,
			),
		);
		$context = new stdClass(); // post 無し

		$cats_out = $instance->block_categories_all( $cats_in, $context );

		$this->assertSame( $cats_in, $cats_out, 'Context without post must not add category' );
	}
}
