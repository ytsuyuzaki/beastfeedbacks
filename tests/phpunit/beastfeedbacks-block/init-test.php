<?php
/**
 * Tests for BeastFeedbacks_Block::init().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Init_Test extends BeastFeedbacks_TestCase {

	protected function set_up(): void {
		parent::set_up();
		remove_all_filters( 'block_categories_all' );
		remove_all_actions( 'init' );
	}

	protected function tear_down(): void {
		remove_all_filters( 'block_categories_all' );
		remove_all_actions( 'init' );
		parent::tear_down();
	}

	/** @test */
	public function init_registers_hooks(): void {
		$instance = \BeastFeedbacks_Block::get_instance();

		// 実行
		$instance->init();

		// フィルタが instance メソッドで優先度10で登録されているか
		$this->assertSame(
			10,
			has_filter( 'block_categories_all', array( $instance, 'block_categories_all' ) )
		);

		// アクション 'init' に init_blocks が登録されているか
		$this->assertNotFalse(
			has_action( 'init', array( $instance, 'init_blocks' ) )
		);
	}
}
