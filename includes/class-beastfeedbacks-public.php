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
		if ( $service->is_rate_limited( $ip_address ) ) {
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

	/**
	 * POSTデータから不要な項目を除外・サニタイズして抽出する
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::extract_post_params() instead.
	 * @param array $post_data POSTデータ.
	 * @return array
	 */
	public function extract_post_params( array $post_data ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->extract_post_params( $post_data );
	}

	/**
	 * Sanitize parameter values with length limits.
	 *
	 * Security: Prevents resource exhaustion and key/value injection by rejecting complex array structures.
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::sanitize_param_value() instead.
	 * @param mixed $value The value to sanitize.
	 * @return mixed Sanitized string or array.
	 */
	public function sanitize_param_value( $value ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->sanitize_param_value( $value );
	}

	/**
	 * フィードバック本文をJSON形式でフォーマットする
	 *
	 * Performance & Security: Avoid wrapping wp_json_encode() with wp_kses(),
	 * as HTML parsing on serialized JSON strings is redundant and risks corrupting valid JSON payloads.
	 * Input parameters are already sanitized prior to serialization.
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::format_feedback_content() instead.
	 * @param string $user_agent ユーザーエージェント.
	 * @param string $ip_address IPアドレス.
	 * @param string $type       フィードバック種別.
	 * @param array  $post_params 送信されたパラメーター.
	 * @return string
	 */
	public function format_feedback_content( string $user_agent, string $ip_address, string $type, array $post_params ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->format_feedback_content( $user_agent, $ip_address, $type, $post_params );
	}

	/**
	 * フィードバック投稿を保存する
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::save_feedback() instead.
	 * @param int    $post_id 親投稿ID.
	 * @param string $type    フィードバック種別.
	 * @param string $title   投稿タイトル.
	 * @param string $time    投稿日時.
	 * @param string $content 投稿本文.
	 * @return int|WP_Error
	 */
	public function save_feedback( int $post_id, string $type, string $title, string $time, string $content ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->save_feedback( $post_id, $type, $title, $time, $content );
	}

	/**
	 * レスポンス用データを構築する
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::build_response_data() instead.
	 * @param int    $post_id 親投稿ID.
	 * @param string $type    フィードバック種別.
	 * @return array
	 */
	public function build_response_data( int $post_id, string $type ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->build_response_data( $post_id, $type );
	}

	/**
	 * Target post validation check.
	 *
	 * Security: Requires target post to exist, be published, not be a feedback item itself,
	 * and not require a password that has not been provided.
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::is_valid_target_post() instead.
	 * @param int $post_id Post ID.
	 * @return bool True if valid, false otherwise.
	 */
	public function is_valid_target_post( int $post_id ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->is_valid_target_post( $post_id );
	}

	/**
	 * Checks if the request contains valid expected parameters.
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::has_valid_request_params() instead.
	 * @param array $post_data POST data array.
	 * @return bool True if parameters are valid, false otherwise.
	 */
	private function has_valid_request_params( array $post_data ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->has_valid_request_params( $post_data );
	}

	/**
	 * レートリミットの判定とカウント処理
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::is_rate_limited() instead.
	 * @param string $ip_address IPアドレス.
	 * @return bool レートリミット超過の場合は true.
	 */
	public function is_rate_limited( string $ip_address ) {
		return BeastFeedbacks_Feedback_Service::get_instance()->is_rate_limited( $ip_address );
	}

	/**
	 * ユーザーエージェントの取得
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::get_user_agent() instead.
	 * @return string
	 */
	public function get_user_agent() {
		return BeastFeedbacks_Feedback_Service::get_instance()->get_user_agent();
	}

	/**
	 * IPアドレスの取得
	 *
	 * Uses strictly REMOTE_ADDR by default to prevent IP spoofing via untrusted HTTP headers.
	 * Provides a filter hook 'beastfeedbacks_ip_address' for trusted reverse proxy / CDN setups.
	 *
	 * @deprecated Use BeastFeedbacks_Feedback_Service::get_ip_address() instead.
	 * @return string
	 */
	public function get_ip_address() {
		return BeastFeedbacks_Feedback_Service::get_instance()->get_ip_address();
	}
}
