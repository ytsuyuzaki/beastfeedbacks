<?php
/**
 * 管理画面
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 管理画面
 */
class BeastFeedbacks_Admin {

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
	 * CSVエクスポート用のアクション名.
	 *
	 * @var string
	 */

	public $export_action_name = 'beastfeedbacks_export';

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

		add_action( 'restrict_manage_posts', array( $this, 'add_export_button' ) );

		BeastFeedbacks_Admin_List::get_instance()->init();
		BeastFeedbacks_Export::get_instance()->init( $this->export_action_name );
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

	/**
	 * Extract structured content data from post_content JSON.
	 *
	 * @param string $post_content JSON string stored in post_content.
	 * @return array Associative array containing parsed JSON fields and key-presence booleans.
	 */
	public function extract_post_content_data( $post_content ) {
		static $cache = array();

		if ( isset( $cache[ $post_content ] ) ) {
			return $cache[ $post_content ];
		}

		// Performance optimization: Bounding in-memory static cache size to 100 items prevents unbounded
		// memory allocation during large batch operations (e.g. multi-thousand row CSV exports).
		if ( count( $cache ) >= 100 ) {
			$cache = array();
		}

		$content = json_decode( $post_content, true );
		if ( ! is_array( $content ) ) {
			$cache[ $post_content ] = array(
				'is_valid'       => false,
				'type'           => '',
				'post_params'    => array(),
				'ip_address'     => '',
				'user_agent'     => '',
				'has_ip_address' => false,
				'has_user_agent' => false,
			);
			return $cache[ $post_content ];
		}

		$type        = isset( $content['type'] ) ? $content['type'] : '';
		$post_params = isset( $content['post_params'] ) && is_array( $content['post_params'] )
			? $content['post_params']
			: array();

		$ip_address = isset( $content['ip_address'] ) ? $content['ip_address'] : '';
		$user_agent = isset( $content['user_agent'] ) ? $content['user_agent'] : '';

		$cache[ $post_content ] = array(
			'is_valid'       => true,
			'type'           => $type,
			'post_params'    => $post_params,
			'ip_address'     => $ip_address,
			'user_agent'     => $user_agent,
			'has_ip_address' => isset( $content['ip_address'] ),
			'has_user_agent' => isset( $content['user_agent'] ),
		);

		return $cache[ $post_content ];
	}

	/**
	 * Retrieve and cache permalink URL and parsed path for a given parent post ID.
	 *
	 * @param int $parent_id Parent post ID.
	 * @return array Associative array containing 'url' and 'path'.
	 */
	public function get_parent_permalink_data( $parent_id ) {
		static $cache = array();

		if ( isset( $cache[ $parent_id ] ) ) {
			return $cache[ $parent_id ];
		}

		if ( count( $cache ) >= 100 ) {
			$cache = array();
		}

		$form_url            = get_permalink( $parent_id );
		$parsed_url          = wp_parse_url( $form_url );
		$cache[ $parent_id ] = array(
			'url'  => $form_url,
			'path' => esc_html( isset( $parsed_url['path'] ) ? $parsed_url['path'] : '' ),
		);

		return $cache[ $parent_id ];
	}

	/**
	 * Add export button for the response list screen.
	 *
	 * @return void
	 */
	public function add_export_button() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! isset( $screen->id ) || 'edit-beastfeedbacks' !== $screen->id ) {
			return;
		}

		$action = $this->export_action_name;
		$nonce  = wp_create_nonce( 'beastfeedbacks_csv_export' );
		$url    = admin_url( 'admin-ajax.php' );

		?>
		<button
			type="button"
			class="button button-primary beastfeedbacks-export-btn"
			data-endpoint="<?php echo esc_attr( $url ); ?>"
			data-action="<?php echo esc_attr( $action ); ?>"
			data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<?php echo esc_html__( 'Export', 'beastfeedbacks' ); ?>
		</button>
		<?php
	}

	/**
	 * Download exported data as CSV
	 */
	public function download_csv() {
		BeastFeedbacks_Export::get_instance()->download_csv();
	}

	/**
	 * Stream CSV export directly to output in chunks to minimize memory usage.
	 *
	 * @param string $filename CSV file name.
	 * @return void
	 */
	public function stream_csv( $filename ) {
		BeastFeedbacks_Export::get_instance()->stream_csv( $filename );
	}

	/**
	 * Retrieve posts for CSV export
	 *
	 * @return array List of WP_Post objects.
	 */
	public function get_export_posts() {
		return BeastFeedbacks_Export::get_instance()->get_export_posts();
	}

	/**
	 * Extract CSV data and fields from feedback posts
	 *
	 * @param array $posts List of WP_Post objects.
	 * @return array Map of field keys to associative array of [ post_id => data_value ].
	 */
	public function get_csv_data( array $posts ) {
		return BeastFeedbacks_Export::get_instance()->get_csv_data( $posts );
	}

	/**
	 * Output CSV headers and stream content to php://output
	 *
	 * @param string $filename   CSV file name.
	 * @param array  $posts      List of WP_Post objects.
	 * @param array  $post_datas Formatted post data map.
	 * @return void
	 */
	public function output_csv( $filename, array $posts, array $post_datas ) {
		BeastFeedbacks_Export::get_instance()->output_csv( $filename, $posts, $post_datas );
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * @param string $field - the CSV field.
	 * @return string
	 */
	public function esc_csv( $field ) {
		return BeastFeedbacks_Export::get_instance()->esc_csv( $field );
	}

	// --------------------------------------------------------------------------------
	// Deprecated / Delegated Methods for Backward Compatibility
	// --------------------------------------------------------------------------------

	/**
	 * 一覧で表示するカラム
	 *
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->manage_posts_columns() instead.
	 */
	public function manage_posts_columns() {
		return BeastFeedbacks_Admin_List::get_instance()->manage_posts_columns();
	}

	/**
	 * 一覧で表示する行
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->manage_posts_custom_column() instead.
	 */
	public function manage_posts_custom_column( $column_name, $post_id ) {
		BeastFeedbacks_Admin_List::get_instance()->manage_posts_custom_column( $column_name, $post_id );
	}

	/**
	 * Add actions to beastfeedbacks response rows in WP Admin.
	 *
	 * @param string[] $actions Default actions.
	 * @return string[]
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->manage_post_row_actions() instead.
	 */
	public function manage_post_row_actions( $actions ) {
		return BeastFeedbacks_Admin_List::get_instance()->manage_post_row_actions( $actions );
	}

	/**
	 * Wp_untrash_post filter handler.
	 *
	 * @param string $current_status   The status to be set.
	 * @param int    $post_id          The post ID.
	 * @param string $previous_status  The previous status.
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->untrash_beastfeedbacks_status_handler() instead.
	 */
	public function untrash_beastfeedbacks_status_handler( $current_status, $post_id, $previous_status ) {
		return BeastFeedbacks_Admin_List::get_instance()->untrash_beastfeedbacks_status_handler( $current_status, $post_id, $previous_status );
	}

	/**
	 * Add a post filter dropdown at the top of the admin page.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->add_type_filter() instead.
	 */
	public function add_type_filter() {
		BeastFeedbacks_Admin_List::get_instance()->add_type_filter();
	}

	/**
	 * Add a post filter dropdown at the top of the admin page.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->add_source_filter() instead.
	 */
	public function add_source_filter() {
		BeastFeedbacks_Admin_List::get_instance()->add_source_filter();
	}

	/**
	 * Clear parent ids cache for source filter when a feedback post is created, updated, or deleted.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->clear_source_filter_cache() instead.
	 */
	public function clear_source_filter_cache( $post_id = 0, $post = null ) {
		BeastFeedbacks_Admin_List::get_instance()->clear_source_filter_cache( $post_id, $post );
	}

	/**
	 * Type フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->type_filter_result() instead.
	 */
	public function type_filter_result( $query ) {
		BeastFeedbacks_Admin_List::get_instance()->type_filter_result( $query );
	}

	/**
	 * Prime parent post and post meta caches to prevent N+1 queries in admin list table.
	 *
	 * @param array    $posts Array of WP_Post objects.
	 * @param WP_Query $query WP_Query instance.
	 * @return array
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->prime_parent_post_caches() instead.
	 */
	public function prime_parent_post_caches( $posts, $query ) {
		return BeastFeedbacks_Admin_List::get_instance()->prime_parent_post_caches( $posts, $query );
	}

	/**
	 * Source フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List::get_instance()->source_filter_result() instead.
	 */
	public function source_filter_result( $query ) {
		BeastFeedbacks_Admin_List::get_instance()->source_filter_result( $query );
	}
}
