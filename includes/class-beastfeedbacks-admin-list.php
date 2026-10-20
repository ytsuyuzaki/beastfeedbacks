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
	 * @return bool True if filter nonce or CSV export nonce is verified, false otherwise.
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->is_filter_nonce_verified() instead.
	 */
	public function is_filter_nonce_verified() {
		return BeastFeedbacks_Admin_List_Filter::get_instance()->is_filter_nonce_verified();
	}

	/**
	 * Add a post filter dropdown at the top of the admin page.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->add_type_filter() instead.
	 */
	public function add_type_filter() {
		BeastFeedbacks_Admin_List_Filter::get_instance()->add_type_filter();
	}

	/**
	 * Add a post filter dropdown at the top of the admin page.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->add_source_filter() instead.
	 */
	public function add_source_filter() {
		BeastFeedbacks_Admin_List_Filter::get_instance()->add_source_filter();
	}

	/**
	 * Clear parent ids cache for source filter when a feedback post is created, updated, or deleted.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->clear_source_filter_cache() instead.
	 */
	public function clear_source_filter_cache( $post_id = 0, $post = null ) {
		BeastFeedbacks_Admin_List_Filter::get_instance()->clear_source_filter_cache( $post_id, $post );
	}

	/**
	 * Type フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->type_filter_result() instead.
	 */
	public function type_filter_result( $query ) {
		BeastFeedbacks_Admin_List_Filter::get_instance()->type_filter_result( $query );
	}

	/**
	 * Prime parent post and post meta caches to prevent N+1 queries in admin list table.
	 *
	 * @param array    $posts Array of WP_Post objects.
	 * @param WP_Query $query WP_Query instance.
	 * @return array
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->prime_parent_post_caches() instead.
	 */
	public function prime_parent_post_caches( $posts, $query ) {
		return BeastFeedbacks_Admin_List_Filter::get_instance()->prime_parent_post_caches( $posts, $query );
	}

	/**
	 * Source フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 * @deprecated Use BeastFeedbacks_Admin_List_Filter::get_instance()->source_filter_result() instead.
	 */
	public function source_filter_result( $query ) {
		BeastFeedbacks_Admin_List_Filter::get_instance()->source_filter_result( $query );
	}
}
