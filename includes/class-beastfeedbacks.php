<?php
/**
 * 初期化
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 初期化用の処理
 */
class BeastFeedbacks {

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
		$this->load_dependencies();

		if ( is_admin() ) {
			BeastFeedbacks_Admin::get_instance()->init();
		}
		BeastFeedbacks_Public::get_instance()->init();
		BeastFeedbacks_Block::get_instance()->init();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-utils.php';
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-admin.php';
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-public.php';
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-block.php';
	}

	/**
	 * Like数の取得 (非推奨: BeastFeedbacks_Utils::get_like_count に移行)
	 *
	 * @deprecated 0.1.0 Use BeastFeedbacks_Utils::get_like_count() instead.
	 *
	 * @param integer $post_id Like登録に使用したpostを渡す.
	 * @return int
	 */
	public function get_like_count( $post_id ) {
		return BeastFeedbacks_Utils::get_like_count( $post_id );
	}
}
