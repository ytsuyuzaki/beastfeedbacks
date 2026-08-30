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

		add_filter( 'post_row_actions', array( $this, 'manage_post_row_actions' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( $this, 'untrash_beastfeedbacks_status_handler' ), 10, 3 );

		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'add_type_filter' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_source_filter' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_export_button' ) );

		add_action( 'pre_get_posts', array( $this, 'type_filter_result' ) );
		add_action( 'pre_get_posts', array( $this, 'source_filter_result' ) );
		add_filter( 'the_posts', array( $this, 'prime_parent_post_caches' ), 10, 2 );

		add_action( "wp_ajax_{$this->export_action_name}", array( $this, 'download_csv' ) );
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
		switch ( $column_name ) {
			case 'beastfeedbacks_date':
				$this->render_date_column( $post_id );
				break;
			case 'beastfeedbacks_response':
				$this->render_response_column( $post_id );
				break;
			case 'beastfeedbacks_source':
				$this->render_source_column( $post_id );
				break;
			case 'beastfeedbacks_type':
				$this->render_type_column( $post_id );
				break;
		}
	}

	/**
	 * Render date column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	private function render_date_column( $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		echo esc_html( date_i18n( 'Y/m/d', get_the_time( 'U' ) ) );
	}

	/**
	 * Render response column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	private function render_response_column( $post_id ) {
		$post    = get_post( $post_id );
		$content = json_decode( $post->post_content, true );
		if ( ! is_array( $content ) ) {
			return;
		}

		$type        = isset( $content['type'] )
			? $content['type']
			: '';
		$post_params = isset( $content['post_params'] )
			? $content['post_params']
			: array();
		?>
		<table>
			<tbody>
				<?php if ( 'vote' === $type ) : ?>
					<tr>
						<td><?php echo esc_html_e( 'Select', 'beastfeedbacks' ); ?></td>
						<td><?php echo esc_html( $post_params['selected'] ); ?></td>
					</tr>
				<?php elseif ( 'survey' === $type ) : ?>
					<?php foreach ( $post_params as $key => $value ) : ?>
						<tr>
							<td><?php echo esc_html( $key ); ?></td>
							<td>
								<?php if ( is_array( $value ) ) : ?>
									<?php foreach ( $value as $v ) : ?>
										<?php echo esc_html( $v ); ?><br />
									<?php endforeach; ?>
								<?php else : ?>
									<?php echo esc_html( $value ); ?>
								<?php endif ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif ?>
			</tbody>
		</table>
		<table>
			<tbody>
				<hr />
				<?php if ( isset( $content['ip_address'] ) ) : ?>
					<tr>
						<td>IP_Address</td>
						<td><?php echo esc_html( $content['ip_address'] ); ?></td>
					</tr>
				<?php endif ?>
				<?php if ( isset( $content['user_agent'] ) ) : ?>
					<tr>
						<td>UserAgent</td>
						<td><?php echo esc_html( $content['user_agent'] ); ?></td>
					</tr>
				<?php endif ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Retrieve and cache permalink URL and parsed path for a given parent post ID.
	 *
	 * @param int $parent_id Parent post ID.
	 * @return array Associative array containing 'url' and 'path'.
	 */
	public function get_parent_permalink_data( $parent_id ) {
		static $cache = array();

		if ( ! isset( $cache[ $parent_id ] ) ) {
			$form_url            = get_permalink( $parent_id );
			$parsed_url          = wp_parse_url( $form_url );
			$cache[ $parent_id ] = array(
				'url'  => $form_url,
				'path' => esc_html( isset( $parsed_url['path'] ) ? $parsed_url['path'] : '' ),
			);
		}

		return $cache[ $parent_id ];
	}

	/**
	 * Render source column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	private function render_source_column( $post_id ) {
		$post = get_post( $post_id );
		if ( ! isset( $post->post_parent ) || ! $post->post_parent ) {
			return;
		}

		$permalink_data = $this->get_parent_permalink_data( $post->post_parent );

		printf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $permalink_data['url'] ),
			$permalink_data['path'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Render type column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	private function render_type_column( $post_id ) {
		$meta = get_post_meta( $post_id, 'beastfeedbacks_type', true );
		echo esc_html( $meta );
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
	 * wp_untrash_post filter handler.
	 *
	 * @param string $current_status   The status to be set.
	 * @param int    $post_id          The post ID.
	 * @param string $previous_status  The previous status.
	 */
	public function untrash_beastfeedbacks_status_handler( $current_status, $post_id, $previous_status ) {
		$post = get_post( $post_id );
		if ( 'beastfeedbacks' === $post->post_type ) {
			if ( in_array( $previous_status, array( 'publish' ), true ) ) {
				return $previous_status;
			}
			return 'publish';
		}
		return $current_status;
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

		$nonce_verified = isset( $_GET['_beastfeedbacks_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_beastfeedbacks_nonce'] ) ), 'beastfeedbacks_filter' );
		$selected_type  = $nonce_verified && isset( $_GET['beastfeedbacks_type'] ) ? sanitize_key( wp_unslash( $_GET['beastfeedbacks_type'] ) ) : '';

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

		$nonce_verified     = isset( $_GET['_beastfeedbacks_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_beastfeedbacks_nonce'] ) ), 'beastfeedbacks_filter' );
		$selected_parent_id = intval( $nonce_verified && isset( $_GET['beastfeedbacks_parent_id'] ) ? sanitize_key( wp_unslash( $_GET['beastfeedbacks_parent_id'] ) ) : 0 );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$raw_parent_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				$this->post_type,
				'publish'
			)
		);

		$parent_ids = ! empty( $raw_parent_ids ) ? array_values( array_filter( array_map( 'absint', $raw_parent_ids ) ) ) : array();

		if ( ! empty( $parent_ids ) ) {
			_prime_post_caches( $parent_ids );
		}

		?>
		<select name="beastfeedbacks_parent_id">
			<option value=""><?php esc_html_e( 'All Sources', 'beastfeedbacks' ); ?></option>
			<?php foreach ( $parent_ids as $parent_id ) : ?>
				<?php
				$permalink_data = $this->get_parent_permalink_data( $parent_id );
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
	 * Type フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 */
	public function type_filter_result( $query ) {
		$nonce_verified = ( isset( $_REQUEST['_beastfeedbacks_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_beastfeedbacks_nonce'] ) ), 'beastfeedbacks_filter' ) )
			|| ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'beastfeedbacks_csv_export' ) );
		$selected_type  = $nonce_verified && isset( $_REQUEST['beastfeedbacks_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['beastfeedbacks_type'] ) ) : '';

		if ( ! $selected_type || 'beastfeedbacks' !== $query->query_vars['post_type'] ) {
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
	 * Prime parent post caches to prevent N+1 queries in admin list table.
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
		$nonce_verified     = ( isset( $_REQUEST['_beastfeedbacks_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_beastfeedbacks_nonce'] ) ), 'beastfeedbacks_filter' ) )
			|| ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'beastfeedbacks_csv_export' ) );
		$selected_parent_id = intval( $nonce_verified && isset( $_REQUEST['beastfeedbacks_parent_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['beastfeedbacks_parent_id'] ) ) : 0 );

		if ( ! $selected_parent_id || 'beastfeedbacks' !== $query->query_vars['post_type'] ) {
			return;
		}

		if ( 'id=>parent' === $query->query_vars['fields'] ) {
			return;
		}

		$query->query_vars['post_parent'] = $selected_parent_id;
	}

	/**
	 * Add export button for the response list screen.
	 *
	 * @return void
	 */
	public function add_export_button() {
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
		check_admin_referer( 'beastfeedbacks_csv_export' );

		// Security: Verify user capability to prevent unauthorized data export.
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'beastfeedbacks' ), 403 );
		}

		$filename = sprintf(
			'beastfeedbacks-%s.csv',
			gmdate( 'Y-m-d_H:i' )
		);

		$this->stream_csv( $filename );
		wp_die();
	}

	/**
	 * Stream CSV export directly to output in chunks to minimize memory usage.
	 *
	 * @param string $filename CSV file name.
	 * @return void
	 */
	public function stream_csv( $filename ) {
		$args = array(
			'posts_per_page'         => -1,
			'post_type'              => 'beastfeedbacks',
			'post_status'            => array( 'publish' ),
			'order'                  => 'ASC',
			'suppress_filters'       => false,
			'date_query'             => array(),
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		$post_ids = get_posts( $args );

		if ( ! headers_sent() ) {
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			header( 'Content-Type: text/csv; charset=utf-8' );
		}

		$output = fopen( 'php://output', 'w' );

		if ( empty( $post_ids ) ) {
			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return;
		}

		$chunk_size = 500;
		$chunks     = array_chunk( $post_ids, $chunk_size );

		// Pass 1: Collect all unique field keys across all posts.
		$fields     = array( 'source', 'date', 'type', 'ip_address', 'user_agent' );
		$fields_map = array_fill_keys( $fields, true );

		foreach ( $chunks as $chunk ) {
			$posts = get_posts(
				array(
					'post_type'              => 'beastfeedbacks',
					'post__in'               => $chunk,
					'orderby'                => 'post__in',
					'posts_per_page'         => count( $chunk ),
					'suppress_filters'       => false,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				)
			);

			foreach ( $posts as $post ) {
				$content = json_decode( $post->post_content, true );
				if ( is_array( $content ) && isset( $content['post_params'] ) && is_array( $content['post_params'] ) ) {
					foreach ( $content['post_params'] as $key => $val ) {
						if ( ! isset( $fields_map[ $key ] ) ) {
							$fields_map[ $key ] = true;
							$fields[]           = $key;
						}
					}
				}
			}

			foreach ( $chunk as $id ) {
				clean_post_cache( $id );
			}
		}

		// Output CSV headers.
		$escaped_fields = array_map( array( $this, 'esc_csv' ), $fields );
		fputcsv( $output, $escaped_fields );

		// Pass 2: Stream rows.
		foreach ( $chunks as $chunk ) {
			$posts = get_posts(
				array(
					'post_type'              => 'beastfeedbacks',
					'post__in'               => $chunk,
					'orderby'                => 'post__in',
					'posts_per_page'         => count( $chunk ),
					'suppress_filters'       => false,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				)
			);

			$parent_ids = array_values( array_filter( array_map( 'intval', array_unique( wp_list_pluck( $posts, 'post_parent' ) ) ) ) );
			if ( ! empty( $parent_ids ) ) {
				_prime_post_caches( $parent_ids );
			}

			foreach ( $posts as $post ) {
				$source = '';
				if ( $post->post_parent ) {
					$permalink_data = $this->get_parent_permalink_data( $post->post_parent );
					$source         = $permalink_data['path'];
				}

				$content = json_decode( $post->post_content, true );
				if ( ! is_array( $content ) ) {
					$content = array();
				}

				$type        = isset( $content['type'] ) ? $content['type'] : '';
				$post_params = isset( $content['post_params'] ) && is_array( $content['post_params'] )
					? $content['post_params']
					: array();

				$ip_address = isset( $content['ip_address'] ) ? $content['ip_address'] : '';
				$user_agent = isset( $content['user_agent'] ) ? $content['user_agent'] : '';

				$row_data = array(
					'source'     => $source,
					'date'       => $post->post_date,
					'type'       => $type,
					'ip_address' => $ip_address,
					'user_agent' => $user_agent,
				);

				$row_data = array_merge( $row_data, $post_params );

				$current_row = array();
				foreach ( $fields as $single_field_name ) {
					$value = isset( $row_data[ $single_field_name ] ) ? $row_data[ $single_field_name ] : '';
					if ( is_array( $value ) ) {
						$value = implode( ',', $value );
					}
					$current_row[] = $this->esc_csv( $value );
				}
				fputcsv( $output, $current_row );
			}

			foreach ( $chunk as $id ) {
				clean_post_cache( $id );
			}
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Retrieve posts for CSV export
	 *
	 * @return array List of WP_Post objects.
	 */
	public function get_export_posts() {
		// NOTE: POST情報にフィルター設定を載せて検索する.
		$args = array(
			'posts_per_page'         => -1,
			'post_type'              => 'beastfeedbacks',
			'post_status'            => array( 'publish' ),
			'order'                  => 'ASC',
			'suppress_filters'       => false,
			'date_query'             => array(),
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		return get_posts( $args );
	}

	/**
	 * Extract CSV data and fields from feedback posts
	 *
	 * @param array $posts List of WP_Post objects.
	 * @return array Map of field keys to associative array of [ post_id => data_value ].
	 */
	public function get_csv_data( array $posts ) {
		$post_datas = array();

		$parent_ids = array_values( array_filter( array_map( 'intval', array_unique( wp_list_pluck( $posts, 'post_parent' ) ) ) ) );
		if ( ! empty( $parent_ids ) ) {
			_prime_post_caches( $parent_ids );
		}

		foreach ( $posts as $post ) {
			$id = $post->ID;

			$source = '';
			if ( $post->post_parent ) {
				$permalink_data = $this->get_parent_permalink_data( $post->post_parent );
				$source         = $permalink_data['path'];
			}

			$content = json_decode( $post->post_content, true );
			if ( ! is_array( $content ) ) {
				$content = array();
			}

			$type        = isset( $content['type'] )
				? $content['type']
				: '';
			$post_params = isset( $content['post_params'] )
				? $content['post_params']
				: array();

			$ip_address = isset( $content['ip_address'] ) ? $content['ip_address'] : '';
			$user_agent = isset( $content['user_agent'] ) ? $content['user_agent'] : '';

			$add_data = array(
				'source'     => $source,
				'date'       => $post->post_date,
				'type'       => $type,
				'ip_address' => $ip_address,
				'user_agent' => $user_agent,
			);

			$add_data = array_merge( $add_data, $post_params );

			foreach ( $add_data as $key => $value ) {
				$data = $value;
				if ( is_array( $value ) ) {
					$data = implode( ',', $value );
				}
				if ( ! isset( $post_datas[ $key ] ) ) {
					$post_datas[ $key ] = array();
				}
				$post_datas[ $key ][ $id ] = $data;
			}
		}

		return $post_datas;
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
		$fields = array_keys( $post_datas );

		if ( ! headers_sent() ) {
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			header( 'Content-Type: text/csv; charset=utf-8' );
		}

		$output = fopen( 'php://output', 'w' );

		// Security: Escape CSV header column names against formula injection.
		$escaped_fields = array_map( array( $this, 'esc_csv' ), $fields );
		fputcsv( $output, $escaped_fields );

		foreach ( $posts as $post ) {
			$current_row = array();

			foreach ( $fields as $single_field_name ) {
				$value         = isset( $post_datas[ $single_field_name ][ $post->ID ] )
					? $post_datas[ $single_field_name ][ $post->ID ]
					: '';
				$current_row[] = $this->esc_csv( $value );
			}
			fputcsv( $output, $current_row );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility for phishing attacks and
	 * disclosure of sensitive information.
	 *
	 * Additionally, Excel exposes the ability to launch arbitrary commands through the DDE protocol.
	 *
	 * @see https://www.contextis.com/en/blog/comma-separated-vulnerabilities
	 *
	 * @param string $field - the CSV field.
	 *
	 * @return string
	 */
	public function esc_csv( $field ) {
		$active_content_triggers = array( '=', '+', '-', '@', '|', '%', "\t", "\r", "\n" );

		$string_field  = (string) $field;
		$trimmed_field = ltrim( $string_field, " \v\0" );

		if ( '' !== $string_field && ( in_array( mb_substr( $string_field, 0, 1 ), $active_content_triggers, true ) || ( '' !== $trimmed_field && in_array( mb_substr( $trimmed_field, 0, 1 ), $active_content_triggers, true ) ) ) ) {
			$field = "'" . $field;
		}

		return $field;
	}
}
