<?php
/**
 * BeastFeedbacks
 *
 * @package           BeastFeedbacks
 * @author            ytsuyuzaki
 * @copyright         2023 ytsuyuzaki
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BeastFeedbacks
 * Description:       Provides a block-editor form for receiving powerful user feedback.
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Version:           0.1.1
 * Author:            ytsuyuzaki
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       beastfeedbacks
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BEASTFEEDBACKS_VERSION', '0.1.1' );
define( 'BEASTFEEDBACKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEASTFEEDBACKS_DOMAIN', basename( BEASTFEEDBACKS_DIR ) );
define( 'BEASTFEEDBACKS_URL', plugin_dir_url( __FILE__ ) );

/**
 * プラグイン有効化
 */
function beastfeedbacks_activate() {
	include_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-activator.php';
	BeastFeedbacks_Activator::activate();
}

/**
 * プラグイン無効化
 */
function beastfeedbacks_deactivate() {
	include_once BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks-deactivator.php';
	BeastFeedbacks_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'beastfeedbacks_activate' );
register_deactivation_hook( __FILE__, 'beastfeedbacks_deactivate' );

require BEASTFEEDBACKS_DIR . 'includes/class-beastfeedbacks.php';
BeastFeedbacks::get_instance()->init();
