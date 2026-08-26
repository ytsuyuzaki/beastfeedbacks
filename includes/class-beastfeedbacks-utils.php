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
	 * Like数の取得
	 *
	 * @param integer $post_id Like登録に使用したpostを渡す.
	 * @return int
	 */
	public static function get_like_count( $post_id ) {
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
		return (int) $query->found_posts;
	}
}
