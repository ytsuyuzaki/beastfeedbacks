<?php
/**
 * CSV エクスポート機能
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.0
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * CSVエクスポート
 */
class BeastFeedbacks_Export {

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
	 * Init
	 *
	 * @param string $action_name Action name for CSV export. Default 'beastfeedbacks_export'.
	 */
	public function init( $action_name = 'beastfeedbacks_export' ) {
		add_action( 'wp_ajax_' . $action_name, array( $this, 'download_csv' ) );
	}

	/**
	 * Download exported data as CSV
	 */
	public function download_csv() {
		check_admin_referer( 'beastfeedbacks_csv_export' );

		// Security: Verify user capability to prevent unauthorized data export.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'beastfeedbacks' ), 403 );
		}

		$filename = sprintf(
			'beastfeedbacks-%s.csv',
			gmdate( 'Y-m-d_H:i' )
		);

		$this->stream_csv( $filename );
		wp_die();
	}

	/**
	 * Send HTTP headers for CSV attachment download.
	 *
	 * @param string $filename CSV file name.
	 * @return void
	 */
	private function send_csv_headers( $filename ) {
		if ( ! headers_sent() ) {
			$safe_filename = sanitize_file_name( $filename );
			header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			header( 'Content-Type: text/csv; charset=utf-8' );
		}
	}

	/**
	 * Stream CSV export directly to output in chunks to minimize memory usage.
	 *
	 * @param string $filename CSV file name.
	 * @return void
	 */
	public function stream_csv( $filename ) {
		$this->send_csv_headers( $filename );
		$output = fopen( 'php://output', 'w' );
		BeastFeedbacks_CSV_Generator::get_instance()->stream_to_handle( $output );
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Retrieve posts for CSV export
	 *
	 * @return array List of WP_Post objects.
	 * @deprecated Use BeastFeedbacks_CSV_Generator::get_instance()->get_export_posts() instead.
	 */
	public function get_export_posts() {
		return BeastFeedbacks_CSV_Generator::get_instance()->get_export_posts();
	}

	/**
	 * Extract CSV data and fields from feedback posts
	 *
	 * @param array $posts List of WP_Post objects.
	 * @return array Map of field keys to associative array of [ post_id => data_value ].
	 * @deprecated Use BeastFeedbacks_CSV_Generator::get_instance()->get_csv_data() instead.
	 */
	public function get_csv_data( array $posts ) {
		return BeastFeedbacks_CSV_Generator::get_instance()->get_csv_data( $posts );
	}

	/**
	 * Output CSV headers and stream content to php://output
	 *
	 * @param string $filename   CSV file name.
	 * @param array  $posts      List of WP_Post objects.
	 * @param array  $post_datas Formatted post data map.
	 * @return void
	 */
	public function output_csv( $filename, array $posts, array $post_datas ) {
		$this->send_csv_headers( $filename );
		$output = fopen( 'php://output', 'w' );
		BeastFeedbacks_CSV_Generator::get_instance()->output_to_handle( $output, $posts, $post_datas );
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * Escape a string to be used in a CSV context
	 *
	 * @param string $field - the CSV field.
	 * @return string
	 * @deprecated Use BeastFeedbacks_CSV_Generator::get_instance()->esc_csv() instead.
	 */
	public function esc_csv( $field ) {
		return BeastFeedbacks_CSV_Generator::get_instance()->esc_csv( $field );
	}
}
