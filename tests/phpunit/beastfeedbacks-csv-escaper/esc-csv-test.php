<?php
/**
 * Tests for BeastFeedbacks_CSV_Escaper.
 *
 * @package BeastFeedbacks
 */

class BeastFeedbacks_CSV_Escaper_Test extends BeastFeedbacks_TestCase {

	/**
	 * Setup.
	 */
	protected function set_up(): void {
		parent::set_up();
	}

	/**
	 * Clean up.
	 */
	protected function tear_down(): void {
		parent::tear_down();
	}

	/** @test */
	public function esc_csv_prefixes_when_dangerous_first_char(): void {
		$escaper = \BeastFeedbacks_CSV_Escaper::get_instance();

		$this->assertSame( "' =SUM(A1:A2)", $escaper->esc_csv( '=SUM(A1:A2)' ) );
		$this->assertSame( "' +1+2", $escaper->esc_csv( '+1+2' ) );
		$this->assertSame( "' -1", $escaper->esc_csv( '-1' ) );
		$this->assertSame( "' @cmd", $escaper->esc_csv( '@cmd' ) );
		$this->assertSame( "' \tcmd", $escaper->esc_csv( "\tcmd" ) );
		$this->assertSame( "' \rcmd", $escaper->esc_csv( "\rcmd" ) );
		$this->assertSame( "'  =SUM(A1:A2)", $escaper->esc_csv( ' =SUM(A1:A2)' ) );
		$this->assertSame( "'   +1+2", $escaper->esc_csv( '  +1+2' ) );
		$this->assertSame( "' \x0C=SUM(A1:A2)", $escaper->esc_csv( "\x0C=SUM(A1:A2)" ) );
		$this->assertSame( "' Line1\n=SUM(A1:A2)", $escaper->esc_csv( "Line1\n=SUM(A1:A2)" ) );

		$this->assertSame( 'safe', $escaper->esc_csv( 'safe' ) );
		$this->assertSame( '  space', $escaper->esc_csv( '  space' ) );
	}

	/** @test */
	public function esc_csv_handles_arrays(): void {
		$escaper = \BeastFeedbacks_CSV_Escaper::get_instance();
		$this->assertSame( '1,2,3', $escaper->esc_csv( array( 1, 2, 3 ) ) );
		$this->assertSame( "' =1,2", $escaper->esc_csv( array( '=1', 2 ) ) );
	}

	/** @test */
	public function esc_csv_handles_empty_string(): void {
		$escaper = \BeastFeedbacks_CSV_Escaper::get_instance();
		$this->assertSame( '', $escaper->esc_csv( '' ) );
	}
}
