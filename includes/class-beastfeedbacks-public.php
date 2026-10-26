<?php
/**
 * 公開用設定
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 公開用設定
 */
class BeastFeedbacks_Public {

	/**
	 * 除外するPOSTキー一覧 (後方互換性のため残す)
	 *
	 * @var array
	 */
	public const IGNORE_KEYS = BeastFeedbacks_Feedback_Service::IGNORE_KEYS;

	/**
	 * Self class
	 *
	 * @var self|null
	 */
	private static $instance = null;

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
	 * Init
	 */
	public function init() {
		$form_action = 'register_beastfeedbacks_form';
		add_action( 'wp_ajax_' . $form_action, array( $this, 'register_beastfeedbacks_form' ) );
		add_action( 'wp_ajax_nopriv_' . $form_action, array( $this, 'register_beastfeedbacks_form' ) );
	}

	/**
	 * アンケートフォームの受け取り処理
	 */
	public function register_beastfeedbacks_form() {
		check_ajax_referer( 'register_beastfeedbacks_form' );

		$service    = BeastFeedbacks_Feedback_Service::get_instance();
		$ip_address = $service->get_ip_address();
		if ( BeastFeedbacks_Rate_Limiter::get_instance()->is_rate_limited( $ip_address ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests', 'beastfeedbacks' ) ), 429 );
		}

		// Security: Verify nonce explicitly before reading or processing $_POST data.
		check_ajax_referer( 'register_beastfeedbacks_form' );

		// Security: Validate that expected parameters exist and are scalar values before sanitizing.
		if ( ! $service->has_valid_request_params( $_POST ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request', 'beastfeedbacks' ) ) );
		}

		$id   = sanitize_text_field( wp_unslash( $_POST['id'] ) );
		$type = sanitize_text_field( wp_unslash( $_POST['beastfeedbacks_type'] ) );

		if ( ! in_array( $type, BeastFeedbacks_Block::TYPES, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid feedback type', 'beastfeedbacks' ) ) );
		}

		$post    = get_post( $id );
		$post_id = $post ? (int) $post->ID : 0;

		if ( ! $service->is_valid_target_post( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'beastfeedbacks' ) ) );
		}

		$user_agent  = $service->get_user_agent();
		$time        = current_time( 'mysql' );
		$title       = "{$ip_address} - {$time}";
		$post_params = $service->extract_post_params( $_POST );
		$content     = $service->format_feedback_content( $user_agent, $ip_address, $type, $post_params );

		$saved = $service->save_feedback( $post_id, $type, $title, $time, $content );
		if ( is_wp_error( $saved ) || ! $saved ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save feedback', 'beastfeedbacks' ) ) );
		}

		$response_data = $service->build_response_data( $post_id, $type );

		wp_send_json( $response_data );
		wp_die();
	}
}
