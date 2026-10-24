<?php
/**
 * Tests for BeastFeedbacks_CSV_Generator.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_CSV_Generator_Test extends BeastFeedbacks_TestCase {

	/**
	 * Setup.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Clean up.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}

	/** @test */
	public function esc_csv_prefixes_when_dangerous_first_char(): void {
		$generator = \BeastFeedbacks_CSV_Generator::get_instance();

		$this->assertSame( "' =SUM(A1:A2)", $generator->esc_csv( '=SUM(A1:A2)' ) );
		$this->assertSame( "' +1+2", $generator->esc_csv( '+1+2' ) );
		$this->assertSame( "' -1", $generator->esc_csv( '-1' ) );
		$this->assertSame( "' @cmd", $generator->esc_csv( '@cmd' ) );
		$this->assertSame( "' \tcmd", $generator->esc_csv( "\tcmd" ) );
		$this->assertSame( "' \rcmd", $generator->esc_csv( "\rcmd" ) );
		$this->assertSame( "'  =SUM(A1:A2)", $generator->esc_csv( ' =SUM(A1:A2)' ) );
		$this->assertSame( "'   +1+2", $generator->esc_csv( '  +1+2' ) );
		$this->assertSame( "' \x0C=SUM(A1:A2)", $generator->esc_csv( "\x0C=SUM(A1:A2)" ) );
		$this->assertSame( "' Line1\n=SUM(A1:A2)", $generator->esc_csv( "Line1\n=SUM(A1:A2)" ) );

		$this->assertSame( 'safe', $generator->esc_csv( 'safe' ) );
		$this->assertSame( '  space', $generator->esc_csv( '  space' ) );
	}

	/** @test */
	public function get_export_posts_returns_only_published_feedbacks(): void {
		wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_title'  => 'P1',
			)
		);
		wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'draft',
				'post_title'  => 'P2',
			)
		);

		$generator = \BeastFeedbacks_CSV_Generator::get_instance();
		$posts = $generator->get_export_posts();

		$this->assertCount( 1, $posts );
		$this->assertSame( 'P1', $posts[0]->post_title );
	}

	/** @test */
	public function stream_to_handle_writes_csv_content(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_title'  => 'Feedback',
				'post_content' => wp_json_encode(
					array(
						'type' => 'survey',
						'ip_address' => '1.2.3.4',
						'user_agent' => 'UA',
						'post_params' => array(
							'q1' => 'answer1',
						)
					)
				)
			)
		);

		$generator = \BeastFeedbacks_CSV_Generator::get_instance();

		$temp = fopen( 'php://temp', 'w+' );
		$generator->stream_to_handle( $temp );

		rewind( $temp );
		$csv_output = stream_get_contents( $temp );
		fclose( $temp );

		$this->assertStringContainsString( 'source,date,type,ip_address,user_agent,q1', $csv_output );
		$this->assertStringContainsString( 'survey,1.2.3.4,UA,answer1', $csv_output );
	}

	/** @test */
	public function get_csv_data_formats_data_correctly(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'beastfeedbacks',
				'post_status' => 'publish',
				'post_title'  => 'Feedback',
				'post_content' => wp_json_encode(
					array(
						'type' => 'like',
						'ip_address' => '5.6.7.8',
						'user_agent' => 'Test',
						'post_params' => array(
							'like' => 'yes',
						)
					)
				)
			)
		);

		$post = get_post( $post_id );
		$generator = \BeastFeedbacks_CSV_Generator::get_instance();

		$data = $generator->get_csv_data( array( $post ) );

		$this->assertArrayHasKey( 'type', $data );
		$this->assertSame( 'like', $data['type'][ $post_id ] );
		$this->assertArrayHasKey( 'like', $data );
		$this->assertSame( 'yes', $data['like'][ $post_id ] );
	}
}
