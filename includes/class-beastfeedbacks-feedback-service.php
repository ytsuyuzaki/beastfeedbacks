<?php
/**
 * フィードバックの送信・保存サービス
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * フィードバックのビジネスロジックをカプセル化するクラス
 */
class BeastFeedbacks_Feedback_Service {

	/**
	 * 除外するPOSTキー一覧
	 *
	 * @var array
	 */
	public const IGNORE_KEYS = array(
		'id'                  => true,
		'beastfeedbacks_type' => true,
		'action'              => true,
		'_wp_http_referer'    => true,
		'_wpnonce'            => true,
	);

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
	 * POSTデータから不要な項目を除外・サニタイズして抽出する
	 *
	 * @param array $post_data POSTデータ.
	 * @return array
	 * @deprecated Use BeastFeedbacks_Input_Sanitizer::get_instance()->extract_post_params() instead.
	 */
	public function extract_post_params( array $post_data ) {
		return BeastFeedbacks_Input_Sanitizer::get_instance()->extract_post_params( $post_data, self::IGNORE_KEYS );
	}

	/**
	 * Sanitize parameter values with length limits.
	 *
	 * Security: Prevents resource exhaustion and key/value injection by rejecting complex array structures.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed Sanitized string or array.
	 * @deprecated Use BeastFeedbacks_Input_Sanitizer::get_instance()->sanitize_param_value() instead.
	 */
	public function sanitize_param_value( $value ) {
		return BeastFeedbacks_Input_Sanitizer::get_instance()->sanitize_param_value( $value );
	}

	/**
	 * フィードバック本文をJSON形式でフォーマットする
	 *
	 * Performance & Security: Avoid wrapping wp_json_encode() with wp_kses(),
	 * as HTML parsing on serialized JSON strings is redundant and risks corrupting valid JSON payloads.
	 * Input parameters are already sanitized prior to serialization.
	 *
	 * @param string $user_agent ユーザーエージェント.
	 * @param string $ip_address IPアドレス.
	 * @param string $type       フィードバック種別.
	 * @param array  $post_params 送信されたパラメーター.
	 * @return string
	 */
	public function format_feedback_content( string $user_agent, string $ip_address, string $type, array $post_params ) {
		// Performance optimization: wp_json_encode() produces a clean JSON payload.
		// Passing JSON to wp_kses() runs unnecessary HTML parsing & regex sanitization over stringified JSON.
		return addslashes(
			wp_json_encode(
				array(
					'user_agent'  => $user_agent,
					'ip_address'  => $ip_address,
					'type'        => $type,
					'post_params' => $post_params,
				),
				JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
			)
		);
	}

	/**
	 * フィードバック投稿を保存する
	 *
	 * @param int    $post_id 親投稿ID.
	 * @param string $type    フィードバック種別.
	 * @param string $title   投稿タイトル.
	 * @param string $time    投稿日時.
	 * @param string $content 投稿本文.
	 * @return int|WP_Error
	 */
	public function save_feedback( int $post_id, string $type, string $title, string $time, string $content ) {
		return wp_insert_post(
			array(
				'post_date'    => $time,
				'post_type'    => 'beastfeedbacks',
				'post_status'  => 'publish',
				'post_parent'  => $post_id,
				'post_title'   => addslashes( wp_kses( $title, array() ) ),
				'post_name'    => md5( $title ),
				'post_content' => $content,
				'meta_input'   => array(
					'beastfeedbacks_type' => $type,
				),
			)
		);
	}

	/**
	 * レスポンス用データを構築する
	 *
	 * @param int    $post_id 親投稿ID.
	 * @param string $type    フィードバック種別.
	 * @return array
	 */
	public function build_response_data( int $post_id, string $type ) {
		$message = ( 'survey' === $type )
			? __( 'Thank you for your responses to the questionnaire. ', 'beastfeedbacks' )
			: __( 'Thank you for the vote. ', 'beastfeedbacks' );
		$count   = ( 'like' === $type )
			? BeastFeedbacks_Utils::get_like_count( $post_id )
			: 1;

		return array(
			'success' => 1,
			'message' => $message,
			'count'   => $count,
		);
	}

	/**
	 * Target post validation check.
	 *
	 * Security: Requires target post to exist, be published, not be a feedback item itself,
	 * and not require a password that has not been provided.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if valid, false otherwise.
	 */
	public function is_valid_target_post( int $post_id ) {
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}

		if ( 'beastfeedbacks' === get_post_type( $post_id ) ) {
			return false;
		}

		if ( post_password_required( $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the request contains valid expected parameters.
	 *
	 * @param array $post_data POST data array.
	 * @return bool True if parameters are valid, false otherwise.
	 */
	public function has_valid_request_params( array $post_data ) {
		return isset( $post_data['id'] ) && isset( $post_data['beastfeedbacks_type'] ) && is_scalar( $post_data['id'] ) && is_scalar( $post_data['beastfeedbacks_type'] );
	}

	/**
	 * レートリミットの判定とカウント処理
	 *
	 * @param string $ip_address IPアドレス.
	 * @return bool レートリミット超過の場合は true.
	 * @deprecated Use BeastFeedbacks_Rate_Limiter::get_instance()->is_rate_limited() instead.
	 */
	public function is_rate_limited( string $ip_address ) {
		return BeastFeedbacks_Rate_Limiter::get_instance()->is_rate_limited( $ip_address );
	}

	/**
	 * ユーザーエージェントの取得
	 *
	 * @return string
	 * @deprecated Use BeastFeedbacks_Client_Context::get_instance()->get_user_agent() instead.
	 */
	public function get_user_agent() {
		return BeastFeedbacks_Client_Context::get_instance()->get_user_agent();
	}

	/**
	 * IPアドレスの取得
	 *
	 * @return string
	 * @deprecated Use BeastFeedbacks_Client_Context::get_instance()->get_ip_address() instead.
	 */
	public function get_ip_address() {
		return BeastFeedbacks_Client_Context::get_instance()->get_ip_address();
	}
}
