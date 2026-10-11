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
		add_action( 'post_updated', array( __CLASS__, 'on_post_updated' ), 10, 3 );
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

		global $wpdb;

		// Performance optimization: Direct $wpdb->get_var() prepared query bypasses WP_Query instantiation,
		// query parsing, filter hook executions, and double-querying (SQL_CALC_FOUND_ROWS) overhead.
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_parent = %d AND p.post_type = 'beastfeedbacks' AND p.post_status = 'publish' AND pm.meta_key = 'beastfeedbacks_type' AND pm.meta_value = 'like'",
				$post_id
			)
		);

		wp_cache_set( $cache_key, $count, $cache_group, DAY_IN_SECONDS );

		return $count;
	}

	/**
	 * Delete like count cache for a specific parent post ID.
	 *
	 * @param int $parent_id Parent post ID.
	 * @return void
	 */
	public static function clear_parent_like_count_cache( $parent_id ) {
		$parent_id = (int) $parent_id;
		if ( $parent_id > 0 ) {
			wp_cache_delete( 'like_count_' . $parent_id, 'beastfeedbacks' );
		}
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

		self::clear_parent_like_count_cache( $post->post_parent );
	}

	/**
	 * Clear like count cache when a feedback post is updated, handling parent reassignment.
	 *
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post_after  Post object after update.
	 * @param WP_Post $post_before Post object before update.
	 * @return void
	 */
	public static function on_post_updated( $post_id, $post_after, $post_before ) {
		$is_after_feedback  = $post_after instanceof WP_Post && 'beastfeedbacks' === $post_after->post_type;
		$is_before_feedback = $post_before instanceof WP_Post && 'beastfeedbacks' === $post_before->post_type;

		if ( ! $is_after_feedback && ! $is_before_feedback ) {
			return;
		}

		if ( $is_before_feedback && ! empty( $post_before->post_parent ) ) {
			self::clear_parent_like_count_cache( $post_before->post_parent );
		}

		if ( $is_after_feedback && ! empty( $post_after->post_parent ) ) {
			self::clear_parent_like_count_cache( $post_after->post_parent );
		}
	}
}
