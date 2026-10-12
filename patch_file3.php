<?php
$content = file_get_contents('includes/class-beastfeedbacks-public.php');
$search = <<<'SEARCH'
	private function sanitize_param_value( $value, int $current_depth = 1, int $max_depth = 2 ) {
		if ( $current_depth > $max_depth ) {
			return '';
		}

		if ( is_array( $value ) ) {
			// Security: Reject complex array structures entirely to prevent deep parsing DoS.
			foreach ( $value as $item ) {
				if ( is_array( $item ) || is_object( $item ) ) {
					return '';
				}
			}

			$sanitized_array = array();
			$max_items       = 50;
			$item_count      = 0;

			foreach ( $value as $key => $item ) {
				if ( $item_count >= $max_items ) {
					break;
				}

				$sanitized_item_key = (string) $key;
				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );
				$sanitized_item_key = sanitize_text_field( wp_unslash( $sanitized_item_key ) );
				if ( '' === $sanitized_item_key ) {
					continue;
				}

				$sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item, $current_depth + 1, $max_depth );
				++$item_count;
			}

			return $sanitized_array;
		}
SEARCH;

$replace = <<<'REPLACE'
	private function sanitize_param_value( $value ) {
		if ( is_array( $value ) ) {
			// Security: Reject complex array structures entirely to prevent deep parsing DoS.
			foreach ( $value as $item ) {
				if ( is_array( $item ) || is_object( $item ) ) {
					return '';
				}
			}

			$sanitized_array = array();
			$max_items       = 50;
			$item_count      = 0;

			foreach ( $value as $key => $item ) {
				if ( $item_count >= $max_items ) {
					break;
				}

				$sanitized_item_key = (string) $key;
				$sanitized_item_key = mb_substr( $sanitized_item_key, 0, 100 );
				$sanitized_item_key = sanitize_text_field( wp_unslash( $sanitized_item_key ) );
				if ( '' === $sanitized_item_key ) {
					continue;
				}

				$sanitized_array[ $sanitized_item_key ] = $this->sanitize_param_value( $item );
				++$item_count;
			}

			return $sanitized_array;
		}
REPLACE;

$new_content = str_replace($search, $replace, $content);
file_put_contents('includes/class-beastfeedbacks-public.php', $new_content);
