<?php
/**
 * Tests for BeastFeedbacks_Admin::get_csv_data().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Admin_Get_Csv_Data_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function get_csv_data_formats_posts_correctly(): void {
		$admin = \BeastFeedbacks_Admin::get_instance();

		$post1 = (object) array(
			'ID'           => 10,
			'post_parent'  => 0,
			'post_date'    => '2025-01-01 10:00:00',
			'post_content' => wp_json_encode(
				array(
					'type'        => 'survey',
					'ip_address'  => '127.0.0.1',
					'user_agent'  => 'TestAgent',
					'post_params' => array(
						'q1' => 'Answer 1',
						'q2' => array( 'Opt A', 'Opt B' ),
					),
				)
			),
		);

		$csv_data = $admin->get_csv_data( array( $post1 ) );

		$this->assertArrayHasKey( 'source', $csv_data );
		$this->assertArrayHasKey( 'date', $csv_data );
		$this->assertArrayHasKey( 'type', $csv_data );
		$this->assertArrayHasKey( 'ip_address', $csv_data );
		$this->assertArrayHasKey( 'user_agent', $csv_data );
		$this->assertArrayHasKey( 'q1', $csv_data );
		$this->assertArrayHasKey( 'q2', $csv_data );

		$this->assertSame( '', $csv_data['source'][10] );
		$this->assertSame( '2025-01-01 10:00:00', $csv_data['date'][10] );
		$this->assertSame( 'survey', $csv_data['type'][10] );
		$this->assertSame( '127.0.0.1', $csv_data['ip_address'][10] );
		$this->assertSame( 'TestAgent', $csv_data['user_agent'][10] );
		$this->assertSame( 'Answer 1', $csv_data['q1'][10] );
		$this->assertSame( 'Opt A,Opt B', $csv_data['q2'][10] );
	}
}
