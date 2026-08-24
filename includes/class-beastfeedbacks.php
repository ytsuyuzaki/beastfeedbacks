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
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-admin.php';
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-public.php';
		require_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-block.php';
	}

	/**
	 * Like数の取得
	 * NOTE: 共通化クラスを作成して移行する
	 *
	 * @param integer $post_id Like登録に使用したpostを渡す.
	 */
	public function get_like_count( $post_id ) {
		// Performance optimization: Limit results to 1 ID and disable meta/term caching
		// to minimize database query payload and avoid hydrating WP_Post objects when only found_posts count is needed.
		$args  = array(
			'post_type'              => 'beastfeedbacks',
			'post_parent'            => $post_id,
			'meta_query'             => array( // NOTE: クエリ効率化.
				array(
					'key'   => 'beastfeedbacks_type',
					'value' => 'like',
				),
			),
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$query = new WP_Query( $args );
		return $query->found_posts;
	}
}
