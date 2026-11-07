<?php
/**
 * フィードバックのソースリポジトリ
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.13
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * フィードバックのソースとなる親投稿に関するデータアクセス・キャッシュ制御を行うクラス
 */
class BeastFeedbacks_Source_Repository {

	/**
	 * Self class
	 *
	 * @var self|null
	 */
	private static $instance = null;

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
	 * フィードバックが紐づく親投稿のID一覧を取得する
	 *
	 * @return array 親投稿IDの配列
	 */
	public function get_parent_ids() {
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
					'beastfeedbacks'
				)
			);

			$raw_parent_ids = ! empty( $results )
				? array_values( array_unique( array_map( 'strval', $results ) ) )
				: array();

			wp_cache_set( $cache_key, $raw_parent_ids, $cache_group );
		}

		return ! empty( $raw_parent_ids ) ? array_values( array_filter( array_map( 'absint', $raw_parent_ids ) ) ) : array();
	}

	/**
	 * 親投稿IDのキャッシュをクリアする
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	public function clear_cache( $post_id = 0, $post = null ) {
		if ( $post instanceof WP_Post ) {
			if ( 'beastfeedbacks' !== $post->post_type ) {
				return;
			}
		} elseif ( $post_id ) {
			if ( 'beastfeedbacks' !== get_post_type( $post_id ) ) {
				return;
			}
		}

		wp_cache_delete( 'source_filter_parent_ids', 'beastfeedbacks' );
	}
}
