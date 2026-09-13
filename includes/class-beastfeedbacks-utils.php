<?php
/**
 * 共通ユーティリティクラス
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * ユーティリティ関数を提供するクラス
 */
class BeastFeedbacks_Utils {

	/**
	 * Register hooks for cache management.
	 */
	public static function init() {
		add_action( 'save_post_beastfeedbacks', array( __CLASS__, 'clear_like_count_cache' ), 10, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'clear_like_count_cache' ), 10, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'clear_like_count_cache' ), 10, 1 );
		add_action( 'untrashed_post', array( __CLASS__, 'clear_like_count_cache' ), 10, 1 );
	}

	/**
	 * Like数の取得
	 *
	 * Performance optimization: Caches like count in object cache to prevent repeated database
	 * queries on pages with Like blocks. Invalidated via clear_like_count_cache() when feedback is mutated.
	 *
	 * @param integer $post_id Like登録に使用したpostを渡す.
	 * @return int
	 */
	public static function get_like_count( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return 0;
		}

		$cache_key    = 'like_count_' . $post_id;
		$cache_group  = 'beastfeedbacks';
		$cached_count = wp_cache_get( $cache_key, $cache_group );

		if ( false !== $cached_count ) {
			return (int) $cached_count;
		}

		$args  = array(
			'post_type'              => 'beastfeedbacks',
			'post_parent'            => $post_id,
			'post_status'            => 'publish',
			'posts_per_page'         => 1, // Only need found_posts count; avoid retrieving full result set.
			'fields'                 => 'ids', // Only return IDs to prevent instantiation of full WP_Post objects.
			'no_found_rows'          => false, // Ensure total found posts calculation is enabled.
			'update_post_term_cache' => false, // Skip taxonomy term cache query for count operation.
			'update_post_meta_cache' => false, // Skip postmeta cache query for count operation.
			'meta_key'               => 'beastfeedbacks_type', // NOTE: クエリ効率化.
			'meta_value'             => 'like',
		);
		$query = new WP_Query( $args );
		$count = (int) $query->found_posts;

		wp_cache_set( $cache_key, $count, $cache_group );

		return $count;
	}

	/**
	 * Clear like count cache when a feedback post is created, updated, or deleted.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post object.
	 * @return void
	 */
	public static function clear_like_count_cache( $post_id, $post = null ) {
		$post = $post instanceof WP_Post ? $post : get_post( $post_id );
		if ( ! $post || 'beastfeedbacks' !== $post->post_type || ! $post->post_parent ) {
			return;
		}

		wp_cache_delete( 'like_count_' . (int) $post->post_parent, 'beastfeedbacks' );
	}
}
