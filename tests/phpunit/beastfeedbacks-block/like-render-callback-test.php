<?php
/**
 * Tests for beastfeedbacks_block_like_render_callback().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Like_Render_Callback_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function beastfeedbacks_block_like_render_callback_renders_like_block_html(): void {
		require_once BEASTFEEDBACKS_DIR . 'src/like/init.php';

		$post_id = $this->create_post(
			array(
				'post_title' => 'Test Post for Like Block',
			)
		);

		$attributes = array();
		$content    = '<button type="submit" class="like-button">Like</button>';

		$html = $this->render_block_with_context(
			'beastfeedbacks/like',
			$attributes,
			'beastfeedbacks_block_like_render_callback',
			$content,
			$post_id
		);

		$this->assertStringContainsString( 'name="beastfeedbacks_like_form"', $html );
		$this->assertStringContainsString( 'value="register_beastfeedbacks_form"', $html );
		$this->assertStringContainsString( 'value="like"', $html );
		$this->assertStringContainsString( 'value="' . $post_id . '"', $html );
		$this->assertStringContainsString( '<p class="like-count">0</p>', $html );
		$this->assertStringContainsString( $content, $html );
	}

	/** @test */
	public function beastfeedbacks_block_like_render_callback_displays_correct_like_count(): void {
		require_once BEASTFEEDBACKS_DIR . 'src/like/init.php';

		$post_id = $this->create_post(
			array(
				'post_title' => 'Test Post with Likes',
			)
		);

		$this->create_like_post( $post_id );
		$this->create_like_post( $post_id );

		$html = $this->render_block_with_context(
			'beastfeedbacks/like',
			array(),
			'beastfeedbacks_block_like_render_callback',
			'',
			$post_id
		);

		$this->assertStringContainsString( '<p class="like-count">2</p>', $html );
	}
}
