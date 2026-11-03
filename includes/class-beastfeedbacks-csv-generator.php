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
		$formatter  = BeastFeedbacks_Feedback_Formatter::get_instance();

		foreach ( $chunks as $chunk ) {
			_prime_post_caches( $chunk, false, false );
			$posts = array();
			foreach ( $chunk as $post_id ) {
				$post = get_post( $post_id );
				if ( $post ) {
					$posts[] = $post;
				}
			}

			$formatter->prime_parent_caches( $posts );

			foreach ( $posts as $post ) {
				$row_data = $formatter->format_post_data( $post );

				foreach ( $row_data as $key => $val ) {
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
		$escaper        = BeastFeedbacks_CSV_Escaper::get_instance();
		$escaped_fields = array_map( array( $escaper, 'esc_csv' ), $fields );
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
				$current_row[] = $escaper->esc_csv( $value );
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
		$formatter  = BeastFeedbacks_Feedback_Formatter::get_instance();

		$formatter->prime_parent_caches( $posts );

		foreach ( $posts as $post ) {
			$id       = $post->ID;
			$row_data = $formatter->format_post_data( $post );

			foreach ( $row_data as $key => $value ) {
				$post_datas[ $key ][ $id ] = $value;
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
		$fields  = array_keys( $post_datas );
		$escaper = BeastFeedbacks_CSV_Escaper::get_instance();

		// Security: Escape CSV header column names against formula injection.
		$escaped_fields = array_map( array( $escaper, 'esc_csv' ), $fields );
		fputcsv( $output, $escaped_fields );

		foreach ( $posts as $post ) {
			$current_row = array();

			foreach ( $fields as $single_field_name ) {
				$value         = isset( $post_datas[ $single_field_name ][ $post->ID ] )
					? $post_datas[ $single_field_name ][ $post->ID ]
					: '';
				$current_row[] = $escaper->esc_csv( $value );
			}
			fputcsv( $output, $current_row );
		}
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * @param string|array $field - the CSV field.
	 *
	 * @return string
	 * @deprecated Use BeastFeedbacks_CSV_Escaper::get_instance()->esc_csv() instead.
	 */
	public function esc_csv( $field ) {
		return BeastFeedbacks_CSV_Escaper::get_instance()->esc_csv( $field );
	}
}
