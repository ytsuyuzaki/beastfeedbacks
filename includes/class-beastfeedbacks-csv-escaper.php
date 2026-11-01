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
