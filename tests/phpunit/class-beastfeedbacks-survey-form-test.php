<?php
/**
 * Tests for beastfeedbacks_block_survey_form_render_callback function.
 *
 * @package BeastFeedbacks
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

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
		if ( function_exists( 'wp_delete_post' ) ) {
			foreach ( array_reverse( $this->created_ids ) as $pid ) {
				if ( get_post( $pid ) ) {
					wp_delete_post( $pid, true );
				}
			}
		}
		$this->created_ids = array();

		parent::tear_down();
	}

	/**
	 * Test rendering survey form with post context and valid output.
	 */
	public function test_survey_form_render_callback_returns_expected_html(): void {
		$post_id = 123;

		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
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
		} else {
			Functions\stubs(
				array(
					'get_block_wrapper_attributes' => 'class="wp-block-beastfeedbacks-survey-form"',
					'get_the_ID'                   => $post_id,
					'wp_nonce_field'               => '<input type="hidden" id="_wpnonce" name="_wpnonce" value="test_nonce" />',
					'admin_url'                    => 'https://example.com/wp-admin/admin-ajax.php',
					'esc_url'                      => function ( $url ) {
						return $url;
					},
					'absint'                       => function ( $val ) {
						return (int) $val;
					},
					'esc_attr'                     => function ( $val ) {
						return (string) $val;
					},
				)
			);
		}

		$attributes = array();
		$content    = '<p>Survey Inner Content</p>';

		$html = beastfeedbacks_block_survey_form_render_callback( $attributes, $content );

		if ( function_exists( 'wp_reset_postdata' ) && ! empty( $this->created_ids ) ) {
			wp_reset_postdata();
		}

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
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			$GLOBALS['post'] = null;
		} else {
			Functions\stubs(
				array(
					'get_block_wrapper_attributes' => 'class="wp-block-beastfeedbacks-survey-form"',
					'get_the_ID'                   => false,
					'wp_nonce_field'               => '<input type="hidden" id="_wpnonce" name="_wpnonce" value="test_nonce" />',
					'admin_url'                    => 'https://example.com/wp-admin/admin-ajax.php',
					'esc_url'                      => function ( $url ) {
						return $url;
					},
					'absint'                       => function ( $val ) {
						return (int) $val;
					},
					'esc_attr'                     => function ( $val ) {
						return (string) $val;
					},
				)
			);
		}

		$html = beastfeedbacks_block_survey_form_render_callback( array(), '' );

		$this->assertStringContainsString( '<form action="', $html );
		$this->assertStringContainsString( 'admin-ajax.php', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_survey_form"', $html );
		$this->assertStringContainsString( 'name="action" value="register_beastfeedbacks_form"', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_type" value="survey"', $html );
		$this->assertStringContainsString( 'name="id" value="0"', $html );
		$this->assertStringContainsString( '_wpnonce', $html );
	}
}
