<?php
/**
 * Feedback Formatter
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.11
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * フィードバックデータの変換・整形ロジック
 */
class BeastFeedbacks_Feedback_Formatter {

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
	 * Prime parent post caches for a group of posts.
	 *
	 * @param array $posts List of WP_Post objects.
	 * @return void
	 */
	public function prime_parent_caches( array $posts ) {
		$parent_ids = array_values( array_filter( array_map( 'intval', array_unique( wp_list_pluck( $posts, 'post_parent' ) ) ) ) );
		if ( ! empty( $parent_ids ) ) {
			_prime_post_caches( $parent_ids );
		}
	}

	/**
	 * Format a single feedback post into a flat associative array for CSV export.
	 *
	 * @param WP_Post $post The feedback post object.
	 * @return array Formatted data array.
	 */
	public function format_post_data( $post ) {
		$extractor = BeastFeedbacks_Data_Extractor::get_instance();

		$source = '';
		if ( $post->post_parent ) {
			$permalink_data = $extractor->get_parent_permalink_data( $post->post_parent );
			$source         = $permalink_data['path'];
		}

		$content_data = $extractor->extract_post_content_data( $post->post_content );

		$row_data = array(
			'source'     => $source,
			'date'       => $post->post_date,
			'type'       => $content_data['type'],
			'ip_address' => $content_data['ip_address'],
			'user_agent' => $content_data['user_agent'],
		);

		foreach ( $content_data['post_params'] as $key => $value ) {
			if ( isset( $row_data[ $key ] ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$row_data[ $key ] = implode( ',', $value );
			} else {
				$row_data[ $key ] = $value;
			}
		}

		return $row_data;
	}
}
