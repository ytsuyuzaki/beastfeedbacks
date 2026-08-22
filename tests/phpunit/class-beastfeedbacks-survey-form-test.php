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
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		if ( ! function_exists( 'beastfeedbacks_block_survey_form_render_callback' ) ) {
			require_once dirname( __DIR__, 2 ) . '/src/survey-form/init.php';
		}
	}

	/**
	 * Test rendering survey form with content and valid post ID.
	 */
	public function test_survey_form_render_callback_returns_expected_html(): void {
		Functions\expect( 'get_block_wrapper_attributes' )
			->once()
			->andReturn( 'class="wp-block-beastfeedbacks-survey-form"' );

		Functions\expect( 'wp_nonce_field' )
			->once()
			->with( 'register_beastfeedbacks_form', '_wpnonce', true, false )
			->andReturn( '<input type="hidden" id="_wpnonce" name="_wpnonce" value="test_nonce" />' );

		Functions\expect( 'admin_url' )
			->once()
			->with( 'admin-ajax.php' )
			->andReturn( 'https://example.com/wp-admin/admin-ajax.php' );

		Functions\expect( 'esc_url' )
			->once()
			->with( 'https://example.com/wp-admin/admin-ajax.php' )
			->andReturn( 'https://example.com/wp-admin/admin-ajax.php' );

		Functions\expect( 'get_the_ID' )
			->once()
			->andReturn( 123 );

		Functions\expect( 'absint' )
			->once()
			->with( 123 )
			->andReturn( 123 );

		Functions\expect( 'esc_attr' )
			->once()
			->with( 123 )
			->andReturn( '123' );

		$attributes = array();
		$content    = '<p>Survey Input Content</p>';

		$html = beastfeedbacks_block_survey_form_render_callback( $attributes, $content );

		$expected_html = '<div class="wp-block-beastfeedbacks-survey-form">' .
			'<form action="https://example.com/wp-admin/admin-ajax.php" name="beastfeedbacks_survey_form" method="POST">' .
			'<input type="hidden" id="_wpnonce" name="_wpnonce" value="test_nonce" />' .
			'<input type="hidden" name="action" value="register_beastfeedbacks_form" />' .
			'<input type="hidden" name="beastfeedbacks_type" value="survey" />' .
			'<input type="hidden" name="id" value="123" />' .
			'<p>Survey Input Content</p>' .
			'</form>' .
			'</div>';

		$this->assertSame( $expected_html, $html );
	}

	/**
	 * Test rendering survey form when content is empty and post ID is 0.
	 */
	public function test_survey_form_render_callback_handles_empty_content(): void {
		Functions\stubs(
			array(
				'get_block_wrapper_attributes' => 'class="wp-block-beastfeedbacks-survey-form"',
				'wp_nonce_field'               => '<input type="hidden" name="_wpnonce" value="nonce" />',
				'admin_url'                    => 'https://example.com/wp-admin/admin-ajax.php',
				'esc_url'                      => function ( $url ) {
					return $url;
				},
				'get_the_ID'                   => 0,
				'absint'                       => function ( $val ) {
					return (int) $val;
				},
				'esc_attr'                     => function ( $val ) {
					return (string) $val;
				},
			)
		);

		$html = beastfeedbacks_block_survey_form_render_callback( array(), '' );

		$this->assertStringContainsString( '<input type="hidden" name="id" value="0" />', $html );
		$this->assertStringContainsString( '<form action="https://example.com/wp-admin/admin-ajax.php"', $html );
		$this->assertStringContainsString( 'name="beastfeedbacks_survey_form"', $html );
		$this->assertStringContainsString( 'value="register_beastfeedbacks_form"', $html );
		$this->assertStringContainsString( 'value="survey"', $html );
	}
}
