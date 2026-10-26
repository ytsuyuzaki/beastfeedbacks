<?php
/**
 * 管理画面一覧テーブル表示の描画用クラス
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.7
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * 管理画面一覧テーブル表示の描画
 */
class BeastFeedbacks_Admin_List_Table_Renderer {

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
	 * Render date column content.
	 *
	 * Performance optimization: Accepts $post_id and retrieves the post object directly via get_post_for_column()
	 * (which reuses $GLOBALS['post'] when available), avoiding the overhead of get_the_time() querying global context.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function render_date_column( $post_id ) {
		$post = $this->get_post_for_column( $post_id );
		if ( ! $post ) {
			return;
		}
		echo esc_html( mysql2date( 'Y/m/d', $post->post_date ) );
	}

	/**
	 * Retrieve WP_Post object for column rendering, utilizing global $post when available.
	 *
	 * @param int $post_id The post ID.
	 * @return WP_Post|null
	 */
	public function get_post_for_column( $post_id ) {
		global $post;
		if ( isset( $post ) && $post instanceof WP_Post && (int) $post->ID === (int) $post_id ) {
			return $post;
		}
		return get_post( $post_id );
	}

	/**
	 * Render response column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function render_response_column( $post_id ) {
		$post = $this->get_post_for_column( $post_id );
		if ( ! $post ) {
			return;
		}

		$extractor    = BeastFeedbacks_Data_Extractor::get_instance();
		$content_data = $extractor->extract_post_content_data( $post->post_content );
		if ( ! $content_data['is_valid'] ) {
			return;
		}

		$type        = $content_data['type'];
		$post_params = $content_data['post_params'];
		?>
		<table>
			<tbody>
				<?php if ( 'vote' === $type ) : ?>
					<tr>
						<td><?php esc_html_e( 'Select', 'beastfeedbacks' ); ?></td>
						<td><?php echo esc_html( isset( $post_params['selected'] ) && is_scalar( $post_params['selected'] ) ? (string) $post_params['selected'] : '' ); ?></td>
					</tr>
				<?php elseif ( 'survey' === $type ) : ?>
					<?php foreach ( $post_params as $key => $value ) : ?>
						<tr>
							<td><?php echo esc_html( is_scalar( $key ) ? (string) $key : '' ); ?></td>
							<td>
								<?php if ( is_array( $value ) ) : ?>
									<?php foreach ( $value as $v ) : ?>
										<?php echo esc_html( is_scalar( $v ) ? (string) $v : '' ); ?><br />
									<?php endforeach; ?>
								<?php else : ?>
									<?php echo esc_html( is_scalar( $value ) ? (string) $value : '' ); ?>
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
				<?php if ( $content_data['has_ip_address'] ) : ?>
					<tr>
						<td>IP_Address</td>
						<td><?php echo esc_html( $content_data['ip_address'] ); ?></td>
					</tr>
				<?php endif ?>
				<?php if ( $content_data['has_user_agent'] ) : ?>
					<tr>
						<td>UserAgent</td>
						<td><?php echo esc_html( $content_data['user_agent'] ); ?></td>
					</tr>
				<?php endif ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render source column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function render_source_column( $post_id ) {
		$post = $this->get_post_for_column( $post_id );
		if ( ! $post || ! isset( $post->post_parent ) || ! $post->post_parent ) {
			return;
		}

		$extractor      = BeastFeedbacks_Data_Extractor::get_instance();
		$permalink_data = $extractor->get_parent_permalink_data( $post->post_parent );

		printf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $permalink_data['url'] ),
			esc_html( $permalink_data['path'] )
		);
	}

	/**
	 * Render type column content.
	 *
	 * @param int $post_id The current post ID.
	 */
	public function render_type_column( $post_id ) {
		$meta = get_post_meta( $post_id, 'beastfeedbacks_type', true );
		echo esc_html( $meta );
	}
}
