<?php
/**
 * Test bootstrap for BrainMonkey unit tests without full WP integration setup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../../' );
}
if ( ! defined( 'BEASTFEEDBACKS_DOMAIN' ) ) {
	define( 'BEASTFEEDBACKS_DOMAIN', 'beastfeedbacks' );
}
if ( ! defined( 'BEASTFEEDBACKS_DIR' ) ) {
	define( 'BEASTFEEDBACKS_DIR', ABSPATH );
}
if ( ! defined( 'BEASTFEEDBACKS_URL' ) ) {
	define( 'BEASTFEEDBACKS_URL', 'http://example.com/wp-content/plugins/beastfeedbacks/' );
}
if ( ! defined( 'BEASTFEEDBACKS_VERSION' ) ) {
	define( 'BEASTFEEDBACKS_VERSION', '0.1.0-test' );
}

require_once ABSPATH . 'vendor/autoload.php';

// Stub WordPress functions for file inclusion / init calls
Brain\Monkey\setUp();
Brain\Monkey\Functions\stubs( array(
	'add_action'                 => true,
	'add_filter'                 => true,
	'get_current_screen'         => null,
	'esc_html__'                 => function( $text ) { return $text; },
	'esc_html'                   => function( $text ) { return $text; },
	'esc_attr'                   => function( $text ) { return $text; },
	'sanitize_key'               => function( $text ) { return $text; },
	'sanitize_text_field'        => function( $text ) { return $text; },
	'absint'                     => function( $val ) { return (int) $val; },
	'register_block_type'        => (object) array( 'editor_script' => 'script-handle' ),
	'wp_set_script_translations' => true,
) );

// Load plugin classes and source files
require_once ABSPATH . 'includes/class-beastfeedbacks.php';
require_once ABSPATH . 'includes/class-beastfeedbacks-admin.php';
require_once ABSPATH . 'src/survey-form/init.php';

Brain\Monkey\tearDown();
