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
		// Security: Verify nonce explicitly before reading or processing $_POST data.
		check_ajax_referer( 'register_beastfeedbacks_form' );

		$result = BeastFeedbacks_Feedback_Processor::get_instance()->process( $_POST );

		if ( ! $result['success'] ) {
			wp_send_json_error( $result['data'], $result['status_code'] );
		}

		wp_send_json( $result['data'] );
		wp_die();
	}
}
