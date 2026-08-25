<?php
/**
 * Tests for uninstall.php.
 *
 * @package BeastFeedbacks
 */

/**
 * Uninstall test case.
 */
class Uninstall_Test extends BeastFeedbacks_TestCase {

	/**
	 * Test that uninstall.php exits when WP_UNINSTALL_PLUGIN is not defined.
	 */
	public function test_uninstall_exits_when_wp_uninstall_plugin_is_not_defined(): void {
		$uninstall_file = BEASTFEEDBACKS_DIR . 'uninstall.php';
		$cmd            = sprintf(
			'php -r %s',
			escapeshellarg( "require '$uninstall_file'; echo 'COMPLETED';" )
		);
		$output         = shell_exec( $cmd );

		$this->assertStringNotContainsString( 'COMPLETED', (string) $output );
	}

	/**
	 * Test that uninstall.php completes execution when WP_UNINSTALL_PLUGIN is defined.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uninstall_runs_when_wp_uninstall_plugin_is_defined(): void {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		$uninstall_file = BEASTFEEDBACKS_DIR . 'uninstall.php';

		ob_start();
		require $uninstall_file;
		$output = ob_get_clean();

		$this->assertTrue( defined( 'WP_UNINSTALL_PLUGIN' ) );
		$this->assertSame( '', $output );
	}
}
