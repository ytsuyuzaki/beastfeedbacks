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
	 */
	public function extract_post_params( array $post_data ) {
		$post_params = array();

		// Security: Enforce maximum parameter limit to prevent resource exhaustion via post parameter flooding.
		$max_params = (int) apply_filters( 'beastfeedbacks_max_post_params', 50 );

		foreach ( array_keys( $post_data ) as $post_key ) {
			if ( count( $post_params ) >= $max_params ) {
				break;
			}

			if ( isset( self::IGNORE_KEYS[ $post_key ] ) ) {
				continue;
			}
			if ( isset( $post_data[ $post_key ] ) ) {
				// Security: Pre-truncate with a buffer to prevent DoS via huge keys.
				// wp_unslash removes slashes which might have expanded the original string.
				// We allocate a buffer (e.g. 200) to safely unslash before final strict truncation.
				$sanitized_key = mb_substr( (string) $post_key, 0, 200 );
				$sanitized_key = sanitize_text_field( wp_unslash( $sanitized_key ) );
				$sanitized_key = mb_substr( $sanitized_key, 0, 100 );

				if ( '' === $sanitized_key ) {
					continue;
				}
				$post_value                    = $post_data[ $post_key ];
				$post_params[ $sanitized_key ] = $this->sanitize_param_value( $post_value );
			}
		}

		return $post_params;
	}

	/**
	 * Sanitize parameter values with length limits.
	 *
	 * Security: Prevents resource exhaustion and key/value injection by rejecting complex array structures.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed Sanitized string or array.
	 */
	public function sanitize_param_value( $value ) {
		if ( is_array( $value ) ) {
			$sanitized_array = array();
			$max_items       = 50;
			$item_count      = 0;

			foreach ( $value as $key => $item ) {
				if ( $item_count >= $max_items ) {
					break;
				}

				// Security: Reject complex array structures entirely to prevent deep parsing DoS.
				if ( is_array( $item ) || is_object( $item ) ) {
					return '';
				}

				// Security: Pre-truncate with a buffer to prevent DoS via huge keys.
				$sanitized_item_key = mb_substr( (string) $key, 0, 200 );
				$unslashed_key      = wp_unslash( $sanitized_item_key );
				// Strip C0 control characters (except \t, \n, \r) before sanitize_text_field.
				$no_control_key     = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $unslashed_key );
				$sanitized_item_key = sanitize_text_field( $no_control_key );
				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );

				if ( '' === $sanitized_item_key ) {
					continue;
				}

				$sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item );
				++$item_count;
			}

			return $sanitized_array;
		}

		if ( is_object( $value ) ) {
			return '';
		}

		$unslashed_val = wp_unslash( (string) $value );
		// Strip C0 control characters (except \t, \n, \r) previously handled by wp_kses().
		$no_control_val = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $unslashed_val );
		$sanitized_val  = wp_specialchars_decode( sanitize_text_field( $no_control_val ), ENT_QUOTES );
		return mb_substr( $sanitized_val, 0, 2000 );
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
	 * @return bool True if parameters are valid, false otherwise.
	 */
	public function has_valid_request_params() {
		return isset( $_POST['id'] ) && isset( $_POST['beastfeedbacks_type'] ) && is_scalar( $_POST['id'] ) && is_scalar( $_POST['beastfeedbacks_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * レートリミットの判定とカウント処理
	 *
	 * @param string $ip_address IPアドレス.
	 * @return bool レートリミット超過の場合は true.
	 */
	public function is_rate_limited( string $ip_address ) {
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}

		$pre = apply_filters( 'beastfeedbacks_pre_is_rate_limited', null, $ip_address );
		if ( null !== $pre ) {
			return (bool) $pre;
		}

		$key          = 'bf_rl_' . md5( '' === $ip_address ? 'unknown' : $ip_address );
		$entry        = get_transient( $key );
		$now          = time();
		$max_requests = max( 1, (int) apply_filters( 'beastfeedbacks_rate_limit_max_requests', 10 ) );
		$window       = max( 1, (int) apply_filters( 'beastfeedbacks_rate_limit_window', 60 ) );

		if ( ! is_array( $entry ) || ! isset( $entry['count'], $entry['reset_at'] ) || $now >= $entry['reset_at'] ) {
			$entry = array(
				'count'    => 1,
				'reset_at' => $now + $window,
			);
			set_transient( $key, $entry, $window );
			return false;
		}

		if ( $entry['count'] >= $max_requests ) {
			return true;
		}

		++$entry['count'];
		$ttl = max( 1, $entry['reset_at'] - $now );
		set_transient( $key, $entry, $ttl );

		return false;
	}

	/**
	 * ユーザーエージェントの取得
	 *
	 * @return string
	 */
	public function get_user_agent() {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) || ! is_scalar( $_SERVER['HTTP_USER_AGENT'] ) ) { // @codingStandardsIgnoreLine
			return '';
		}

		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ); // @codingStandardsIgnoreLine

		// Security: Limit User-Agent string length to mitigate header flooding and buffer bloat.
		return mb_substr( $ua, 0, 500 );
	}

	/**
	 * IPアドレスの取得
	 *
	 * Uses strictly REMOTE_ADDR by default to prevent IP spoofing via untrusted HTTP headers.
	 * Provides a filter hook 'beastfeedbacks_ip_address' for trusted reverse proxy / CDN setups.
	 *
	 * @return string
	 */
	public function get_ip_address() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_scalar( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}

		/**
		 * Filter the client IP address.
		 *
		 * Allows site administrators operating behind trusted reverse proxies or CDNs
		 * to customize client IP resolution safely.
		 *
		 * @since 0.1.6
		 *
		 * @param string $ip The client IP address from REMOTE_ADDR.
		 */
		$ip = apply_filters( 'beastfeedbacks_ip_address', $ip );

		return is_scalar( $ip ) && false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? (string) $ip : '';
	}
}
