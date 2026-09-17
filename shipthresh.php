<?php
/**
 * Plugin Name: ShipThresh
 * Plugin URI: https://github.com/squibsy/shipthresh
 * Description: Displays a configurable free-shipping progress message on the WooCommerce checkout page. Message text and spend threshold are editable from an admin settings screen.
 * Version: 1.0.0
 * Author: Bananalytics
 * Author URI: https://bananalytics.ie
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shipthresh
 * Requires Plugins: woocommerce
 * WC requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SHIPTHRESH_VERSION', '1.0.0' );
define( 'SHIPTHRESH_OPTION_KEY', 'shipthresh_settings' );
define( 'SHIPTHRESH_FILE', __FILE__ );
define( 'SHIPTHRESH_DIR', plugin_dir_path( __FILE__ ) );

require_once SHIPTHRESH_DIR . 'includes/class-shipthresh-settings.php';
require_once SHIPTHRESH_DIR . 'includes/class-shipthresh-notice.php';

register_activation_hook( __FILE__, array( 'ShipThresh_Settings', 'set_defaults' ) );

add_action( 'plugins_loaded', 'shipthresh_init' );

/**
 * Bootstrap the plugin once all plugins have loaded, so the WooCommerce
 * class-exists check is reliable regardless of plugin load order.
 */
function shipthresh_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'shipthresh_missing_wc_notice' );
		return;
	}

	new ShipThresh_Settings();
	new ShipThresh_Notice();
}

function shipthresh_missing_wc_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>' . esc_html__( 'ShipThresh requires WooCommerce to be installed and active.', 'shipthresh' ) . '</p></div>';
}
