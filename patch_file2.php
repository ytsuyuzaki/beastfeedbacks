<?php
$content = file_get_contents('includes/class-beastfeedbacks-public.php');
$search = <<<'SEARCH'
	public function extract_post_params( array $post_data ) {
		$post_params = array();

		// Security: Enforce maximum parameter limit to prevent resource exhaustion via post parameter flooding.
		$max_params = (int) apply_filters( 'beastfeedbacks_max_post_params', 50 );

		foreach ( array_keys( $post_data ) as $post_key ) {
			if ( count( $post_params ) >= $max_params ) {
				break;
			}

			if ( in_array( $post_key, self::IGNORE_KEYS, true ) ) {
				continue;
			}
			if ( isset( $post_data[ $post_key ] ) ) {
				$sanitized_key = sanitize_text_field( wp_unslash( (string) $post_key ) );
				// Security: Truncate oversized keys to prevent DoS/storage bloat.
				$sanitized_key = mb_substr( $sanitized_key, 0, 100 );
				if ( '' === $sanitized_key ) {
					continue;
				}
				$post_value                    = $post_data[ $post_key ];
				$post_params[ $sanitized_key ] = $this->sanitize_param_value( $post_value );
			}
		}

		return $post_params;
	}

	/**
	 * Recursively sanitize parameter values with depth and length limits.
	 *
	 * Security: Prevents stack overflow, resource exhaustion, and key/value injection from deeply nested POST structures.
	 * Unslashing is performed inside bounded traversal so rejected deep/excess branches are never unslashed.
	 *
	 * @param mixed $value         The value to sanitize.
	 * @param int   $current_depth Current recursion depth.
	 * @param int   $max_depth     Maximum allowed recursion depth.
	 * @return mixed Sanitized string or array.
	 */
SEARCH;

$replace = <<<'REPLACE'
	public function extract_post_params( array $post_data ) {
		$post_params = array();

		// Security: Enforce maximum parameter limit to prevent resource exhaustion via post parameter flooding.
		$max_params = (int) apply_filters( 'beastfeedbacks_max_post_params', 50 );

		foreach ( array_keys( $post_data ) as $post_key ) {
			if ( count( $post_params ) >= $max_params ) {
				break;
			}

			if ( in_array( $post_key, self::IGNORE_KEYS, true ) ) {
				continue;
			}
			if ( isset( $post_data[ $post_key ] ) ) {
				$sanitized_key = (string) $post_key;
				// Security: Truncate oversized keys to prevent DoS/storage bloat.
				$sanitized_key = mb_substr( $sanitized_key, 0, 100 );
				$sanitized_key = sanitize_text_field( wp_unslash( $sanitized_key ) );
				if ( '' === $sanitized_key ) {
					continue;
				}
				$post_value                    = $post_data[ $post_key ];
				$post_params[ $sanitized_key ] = $this->sanitize_param_value( $post_value );
			}
		}

		return $post_params;
	}

	/**
	 * Sanitize parameter values with length limits.
	 *
	 * Security: Prevents resource exhaustion and key/value injection by rejecting complex array structures.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed Sanitized string or array.
	 */
REPLACE;

$new_content = str_replace($search, $replace, $content);
file_put_contents('includes/class-beastfeedbacks-public.php', $new_content);
