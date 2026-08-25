<?php
/**
 * Tests for beastfeedbacks_block_vote_render_callback().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Vote_Render_Callback_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test that vote render callback renders form with attributes and content.
	 *
	 * @test
	 */
	public function vote_render_callback_renders_form_with_attributes_and_content(): void {
		if ( ! function_exists( 'beastfeedbacks_block_vote_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/vote/init.php';
		}

		$post_id = $this->create_post(
			array(
				'post_title' => 'Vote Test Post',
			)
		);

		$content = '<div class="vote-options"><input type="radio" name="option" value="1" /> Option 1</div>';

		$html = $this->render_block_with_context(
			'beastfeedbacks/vote',
			array(),
			'beastfeedbacks_block_vote_render_callback',
			$content,
			$post_id
		);

		$this->assertStringContainsString( 'name="beastfeedbacks_vote_form"', $html );
		$this->assertStringContainsString( 'admin-ajax.php', $html );
		$this->assertStringContainsString( '_wpnonce', $html );
		$this->assertStringContainsString( '<input type="hidden" name="action" value="register_beastfeedbacks_form" />', $html );
		$this->assertStringContainsString( '<input type="hidden" name="beastfeedbacks_type" value="vote" />', $html );
		$this->assertStringContainsString( '<input type="hidden" name="id" value="' . $post_id . '" />', $html );
		$this->assertStringContainsString( $content, $html );
	}

	/**
	 * Test that vote render callback handles empty content and zero post ID.
	 *
	 * @test
	 */
	public function vote_render_callback_handles_empty_content_and_zero_post_id(): void {
		if ( ! function_exists( 'beastfeedbacks_block_vote_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/vote/init.php';
		}

		$html = $this->render_block_with_context(
			'beastfeedbacks/vote',
			array(),
			'beastfeedbacks_block_vote_render_callback',
			''
		);

		$this->assertStringContainsString( 'name="beastfeedbacks_vote_form"', $html );
		$this->assertStringContainsString( '<input type="hidden" name="beastfeedbacks_type" value="vote" />', $html );
		$this->assertStringContainsString( '<input type="hidden" name="id" value="0" />', $html );
		$this->assertStringContainsString( '<form action="', $html );
		$this->assertStringContainsString( '</form>', $html );
	}
}
