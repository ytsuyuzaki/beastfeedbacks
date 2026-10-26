<?php
/**
 * Data Extractor
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * Data Extractor class to separate data parsing/utility logic.
 */
class BeastFeedbacks_Data_Extractor {

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
	 * Extract structured content data from post_content JSON.
	 *
	 * @param string $post_content JSON string stored in post_content.
	 * @return array Associative array containing parsed JSON fields and key-presence booleans.
	 */
	public function extract_post_content_data( $post_content ) {
		static $cache = array();

		if ( isset( $cache[ $post_content ] ) ) {
			return $cache[ $post_content ];
		}

		// Performance optimization: Bounding in-memory static cache size to 100 items prevents unbounded
		// memory allocation during large batch operations (e.g. multi-thousand row CSV exports).
		// By evicting the oldest item sequentially, we prevent complete cache thrashing while retaining the bound.
		if ( count( $cache ) >= 100 ) {
			reset( $cache );
			unset( $cache[ key( $cache ) ] );
		}

		$content = json_decode( $post_content, true );
		if ( ! is_array( $content ) ) {
			$cache[ $post_content ] = array(
				'is_valid'       => false,
				'type'           => '',
				'post_params'    => array(),
				'ip_address'     => '',
				'user_agent'     => '',
				'has_ip_address' => false,
				'has_user_agent' => false,
			);
			return $cache[ $post_content ];
		}

		$type        = isset( $content['type'] ) ? $content['type'] : '';
		$post_params = isset( $content['post_params'] ) && is_array( $content['post_params'] )
			? $content['post_params']
			: array();

		$ip_address = isset( $content['ip_address'] ) ? $content['ip_address'] : '';
		$user_agent = isset( $content['user_agent'] ) ? $content['user_agent'] : '';

		$cache[ $post_content ] = array(
			'is_valid'       => true,
			'type'           => $type,
			'post_params'    => $post_params,
			'ip_address'     => $ip_address,
			'user_agent'     => $user_agent,
			'has_ip_address' => isset( $content['ip_address'] ),
			'has_user_agent' => isset( $content['user_agent'] ),
		);

		return $cache[ $post_content ];
	}

	/**
	 * Retrieve and cache permalink URL and parsed path for a given parent post ID.
	 *
	 * @param int $parent_id Parent post ID.
	 * @return array Associative array containing 'url' and 'path'.
	 */
	public function get_parent_permalink_data( $parent_id ) {
		static $cache = array();

		if ( isset( $cache[ $parent_id ] ) ) {
			return $cache[ $parent_id ];
		}

		// Performance optimization: Bounding in-memory static cache size to 100 items prevents unbounded
		// memory allocation during large batch operations (e.g. multi-thousand row CSV exports).
		// By evicting the oldest item sequentially (FIFO), we prevent complete cache thrashing while retaining the bound.
		if ( count( $cache ) >= 100 ) {
			reset( $cache );
			unset( $cache[ key( $cache ) ] );
		}

		$form_url            = get_permalink( $parent_id );
		$parsed_url          = wp_parse_url( $form_url );
		$cache[ $parent_id ] = array(
			'url'  => $form_url,
			'path' => isset( $parsed_url['path'] ) ? $parsed_url['path'] : '',
		);

		return $cache[ $parent_id ];
	}
}
