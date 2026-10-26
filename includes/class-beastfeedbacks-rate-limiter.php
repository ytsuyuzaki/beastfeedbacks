<?php
/**
 * レートリミット管理
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * レートリミット機能を提供するクラス
 */
class BeastFeedbacks_Rate_Limiter {

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
}
