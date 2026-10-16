<?php
/**
 * 管理画面一覧テーブル表示
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 管理画面一覧テーブル表示
 */
class BeastFeedbacks_Admin_List {

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
		add_filter( 'post_row_actions', array( $this, 'manage_post_row_actions' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( $this, 'untrash_beastfeedbacks_status_handler' ), 10, 3 );

		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'add_type_filter' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_source_filter' ) );

		add_action( 'pre_get_posts', array( $this, 'type_filter_result' ) );
		add_action( 'pre_get_posts', array( $this, 'source_filter_result' ) );
		add_filter( 'the_posts', array( $this, 'prime_parent_post_caches' ), 10, 2 );

		add_action( "save_post_{$this->post_type}", array( $this, 'clear_source_filter_cache' ), 10, 2 );
		add_action( 'deleted_post', array( $this, 'clear_source_filter_cache' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'clear_source_filter_cache' ), 10, 1 );
		add_action( 'untrashed_post', array( $this, 'clear_source_filter_cache' ), 10, 1 );
	}

	/**
	 * 一覧で表示するカラム
	 */
	public function manage_posts_columns() {
		return array(
			'cb'                      => '<input type="checkbox" />',
			'beastfeedbacks_source'   => __( 'Source', 'beastfeedbacks' ),
			'beastfeedbacks_type'     => __( 'Type', 'beastfeedbacks' ),
			'beastfeedbacks_date'     => __( 'Date', 'beastfeedbacks' ),
			'beastfeedbacks_response' => __( 'Response Data', 'beastfeedbacks' ),
		);
	}

	/**
	 * 一覧で表示する行
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function manage_posts_custom_column( $column_name, $post_id ) {
		$renderer = BeastFeedbacks_Admin_List_Table_Renderer::get_instance();
		switch ( $column_name ) {
			case 'beastfeedbacks_date':
				$renderer->render_date_column( $post_id );
				break;
			case 'beastfeedbacks_response':
				$renderer->render_response_column( $post_id );
				break;
			case 'beastfeedbacks_source':
				$renderer->render_source_column( $post_id );
				break;
			case 'beastfeedbacks_type':
				$renderer->render_type_column( $post_id );
				break;
		}
	}

	/**
	 * Render date column content.
	 *
	 * Performance optimization: Accepts $post_id and retrieves the post object directly via get_post_for_column()
	 * (which reuses $GLOBALS['post'] when available), avoiding the overhead of get_the_time() querying global context.
	 *
	 * @param int $post_id The current post ID.
	 * @deprecated Use BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_date_column() instead.
	 */
	public function render_date_column( $post_id ) {
		BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_date_column( $post_id );
	}

	/**
	 * Retrieve WP_Post object for column rendering, utilizing global $post when available.
	 *
	 * @param int $post_id The post ID.
	 * @return WP_Post|null
	 * @deprecated Use BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->get_post_for_column() instead.
	 */
	public function get_post_for_column( $post_id ) {
		return BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->get_post_for_column( $post_id );
	}

	/**
	 * Render response column content.
	 *
	 * @param int $post_id The current post ID.
	 * @deprecated Use BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_response_column() instead.
	 */
	public function render_response_column( $post_id ) {
		BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_response_column( $post_id );
	}

	/**
	 * Render source column content.
	 *
	 * @param int $post_id The current post ID.
	 * @deprecated Use BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_source_column() instead.
	 */
	public function render_source_column( $post_id ) {
		BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_source_column( $post_id );
	}

	/**
	 * Render type column content.
	 *
	 * @param int $post_id The current post ID.
	 * @deprecated Use BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_type_column() instead.
	 */
	public function render_type_column( $post_id ) {
		BeastFeedbacks_Admin_List_Table_Renderer::get_instance()->render_type_column( $post_id );
	}

	/**
	 * Add actions to beastfeedbacks response rows in WP Admin.
	 *
	 * @param string[] $actions Default actions.
	 * @return string[]
	 */
	public function manage_post_row_actions( $actions ) {
		global $post;

		if ( 'beastfeedbacks' !== $post->post_type ) {
			return $actions;
		}

		if ( 'publish' !== $post->post_status ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['edit'] );
		unset( $actions['view'] );

		return $actions;
	}

	/**
	 * Method untrash_beastfeedbacks_status_handler
	 * Wp_untrash_post filter handler.
	 *
	 * @param string $current_status   The status to be set.
	 * @param int    $post_id          The post ID.
	 * @param string $previous_status  The previous status.
	 */
	public function untrash_beastfeedbacks_status_handler( $current_status, $post_id, $previous_status ) {
		$post = get_post( $post_id );
		if ( $post && 'beastfeedbacks' === $post->post_type ) {
			if ( in_array( $previous_status, array( 'publish' ), true ) ) {
				return $previous_status;
			}
			return 'publish';
		}
		return $current_status;
	}

	/**
	 * Verify admin filter nonce from request data.
	 *
	 * Memory context reminder: Raw nonce input should be unslashed without pre-sanitization before wp_verify_nonce().
	 *
	 * @return bool True if filter nonce or CSV export nonce is verified, false otherwise.
	 */
	public function is_filter_nonce_verified() {
		if ( isset( $_REQUEST['_beastfeedbacks_nonce'] ) && is_scalar( $_REQUEST['_beastfeedbacks_nonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_beastfeedbacks_nonce'] ), 'beastfeedbacks_filter' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return true;
		}

		if ( isset( $_REQUEST['_wpnonce'] ) && is_scalar( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'beastfeedbacks_csv_export' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return true;
		}

		return false;
	}

	/**
	 * Add a post filter dropdown at the top of the admin page.
	 *
	 * @return void
	 */
	public function add_type_filter() {
		$screen = get_current_screen();

		if ( ! $screen || ! isset( $screen->id ) || 'edit-beastfeedbacks' !== $screen->id ) {
			return;
		}

		$selected_type = $this->is_filter_nonce_verified() && isset( $_GET['beastfeedbacks_type'] ) && is_scalar( $_GET['beastfeedbacks_type'] ) ? sanitize_key( wp_unslash( $_GET['beastfeedbacks_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $selected_type, BeastFeedbacks_Block::TYPES, true ) ) {
			$selected_type = '';
		}

		wp_nonce_field( 'beastfeedbacks_filter', '_beastfeedbacks_nonce' );
		?>
		<select name="beastfeedbacks_type">
			<option value=""><?php esc_html_e( 'All Types', 'beastfeedbacks' ); ?></option>
			<?php foreach ( BeastFeedbacks_Block::TYPES as $select_type ) : ?>
				<option value="<?php echo esc_html( $select_type ); ?>"
					<?php if ( $selected_type === $select_type ) : ?>
					selected
					<?php endif; ?>>
					<?php echo esc_html( $select_type ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Add a post filter dropdown at the top of the admin page.
	 *
	 * @return void
	 */
	public function add_source_filter() {
		$screen = get_current_screen();

		if ( ! $screen || ! isset( $screen->id ) || 'edit-beastfeedbacks' !== $screen->id ) {
			return;
		}

		$selected_parent_id = $this->is_filter_nonce_verified() && isset( $_GET['beastfeedbacks_parent_id'] ) && is_scalar( $_GET['beastfeedbacks_parent_id'] ) ? absint( wp_unslash( $_GET['beastfeedbacks_parent_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$cache_key   = 'source_filter_parent_ids';
		$cache_group = 'beastfeedbacks';

		$raw_parent_ids = wp_cache_get( $cache_key, $cache_group );

		if ( false === $raw_parent_ids ) {
			global $wpdb;

			// Performance optimization: Direct $wpdb->get_col() prepared query bypasses WP_Query instantiation,
			// query parsing, filter hook executions, and redundant internal post caching overhead.
			// Grouping and ordering by MAX(post_date) DESC preserves option ordering based on recent feedback activity.
			$results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_parent FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND post_parent > 0 GROUP BY post_parent ORDER BY MAX(post_date) DESC, MAX(ID) DESC",
					$this->post_type
				)
			);

			$raw_parent_ids = ! empty( $results )
				? array_values( array_unique( array_map( 'strval', $results ) ) )
				: array();

			wp_cache_set( $cache_key, $raw_parent_ids, $cache_group );
		}

		$parent_ids = ! empty( $raw_parent_ids ) ? array_values( array_filter( array_map( 'absint', $raw_parent_ids ) ) ) : array();

		if ( ! empty( $parent_ids ) ) {
			_prime_post_caches( $parent_ids );
		}

		$admin = BeastFeedbacks_Admin::get_instance();
		?>
		<select name="beastfeedbacks_parent_id">
			<option value=""><?php esc_html_e( 'All Sources', 'beastfeedbacks' ); ?></option>
			<?php foreach ( $parent_ids as $parent_id ) : ?>
				<?php
				$permalink_data = $admin->get_parent_permalink_data( $parent_id );
				$select_source  = $permalink_data['path'];
				?>
				<option value="<?php echo esc_html( $parent_id ); ?>"
					<?php if ( $selected_parent_id === $parent_id ) : ?>
					selected
					<?php endif; ?>>
					<?php echo esc_html( $select_source ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Clear parent ids cache for source filter when a feedback post is created, updated, or deleted.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	public function clear_source_filter_cache( $post_id = 0, $post = null ) {
		if ( $post instanceof WP_Post ) {
			if ( $post->post_type !== $this->post_type ) {
				return;
			}
		} elseif ( $post_id ) {
			if ( get_post_type( $post_id ) !== $this->post_type ) {
				return;
			}
		}

		wp_cache_delete( 'source_filter_parent_ids', 'beastfeedbacks' );
	}

	/**
	 * Type フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 */
	public function type_filter_result( $query ) {
		$selected_type = $this->is_filter_nonce_verified() && isset( $_REQUEST['beastfeedbacks_type'] ) && is_scalar( $_REQUEST['beastfeedbacks_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['beastfeedbacks_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $selected_type || ! in_array( $selected_type, BeastFeedbacks_Block::TYPES, true ) || ! isset( $query->query_vars['post_type'] ) || 'beastfeedbacks' !== $query->query_vars['post_type'] ) {
			return;
		}

		$meta_query = array(
			array(
				'key'   => 'beastfeedbacks_type',
				'value' => $selected_type,
			),
		);

		$old_meta_query = $query->get( 'meta_query' );
		if ( $old_meta_query ) {
			$meta_query[] = $old_meta_query;
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Prime parent post and post meta caches to prevent N+1 queries in admin list table.
	 *
	 * @param array    $posts Array of WP_Post objects.
	 * @param WP_Query $query WP_Query instance.
	 * @return array
	 */
	public function prime_parent_post_caches( $posts, $query ) {
		if ( ! is_admin() ) {
			return $posts;
		}

		if ( empty( $posts ) || ! is_array( $posts ) ) {
			return $posts;
		}

		$post_type = isset( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : '';
		if ( is_array( $post_type ) ) {
			if ( ! in_array( $this->post_type, $post_type, true ) ) {
				return $posts;
			}
		} elseif ( $this->post_type !== $post_type ) {
			return $posts;
		}

		$post_ids = array_values( array_filter( array_map( 'absint', wp_list_pluck( $posts, 'ID' ) ) ) );
		if ( ! empty( $post_ids ) ) {
			update_postmeta_cache( $post_ids );
		}

		$parent_ids = array_values( array_unique( array_filter( array_map( 'absint', wp_list_pluck( $posts, 'post_parent' ) ) ) ) );
		if ( ! empty( $parent_ids ) ) {
			_prime_post_caches( $parent_ids );
		}

		return $posts;
	}

	/**
	 * Source フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 */
	public function source_filter_result( $query ) {
		$selected_parent_id = $this->is_filter_nonce_verified() && isset( $_REQUEST['beastfeedbacks_parent_id'] ) && is_scalar( $_REQUEST['beastfeedbacks_parent_id'] ) ? absint( wp_unslash( $_REQUEST['beastfeedbacks_parent_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $selected_parent_id || empty( $query->query_vars['post_type'] ) || 'beastfeedbacks' !== $query->query_vars['post_type'] ) {
			return;
		}

		if ( isset( $query->query_vars['fields'] ) && 'id=>parent' === $query->query_vars['fields'] ) {
			return;
		}

		$query->query_vars['post_parent'] = $selected_parent_id;
	}
}
