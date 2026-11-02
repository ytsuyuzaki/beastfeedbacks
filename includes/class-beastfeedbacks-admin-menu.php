<?php
/**
 * 管理画面のメニュー登録およびUI制御
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.10
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 管理画面のメニュー登録およびUI制御を行うクラス
 */
class BeastFeedbacks_Admin_Menu {

	/**
	 * Self class
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * 追加する投稿タイプ、フィードバックの入力値の保存に活用
	 *
	 * @var string ポストタイプ.
	 */
	public $post_type = 'beastfeedbacks';

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
	 * Init
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// フィードバックの管理ページの構築.
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_filter( 'bulk_actions-edit-' . $this->post_type, array( $this, 'admin_bulk_actions' ) );
		add_filter( 'views_edit-' . $this->post_type, array( $this, 'admin_view_tabs' ) );
	}

	/**
	 * 静的ファイルのcssやjsを読み込む
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'edit-beastfeedbacks' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			BEASTFEEDBACKS_DOMAIN,
			BEASTFEEDBACKS_URL . 'public/js/beastfeedbacks-admin.js',
			array(),
			BEASTFEEDBACKS_VERSION,
			true
		);

		wp_enqueue_style(
			BEASTFEEDBACKS_DOMAIN,
			BEASTFEEDBACKS_URL . 'public/css/beastfeedbacks-admin.css',
			array(),
			BEASTFEEDBACKS_VERSION
		);
	}


	/**
	 * メニューページの登録
	 */
	public function add_menu_page() {
		add_menu_page(
			'BeastFeedbacks',
			'BeastFeedbacks',
			'edit_pages',
			'edit.php?post_type=' . $this->post_type,
			'',
			'dashicons-feedback'
		);

		register_post_type(
			$this->post_type,
			array(
				'labels'                => array(
					'name' => 'Beastfeedbacks',
				),

				'public'                => false,
				'show_ui'               => true,
				'show_in_menu'          => false,
				'show_in_admin_bar'     => false,
				'show_in_rest'          => false,

				'rewrite'               => false,
				'query_var'             => false,

				'rest_controller_class' => '',

				'map_meta_cap'          => true,
				'capability_type'       => 'page',
				'capabilities'          => array(
					'create_posts' => 'do_not_allow',
				),
			)
		);
	}

	/**
	 * プルダウンの一括操作、編集を削除
	 *
	 * @param array $actions List of actions available.
	 * @return array $actions
	 */
	public function admin_bulk_actions( $actions ) {
		global $current_screen;
		if ( 'edit-beastfeedbacks' !== $current_screen->id ) {
			return $actions;
		}

		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * タブ表示の整形
	 *
	 * @param array $views List of post views.
	 * @return array $views
	 */
	public function admin_view_tabs( $views ) {
		global $current_screen;
		if ( 'edit-beastfeedbacks' !== $current_screen->id ) {
			return $views;
		}

		unset( $views['publish'] );

		return $views;
	}
}
