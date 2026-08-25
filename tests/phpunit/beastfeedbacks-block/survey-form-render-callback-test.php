<?php
/**
 * Tests for beastfeedbacks_block_survey_form_render_callback().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Block_Survey_Form_Render_Callback_Test extends BeastFeedbacks_TestCase {

	protected function set_up(): void {
		parent::set_up();

		if ( ! function_exists( 'beastfeedbacks_block_survey_form_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/survey-form/init.php';
		}
	}

	/**
	 * Test rendering survey form with post context and valid output.
	 */
	public function test_survey_form_render_callback_returns_expected_html(): void {
		$post_id = $this->create_post(
			array(
				'post_title' => 'Survey Form Post Context',
			)
		);

		$content = '<p>Survey Inner Content</p>';
		$html    = $this->render_block_with_context(
			'beastfeedbacks/survey-form',
			array(),
			'beastfeedbacks_block_survey_form_render_callback',
			$content,
			$post_id
		);

		$this->assertStringContainsString( '<div ', $html );
		$this->assertStringContainsString( '<form action="', $html );
		$this->assertStringContainsString( 'admin-ajax.php', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_survey_form"', $html );
		$this->assertStringContainsString( 'method="POST"', $html );
		$this->assertStringContainsString( 'name="action" value="register_beastfeedbacks_form"', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_type" value="survey"', $html );
		$this->assertStringContainsString( 'name="id" value="' . $post_id . '"', $html );
		$this->assertStringContainsString( '_wpnonce', $html );
		$this->assertStringContainsString( '<p>Survey Inner Content</p>', $html );
	}

	/**
	 * Test rendering survey form without post context (empty content & zero post ID).
	 */
	public function test_survey_form_render_callback_handles_empty_content_and_no_post(): void {
		$GLOBALS['post'] = null;

		$html = $this->render_block_with_context(
			'beastfeedbacks/survey-form',
			array(),
			'beastfeedbacks_block_survey_form_render_callback',
			''
		);

		$this->assertStringContainsString( '<div ', $html );
		$this->assertStringContainsString( '<form action="', $html );
		$this->assertStringContainsString( 'admin-ajax.php', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_survey_form"', $html );
		$this->assertStringContainsString( 'name="action" value="register_beastfeedbacks_form"', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_type" value="survey"', $html );
		$this->assertStringContainsString( 'name="id" value="0"', $html );
		$this->assertStringContainsString( '_wpnonce', $html );
	}
}
