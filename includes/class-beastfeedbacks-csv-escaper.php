<?php
/**
 * CSVデータのエスケープ処理
 *
 * @link       https://beastfeedbacks.com
 * @since      0.1.10
 *
 * @package    BeastFeedbacks
 * @subpackage BeastFeedbacks/includes
 */

/**
 * CSVデータのエスケープロジック
 */
class BeastFeedbacks_CSV_Escaper {

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
	 * Escape a string to be used in a CSV context
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility for phishing attacks and
	 * disclosure of sensitive information.
	 *
	 * Additionally, Excel exposes the ability to launch arbitrary commands through the DDE protocol.
	 *
	 * @see https://www.contextis.com/en/blog/comma-separated-vulnerabilities
	 *
	 * @param string|array $field - the CSV field.
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

		// Performance optimization: Using direct byte offset $trimmed[0] avoids expensive mb_substr()
		// overhead because all trigger characters are single-byte ASCII characters.
		$trimmed        = ltrim( $string_field, " \v\0\x0C" );
		$needs_escaping = ( '' !== $trimmed && isset( $active_content_triggers[ $trimmed[0] ] ) );

		// Performance optimization: Avoid preg_split() overhead for strings without newlines.
		if ( ! $needs_escaping && false !== strpbrk( $string_field, "\r\n" ) ) {
			$lines = preg_split( '/(\r\n|\r|\n)/', $string_field );
			foreach ( $lines as $line ) {
				$trimmed_line = ltrim( $line, " \v\0\x0C" );
				if ( '' !== $trimmed_line && isset( $active_content_triggers[ $trimmed_line[0] ] ) ) {
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
