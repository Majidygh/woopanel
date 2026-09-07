<?php
/**
 * Plugin Name:       WooPanel
 * Plugin URI:        https://github.com/Majidygh/woopanel
 * Description:       Modern user dashboard for WooCommerce — orders, downloads, addresses and account settings in one clean panel. RTL-ready, dark mode, zero build step.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            majidygh
 * Author URI:        https://github.com/majidygh
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woopanel
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   11.1
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOPANEL_VERSION', '1.0.1' );
define( 'WOOPANEL_FILE', __FILE__ );
define( 'WOOPANEL_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOPANEL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare WooCommerce HPOS (custom order tables) compatibility.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

require_once WOOPANEL_DIR . 'includes/helpers.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-options.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-data.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-forms.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-render.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-shortcode.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-admin.php';

register_activation_hook( __FILE__, array( 'WooPanel_Options', 'add_default_options' ) );

add_action( 'init', array( 'WooPanel_Shortcode', 'init' ) );
add_action( 'admin_menu', array( 'WooPanel_Admin', 'register_menu' ) );
add_action( 'admin_init', array( 'WooPanel_Admin', 'register_settings' ) );

/**
 * Load translations (bundled fa_IR ships with the plugin).
 */
add_action( 'init', function () {
	load_plugin_textdomain( 'woopanel', false, dirname( plugin_basename( WOOPANEL_FILE ) ) . '/languages' );
} );

/**
 * Optional takeover of the default WooCommerce "My Account" dashboard content.
 * Off by default — endpoints stay intact, only the dashboard area is replaced.
 */
add_action( 'plugins_loaded', function () {
	$options = WooPanel_Options::get();
	if ( empty( $options['replace_dashboard'] ) || ! WooPanel_Data::is_woo() ) {
		return;
	}
	add_action( 'woocommerce_account_dashboard', array( 'WooPanel_Shortcode', 'render_account_takeover' ), 5 );
} );
