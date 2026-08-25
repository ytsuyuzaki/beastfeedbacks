<?php
/**
 * Tests for BeastFeedbacks_Public::register_beastfeedbacks_form().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Register_Beastfeedbacks_Form_Test extends BeastFeedbacks_TestCase {

	protected function set_up(): void {
		parent::set_up();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}
	}

	protected function tear_down(): void {
		unset( $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] );
		$_POST    = array();
		$_REQUEST = array();

		parent::tear_down();
	}

	/** @test */
	public function register_beastfeedbacks_form_stores_survey_response_and_ignores_control_fields(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'survey parent',
			)
		);

		$_SERVER['REMOTE_ADDR']     = '192.0.2.10';
		$_SERVER['HTTP_USER_AGENT'] = 'Unit Test Agent';
		$_POST                      = $this->create_ajax_request(
			array(
				'Satisfaction' => 'Very satisfied',
				'Features'     => array( 'Speed', 'Support<script>' ),
			),
			$parent_id,
			'survey'
		);
		$_REQUEST                   = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 1, $response['count'] );
		$this->assertStringContainsString( 'questionnaire', $response['message'] );

		$stored = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_parent'    => $parent_id,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);

		$this->assertCount( 1, $stored );
		$this->created_ids[] = $stored[0]->ID;

		$content = json_decode( stripslashes( $stored[0]->post_content ), true );

		$this->assertSame( 'survey', $content['type'] );
		$this->assertSame( '192.0.2.10', $content['ip_address'] );
		$this->assertSame( 'Unit Test Agent', $content['user_agent'] );
		$this->assertSame( 'Very satisfied', $content['post_params']['Satisfaction'] );
		$this->assertSame( array( 'Speed', 'Support' ), $content['post_params']['Features'] );
		$this->assertArrayNotHasKey( 'action', $content['post_params'] );
		$this->assertArrayNotHasKey( '_wp_http_referer', $content['post_params'] );
		$this->assertSame( 'survey', get_post_meta( $stored[0]->ID, 'beastfeedbacks_type', true ) );
	}

	/** @test */
	public function register_beastfeedbacks_form_returns_updated_count_for_like_response(): void {
		$parent_id = $this->create_post(
			array(
				'post_title' => 'like parent',
			)
		);

		$this->create_like_post( $parent_id );

		$_SERVER['REMOTE_ADDR'] = '192.0.2.20';
		$_POST                  = $this->create_ajax_request( array(), $parent_id, 'like' );
		$_REQUEST               = $_POST;

		$response = $this->call_ajax_handler();

		$this->assertSame( 1, $response['success'] );
		$this->assertSame( 2, $response['count'] );
		$this->assertStringContainsString( 'vote', $response['message'] );

		$stored = get_posts(
			array(
				'post_type'      => 'beastfeedbacks',
				'post_parent'    => $parent_id,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => 'beastfeedbacks_type',
				'meta_value'     => 'like',
			)
		);

		$this->assertCount( 2, $stored );
		foreach ( $stored as $post ) {
			$this->created_ids[] = $post->ID;
		}
	}
}
