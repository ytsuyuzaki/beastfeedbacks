<?php
/**
 * フィードバックのプロセッサ
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.12
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * フィードバックリクエストを処理するクラス
 */
class BeastFeedbacks_Feedback_Processor {

	/**
	 * Self class
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
	}

	/**
	 * Instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Process feedback request.
	 *
	 * @param array $post_data The request data (usually $_POST).
	 * @return array Associative array containing 'success', 'data', and 'status_code'.
	 */
	public function process( array $post_data ) {
		$service    = BeastFeedbacks_Feedback_Service::get_instance();
		$context    = BeastFeedbacks_Client_Context::get_instance();
		$ip_address = $context->get_ip_address();

		if ( BeastFeedbacks_Rate_Limiter::get_instance()->is_rate_limited( $ip_address ) ) {
			return array(
				'success'     => false,
				'data'        => array( 'message' => __( 'Too many requests', 'beastfeedbacks' ) ),
				'status_code' => 429,
			);
		}

		// Security: Validate that expected parameters exist and are scalar values before sanitizing.
		if ( ! $service->has_valid_request_params( $post_data ) ) {
			return array(
				'success'     => false,
				'data'        => array( 'message' => __( 'Invalid request', 'beastfeedbacks' ) ),
				'status_code' => 400,
			);
		}

		$id   = sanitize_text_field( wp_unslash( $post_data['id'] ) );
		$type = sanitize_text_field( wp_unslash( $post_data['beastfeedbacks_type'] ) );

		if ( ! in_array( $type, BeastFeedbacks_Block::TYPES, true ) ) {
			return array(
				'success'     => false,
				'data'        => array( 'message' => __( 'Invalid feedback type', 'beastfeedbacks' ) ),
				'status_code' => 400,
			);
		}

		$post    = get_post( $id );
		$post_id = $post ? (int) $post->ID : 0;

		if ( ! $service->is_valid_target_post( $post_id ) ) {
			return array(
				'success'     => false,
				'data'        => array( 'message' => __( 'Invalid post ID', 'beastfeedbacks' ) ),
				'status_code' => 400,
			);
		}

		$user_agent  = $context->get_user_agent();
		$time        = current_time( 'mysql' );
		$title       = "{$ip_address} - {$time}";
		$post_params = $service->extract_post_params( $post_data );
		$content     = $service->format_feedback_content( $user_agent, $ip_address, $type, $post_params );

		$saved = $service->save_feedback( $post_id, $type, $title, $time, $content );
		if ( is_wp_error( $saved ) || ! $saved ) {
			return array(
				'success'     => false,
				'data'        => array( 'message' => __( 'Failed to save feedback', 'beastfeedbacks' ) ),
				'status_code' => 500,
			);
		}

		$response_data = $service->build_response_data( $post_id, $type );

		return array(
			'success'     => true,
			'data'        => $response_data,
			'status_code' => 200,
		);
	}
}
