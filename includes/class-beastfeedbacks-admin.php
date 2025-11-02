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
		$list = array(
			'beastfeedbacks_source',
			'beastfeedbacks_type',
			'beastfeedbacks_date',
			'beastfeedbacks_response',
		);

		if ( ! in_array( $column_name, $list, true ) ) {
			return;
		}

		switch ( $column_name ) {
			case 'beastfeedbacks_date':
				echo esc_html( date_i18n( 'Y/m/d', get_the_time( 'U' ) ) );
				return;
			case 'beastfeedbacks_response':
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
				return;
			case 'beastfeedbacks_source':
				$post = get_post( $post_id );
				if ( ! isset( $post->post_parent ) ) {
					return;
				}

				$form_url   = get_permalink( $post->post_parent );
				$parsed_url = wp_parse_url( $form_url );

				printf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( $form_url ),
					esc_html( $parsed_url['path'] )
				);
				return;
			case 'beastfeedbacks_type':
				$meta = get_post_meta( $post_id, 'beastfeedbacks_type', true );
				echo esc_html( $meta );
				return;
		}
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

		if ( 'edit-beastfeedbacks' !== $screen->id ) {
			return;
		}

		$selected_type = isset( $_GET['beastfeedbacks_type'] ) ? sanitize_key( $_GET['beastfeedbacks_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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

		if ( 'edit-beastfeedbacks' !== $screen->id ) {
			return;
		}

		$selected_parent_id = intval( isset( $_GET['beastfeedbacks_parent_id'] ) ? sanitize_key( $_GET['beastfeedbacks_parent_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = array(
			'fields'           => 'id=>parent',
			'posts_per_page'   => 100000, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'post_type'        => 'beastfeedbacks',
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);

		$posts      = get_posts( $args );
		$parent_ids = array_values( array_unique( array_values( $posts ) ) );

		?>
		<select name="beastfeedbacks_parent_id">
			<option value=""><?php esc_html_e( 'All Sources', 'beastfeedbacks' ); ?></option>
			<?php foreach ( $parent_ids as $parent_id ) : ?>
				<?php
				$parent_url    = get_permalink( $parent_id );
				$parsed_url    = wp_parse_url( $parent_url );
				$select_source = esc_html( $parsed_url['path'] );
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
		$selected_type = isset( $_GET['beastfeedbacks_type'] ) ? sanitize_key( $_GET['beastfeedbacks_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
	 * Source フィルターの表示に対応
	 *
	 * @param WP_Query $query Current query.
	 *
	 * @return void
	 */
	public function source_filter_result( $query ) {
		$selected_parent_id = intval( isset( $_GET['beastfeedbacks_parent_id'] ) ? sanitize_key( $_GET['beastfeedbacks_parent_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $selected_parent_id || 'beastfeedbacks' !== $query->query_vars['post_type'] ) {
			return;
		}

		if ( 'id=>parent' === $query->query_vars['fields'] ) {
			return;
		}

		$query->query_vars['post_parent'] = $selected_parent_id;
	}

	public function add_export_button() {
		$screen = get_current_screen();
		if ( 'edit-beastfeedbacks' !== $screen->id ) {
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

		// NOTE: POST情報にフィルター設定を載せて検索する.
		$args = array(
			'posts_per_page'   => -1,
			'post_type'        => 'beastfeedbacks',
			'post_status'      => array( 'publish' ),
			'order'            => 'ASC',
			'suppress_filters' => false,
			'date_query'       => array(),
		);

		$posts      = get_posts( $args );
		$post_datas = array();
		foreach ( $posts as $post ) {
			$id = $post->ID;

			$source = '';
			if ( $post->post_parent ) {
				$form_url   = get_permalink( $post->post_parent );
				$parsed_url = wp_parse_url( $form_url );
				$source     = esc_html( $parsed_url['path'] );
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

		$filename = sprintf(
			'beastfeedbacks-%s.csv',
			gmdate( 'Y-m-d_H:i' )
		);

		$fields = array_keys( $post_datas );

		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Content-Type: text/csv; charset=utf-8' );

		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, $fields );
		foreach ( $posts as $post ) {
			$current_row = array();

			foreach ( $fields as $single_field_name ) {
				$current_row[] = $this->esc_csv(
					$post_datas[ $single_field_name ][ $post->ID ]
				);
			}
			fputcsv( $output, $current_row );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit();
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
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $field, 0, 1 ), $active_content_triggers, true ) ) {
			$field = "'" . $field;
		}

		return $field;
	}
}
