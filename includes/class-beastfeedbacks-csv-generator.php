<?php
/**
 * CSV Generator
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.9
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * CSVデータ生成ロジック
 */
class BeastFeedbacks_CSV_Generator {

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
	 * Process post chunks and write row data into temporary stream buffer while collecting dynamic field names.
	 *
	 * @param array    $chunks      Array of post ID chunks.
	 * @param resource $temp_stream Temporary stream handle.
	 * @return array|false Array of field names on success, false on write error.
	 */
	private function process_csv_post_chunks( array $chunks, $temp_stream ) {
		$fields     = array( 'source', 'date', 'type', 'ip_address', 'user_agent' );
		$fields_map = array_fill_keys( $fields, true );
		$extractor  = BeastFeedbacks_Data_Extractor::get_instance();

		foreach ( $chunks as $chunk ) {
			_prime_post_caches( $chunk, false, false );
			$posts = array();
			foreach ( $chunk as $post_id ) {
				$post = get_post( $post_id );
				if ( $post ) {
					$posts[] = $post;
				}
			}

			$parent_ids = array_values( array_filter( array_map( 'intval', array_unique( wp_list_pluck( $posts, 'post_parent' ) ) ) ) );
			if ( ! empty( $parent_ids ) ) {
				_prime_post_caches( $parent_ids );
			}

			foreach ( $posts as $post ) {
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

				$row_data += $content_data['post_params'];

				foreach ( $content_data['post_params'] as $key => $val ) {
					if ( ! isset( $fields_map[ $key ] ) ) {
						$fields_map[ $key ] = true;
						$fields[]           = $key;
					}
				}

				$json_line   = wp_json_encode( $row_data ) . "\n";
				$bytes_wrote = fwrite( $temp_stream, $json_line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

				if ( false === $bytes_wrote || $bytes_wrote < strlen( $json_line ) ) {
					return false;
				}
			}

			foreach ( $chunk as $id ) {
				clean_post_cache( $id );
			}
		}

		return $fields;
	}

	/**
	 * Read JSON row data from temporary stream buffer and write formatted CSV to output stream.
	 *
	 * @param resource $temp_stream Temporary stream handle.
	 * @param resource $output      Output stream handle.
	 * @param array    $fields      List of field names.
	 * @return void
	 */
	private function write_temp_stream_to_csv( $temp_stream, $output, array $fields ) {
		// Output CSV headers.
		$escaped_fields = array_map( array( $this, 'esc_csv' ), $fields );
		fputcsv( $output, $escaped_fields );

		// Stream rows from temp buffer.
		rewind( $temp_stream );

		while ( true ) {
			$line = fgets( $temp_stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgets
			if ( false === $line ) {
				break;
			}
			$row_data = json_decode( trim( $line ), true );
			if ( ! is_array( $row_data ) ) {
				continue;
			}

			$current_row = array();
			foreach ( $fields as $single_field_name ) {
				$value = isset( $row_data[ $single_field_name ] ) ? $row_data[ $single_field_name ] : '';
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				$current_row[] = $this->esc_csv( $value );
			}
			fputcsv( $output, $current_row );
		}
	}

	/**
	 * Stream CSV export directly to the provided handle in chunks to minimize memory usage.
	 *
	 * @param resource $output Output stream handle.
	 * @return void
	 */
	public function stream_to_handle( $output ) {
		$args = array(
			'posts_per_page'         => -1,
			'post_type'              => 'beastfeedbacks',
			'post_status'            => array( 'publish' ),
			'order'                  => 'ASC',
			'suppress_filters'       => false,
			'date_query'             => array(),
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		$post_ids = get_posts( $args );

		if ( empty( $post_ids ) ) {
			return;
		}

		$chunk_size = 500;
		$chunks     = array_chunk( $post_ids, $chunk_size );

		$temp_stream = $this->open_temp_stream();

		if ( ! $temp_stream ) {
			return;
		}

		$fields = $this->process_csv_post_chunks( $chunks, $temp_stream );

		if ( false === $fields ) {
			fclose( $temp_stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return;
		}

		$this->write_temp_stream_to_csv( $temp_stream, $output, $fields );

		fclose( $temp_stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Open temporary stream buffer for CSV export.
	 *
	 * @return resource|false
	 */
	protected function open_temp_stream() {
		return fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	}

	/**
	 * Retrieve posts for CSV export
	 *
	 * @return array List of WP_Post objects.
	 */
	public function get_export_posts() {
		$args = array(
			'posts_per_page'         => -1,
			'post_type'              => 'beastfeedbacks',
			'post_status'            => array( 'publish' ),
			'order'                  => 'ASC',
			'suppress_filters'       => false,
			'date_query'             => array(),
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		);

		return get_posts( $args );
	}

	/**
	 * Extract CSV data and fields from feedback posts
	 *
	 * @param array $posts List of WP_Post objects.
	 * @return array Map of field keys to associative array of [ post_id => data_value ].
	 */
	public function get_csv_data( array $posts ) {
		$post_datas = array();
		$extractor  = BeastFeedbacks_Data_Extractor::get_instance();

		$parent_ids = array_values( array_filter( array_map( 'intval', array_unique( wp_list_pluck( $posts, 'post_parent' ) ) ) ) );
		if ( ! empty( $parent_ids ) ) {
			_prime_post_caches( $parent_ids );
		}

		foreach ( $posts as $post ) {
			$id = $post->ID;

			$source = '';
			if ( $post->post_parent ) {
				$permalink_data = $extractor->get_parent_permalink_data( $post->post_parent );
				$source         = $permalink_data['path'];
			}

			$content_data = $extractor->extract_post_content_data( $post->post_content );

			$post_datas['source'][ $id ]     = $source;
			$post_datas['date'][ $id ]       = $post->post_date;
			$post_datas['type'][ $id ]       = $content_data['type'];
			$post_datas['ip_address'][ $id ] = $content_data['ip_address'];
			$post_datas['user_agent'][ $id ] = $content_data['user_agent'];

			foreach ( $content_data['post_params'] as $key => $value ) {
				if ( is_array( $value ) ) {
					$post_datas[ $key ][ $id ] = implode( ',', $value );
				} else {
					$post_datas[ $key ][ $id ] = $value;
				}
			}
		}

		return $post_datas;
	}

	/**
	 * Output CSV headers and stream content to provided handle
	 *
	 * @param resource $output     Output stream handle.
	 * @param array    $posts      List of WP_Post objects.
	 * @param array    $post_datas Formatted post data map.
	 * @return void
	 */
	public function output_to_handle( $output, array $posts, array $post_datas ) {
		$fields = array_keys( $post_datas );

		// Security: Escape CSV header column names against formula injection.
		$escaped_fields = array_map( array( $this, 'esc_csv' ), $fields );
		fputcsv( $output, $escaped_fields );

		foreach ( $posts as $post ) {
			$current_row = array();

			foreach ( $fields as $single_field_name ) {
				$value         = isset( $post_datas[ $single_field_name ][ $post->ID ] )
					? $post_datas[ $single_field_name ][ $post->ID ]
					: '';
				$current_row[] = $this->esc_csv( $value );
			}
			fputcsv( $output, $current_row );
		}
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
		if ( is_array( $field ) ) {
			$field = implode( ',', array_map( 'strval', array_filter( $field, 'is_scalar' ) ) );
		}

		$string_field = (string) $field;

		if ( '' === $string_field ) {
			return $field;
		}

		// Fast path: Check if any active content trigger character is present anywhere in the string.
		if ( false === strpbrk( $string_field, "=+-@|%\t\r\n" ) ) {
			return $field;
		}

		static $active_content_triggers = array(
			'='  => true,
			'+'  => true,
			'-'  => true,
			'@'  => true,
			'|'  => true,
			'%'  => true,
			"\t" => true,
			"\r" => true,
			"\n" => true,
		);

		$starts_with_trigger = function ( $str ) use ( $active_content_triggers ) {
			$trimmed = ltrim( $str, " \v\0\x0C" );
			return '' !== $trimmed && isset( $active_content_triggers[ mb_substr( $trimmed, 0, 1 ) ] );
		};

		$needs_escaping = $starts_with_trigger( $string_field );

		if ( ! $needs_escaping ) {
			$lines = preg_split( '/(\r\n|\r|\n)/', $string_field );
			foreach ( $lines as $line ) {
				if ( $starts_with_trigger( $line ) ) {
					$needs_escaping = true;
					break;
				}
			}
		}

		if ( $needs_escaping && 0 !== strpos( $string_field, "' " ) ) {
			$field = "' " . $field;
		}

		return $field;
	}
}
