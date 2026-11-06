<?php
/**
 * クライアントのコンテキスト（IPやUA）を取得・管理するクラス
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.13
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * HTTPリクエストにおけるクライアント環境情報を管理するクラス
 */
class BeastFeedbacks_Client_Context {

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
