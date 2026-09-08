<?php
/**
 * Plugin Name:       WooPanel
 * Plugin URI:        https://github.com/Majidygh/woopanel
 * Description:       Modern user dashboard for WooCommerce — orders, downloads, addresses and account settings in one clean panel. RTL-ready, dark mode, zero build step.
 * Version:           1.6.0
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

define( 'WOOPANEL_VERSION', '1.6.0' );
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
require_once WOOPANEL_DIR . 'includes/class-woopanel-takeover.php';
require_once WOOPANEL_DIR . 'includes/class-woopanel-admin.php';

register_activation_hook( __FILE__, array( 'WooPanel_Options', 'add_default_options' ) );

// Version-gated housekeeping for existing installs (runs once per upgrade;
// activation hook alone never fires on `wp plugin update`).
add_action( 'plugins_loaded', array( 'WooPanel_Options', 'maybe_upgrade' ), 5 );

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
 * Takeover of the default WooCommerce "My Account" area — ON by default.
 * Three layers (template swap, hook swap, endpoint routing) so it works
 * with any theme, including ones that render their own account layout.
 *
 * Must run after WC's init(0) loads wc-template-hooks.php, otherwise the
 * remove_action() calls below find nothing to remove.
 */
add_action( 'init', function () {
	if ( ! WooPanel_Takeover::active() ) {
		return;
	}
	WooPanel_Takeover::init();
}, 20 );
