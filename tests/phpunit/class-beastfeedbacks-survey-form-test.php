<?php
/**
 * Tests for beastfeedbacks_block_survey_form_render_callback function.
 *
 * @package BeastFeedbacks
 */

use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class BeastFeedbacks_Survey_Form_Test
 */
class BeastFeedbacks_Survey_Form_Test extends TestCase {

	/**
	 * Created post IDs to delete in tear down.
	 *
	 * @var int[]
	 */
	private $created_ids = array();

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		if ( ! function_exists( 'beastfeedbacks_block_survey_form_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/survey-form/init.php';
		}
	}

	/**
	 * Tear down test environment and cleanup posts.
	 */
	public function tear_down(): void {
		if ( class_exists( 'WP_Block_Supports' ) ) {
			WP_Block_Supports::$block_to_render = null;
		}

		foreach ( array_reverse( $this->created_ids ) as $pid ) {
			if ( get_post( $pid ) ) {
				wp_delete_post( $pid, true );
			}
		}
		$this->created_ids = array();

		parent::tear_down();
	}

	/**
	 * Test rendering survey form with post context and valid output.
	 */
	public function test_survey_form_render_callback_returns_expected_html(): void {
		$post_id             = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Survey Form Post Context',
			)
		);
		$this->created_ids[] = $post_id;

		$GLOBALS['post'] = get_post( $post_id );
		setup_postdata( $GLOBALS['post'] );

		$attributes                         = array();
		WP_Block_Supports::$block_to_render = array(
			'blockName'    => 'beastfeedbacks/survey-form',
			'attrs'        => $attributes,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		$content = '<p>Survey Inner Content</p>';
		$html    = beastfeedbacks_block_survey_form_render_callback( $attributes, $content );

		wp_reset_postdata();

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

		$attributes                         = array();
		WP_Block_Supports::$block_to_render = array(
			'blockName'    => 'beastfeedbacks/survey-form',
			'attrs'        => $attributes,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		$html = beastfeedbacks_block_survey_form_render_callback( $attributes, '' );

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
