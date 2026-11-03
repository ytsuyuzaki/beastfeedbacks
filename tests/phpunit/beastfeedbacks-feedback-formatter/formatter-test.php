<?php
/**
 * Tests for BeastFeedbacks_Feedback_Formatter.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Feedback_Formatter_Test extends BeastFeedbacks_TestCase {

	/**
	 * @var BeastFeedbacks_Feedback_Formatter
	 */
	private $formatter;

	public function set_up(): void {
		parent::set_up();
		$this->formatter = BeastFeedbacks_Feedback_Formatter::get_instance();
	}

	/** @test */
	public function prime_parent_caches_caches_parent_posts(): void {
		$parent_id1 = $this->create_post( array( 'post_type' => 'post', 'post_title' => 'Parent 1' ) );
		$parent_id2 = $this->create_post( array( 'post_type' => 'post', 'post_title' => 'Parent 2' ) );

		$post1 = (object) array( 'post_parent' => $parent_id1 );
		$post2 = (object) array( 'post_parent' => $parent_id2 );
		$post3 = (object) array( 'post_parent' => 0 ); // No parent

		clean_post_cache( $parent_id1 );
		clean_post_cache( $parent_id2 );

		$this->assertFalse( wp_cache_get( $parent_id1, 'posts' ) );
		$this->assertFalse( wp_cache_get( $parent_id2, 'posts' ) );

		$this->formatter->prime_parent_caches( array( $post1, $post2, $post3 ) );

		$this->assertNotFalse( wp_cache_get( $parent_id1, 'posts' ) );
		$this->assertNotFalse( wp_cache_get( $parent_id2, 'posts' ) );
	}

	/** @test */
	public function format_post_data_returns_expected_array(): void {
		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Test Parent',
				'post_name'  => 'test-parent',
			)
		);

		$content = wp_json_encode(
			array(
				'type'        => 'survey',
				'ip_address'  => '127.0.0.1',
				'user_agent'  => 'TestBrowser/1.0',
				'post_params' => array(
					'question1' => 'Answer 1',
					'question2' => array( 'Choice A', 'Choice B' ),
				),
			)
		);

		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => $content,
				'post_date'    => '2025-01-01 12:00:00',
			)
		);

		$post = get_post( $post_id );

		$row_data = $this->formatter->format_post_data( $post );

		$this->assertIsArray( $row_data );
		$this->assertStringContainsString( 'test-parent', $row_data['source'] );
		$this->assertSame( '2025-01-01 12:00:00', $row_data['date'] );
		$this->assertSame( 'survey', $row_data['type'] );
		$this->assertSame( '127.0.0.1', $row_data['ip_address'] );
		$this->assertSame( 'TestBrowser/1.0', $row_data['user_agent'] );
		$this->assertSame( 'Answer 1', $row_data['question1'] );
		$this->assertSame( 'Choice A,Choice B', $row_data['question2'] ); // Array imploded
	}

	/** @test */
	public function format_post_data_does_not_overwrite_metadata_with_post_params(): void {
		$parent_id = $this->create_post(
			array(
				'post_type'  => 'page',
				'post_title' => 'Test Parent',
				'post_name'  => 'test-parent',
			)
		);

		$content = wp_json_encode(
			array(
				'type'        => 'survey',
				'ip_address'  => '127.0.0.1',
				'user_agent'  => 'TestBrowser/1.0',
				'post_params' => array(
					'date'       => 'User Date',
					'source'     => 'User Source',
					'type'       => 'User Type',
					'ip_address' => 'User IP',
					'user_agent' => 'User Agent',
					'question1'  => 'Answer 1',
				),
			)
		);

		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => $parent_id,
				'post_content' => $content,
				'post_date'    => '2025-01-01 12:00:00',
			)
		);

		$post = get_post( $post_id );

		$row_data = $this->formatter->format_post_data( $post );

		$this->assertIsArray( $row_data );
		$this->assertStringContainsString( 'test-parent', $row_data['source'] ); // Not overwritten
		$this->assertSame( '2025-01-01 12:00:00', $row_data['date'] ); // Not overwritten
		$this->assertSame( 'survey', $row_data['type'] ); // Not overwritten
		$this->assertSame( '127.0.0.1', $row_data['ip_address'] ); // Not overwritten
		$this->assertSame( 'TestBrowser/1.0', $row_data['user_agent'] ); // Not overwritten
		$this->assertSame( 'Answer 1', $row_data['question1'] ); // Normal param included
	}

	/** @test */
	public function format_post_data_handles_missing_parent_and_content_gracefully(): void {
		$post_id = $this->create_post(
			array(
				'post_type'    => 'beastfeedbacks',
				'post_parent'  => 0,
				'post_content' => 'invalid_json',
				'post_date'    => '2025-01-01 12:00:00',
			)
		);

		$post = get_post( $post_id );

		$row_data = $this->formatter->format_post_data( $post );

		$this->assertIsArray( $row_data );
		$this->assertSame( '', $row_data['source'] );
		$this->assertSame( '2025-01-01 12:00:00', $row_data['date'] );
		$this->assertSame( '', $row_data['type'] );
		$this->assertSame( '', $row_data['ip_address'] );
		$this->assertSame( '', $row_data['user_agent'] );
	}
}
