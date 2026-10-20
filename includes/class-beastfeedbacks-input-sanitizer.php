<?php
/**
 * 入力データのサニタイズ
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 入力データのサニタイズを行うクラス
 */
class BeastFeedbacks_Input_Sanitizer {

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
	 * サニタイズされたキーを返す
	 *
	 * @param mixed $key 対象のキー.
	 * @return string サニタイズされたキー
	 */
	private function sanitize_key( $key ) {
		// Security: Pre-truncate with a buffer to prevent DoS via huge keys.
		// wp_unslash removes slashes which might have expanded the original string.
		// We allocate a buffer (e.g. 200) to safely unslash before final strict truncation.
		$sanitized_key = mb_substr( (string) $key, 0, 200 );
		$unslashed_key = wp_unslash( $sanitized_key );
		// Strip C0 control characters (except \t, \n, \r) before sanitize_text_field.
		$no_control_key = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $unslashed_key );
		$sanitized_key  = sanitize_text_field( $no_control_key );
		$sanitized_key  = mb_substr( $sanitized_key, 0, 100 );
		return $sanitized_key;
	}

	/**
	 * POSTデータから不要な項目を除外・サニタイズして抽出する
	 *
	 * @param array $post_data POSTデータ.
	 * @param array $ignore_keys 除外するキーの連想配列.
	 * @return array
	 */
	public function extract_post_params( array $post_data, array $ignore_keys = array() ) {
		$post_params = array();

		// Security: Enforce maximum parameter limit to prevent resource exhaustion via post parameter flooding.
		$max_params = (int) apply_filters( 'beastfeedbacks_max_post_params', 50 );

		foreach ( array_keys( $post_data ) as $post_key ) {
			if ( count( $post_params ) >= $max_params ) {
				break;
			}

			if ( isset( $ignore_keys[ $post_key ] ) ) {
				continue;
			}
			if ( isset( $post_data[ $post_key ] ) ) {
				$sanitized_key = $this->sanitize_key( $post_key );

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

				$sanitized_item_key = $this->sanitize_key( $key );

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
}
