<?php
/**
 * Tests for BeastFeedbacks_Public::build_response_data().
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_Public_Build_Response_Data_Test extends BeastFeedbacks_TestCase {

	/** @test */
	public function build_response_data_returns_expected_array_for_survey_and_like(): void {
		$instance = \BeastFeedbacks_Public::get_instance();

		$survey_res = $instance->build_response_data( 123, 'survey' );
		$this->assertSame( 1, $survey_res['success'] );
		$this->assertSame( 1, $survey_res['count'] );
		$this->assertStringContainsString( 'questionnaire', $survey_res['message'] );

		$like_res = $instance->build_response_data( 123, 'like' );
		$this->assertSame( 1, $like_res['success'] );
		$this->assertStringContainsString( 'vote', $like_res['message'] );
	}
}
