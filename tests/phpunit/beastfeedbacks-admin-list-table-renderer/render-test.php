<?php
/**
 * Class Render_Test
 *
 * @package BeastFeedbacks
 */

/**
 * Test case for BeastFeedbacks_Admin_List_Table_Renderer
 */
class Render_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Admin_List_Table_Renderer
	 */
	private $renderer;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->renderer = BeastFeedbacks_Admin_List_Table_Renderer::get_instance();
	}

	/**
	 * Test render_date_column method.
	 */
	public function test_render_date_column() {
		$post_id = wp_insert_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'Test Feedback',
				'post_date'  => '2025-01-01 10:00:00',
			)
		);

		ob_start();
		$this->renderer->render_date_column( $post_id );
		$output = ob_get_clean();

		$this->assertEquals( '2025/01/01', $output );
	}

	/**
	 * Test render_type_column method.
	 */
	public function test_render_type_column() {
		$post_id = wp_insert_post(
			array(
				'post_type'  => 'beastfeedbacks',
				'post_title' => 'Test Feedback',
			)
		);
		update_post_meta( $post_id, 'beastfeedbacks_type', 'survey' );

		ob_start();
		$this->renderer->render_type_column( $post_id );
		$output = ob_get_clean();

		$this->assertEquals( 'survey', $output );
	}

	/**
	 * Test render_source_column method.
	 */
	public function test_render_source_column() {
		$parent_id = wp_insert_post(
			array(
				'post_type'  => 'post',
				'post_title' => 'Test Parent',
				'post_name'  => 'test-parent',
				'post_status'=> 'publish',
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_title'  => 'Test Feedback',
				'post_parent' => $parent_id,
			)
		);

		ob_start();
		$this->renderer->render_source_column( $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<a href="', $output );
		$this->assertStringContainsString( 'test-parent', $output );
	}

	/**
	 * Test render_response_column method.
	 */
	public function test_render_response_column() {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_title'   => 'Test Feedback',
				'post_content' => wp_json_encode(
					array(
						'type'        => 'vote',
						'post_params' => array(
							'selected' => 'Option A',
						),
						'ip_address'  => '127.0.0.1',
						'user_agent'  => 'TestBrowser',
					)
				),
			)
		);

		ob_start();
		$this->renderer->render_response_column( $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Option A', $output );
		$this->assertStringContainsString( '127.0.0.1', $output );
		$this->assertStringContainsString( 'TestBrowser', $output );
	}
}
