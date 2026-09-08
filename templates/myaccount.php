<?php
/**
 * Replacement for WooCommerce's myaccount/my-account.php.
 *
 * Loaded via the woocommerce_locate_template filter when the takeover is on,
 * so it works even with themes that load the core template directly
 * (WoodMart, Flatsome, Astra, …) instead of relying on hook swaps alone.
 *
 * The Woo side-menu is intentionally NOT rendered — WooPanel ships its own.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

// Woo notices (password reset confirmations, etc.) still belong to Woo flows.
if ( function_exists( 'wc_print_notices' ) ) {
	wc_print_notices();
}

WooPanel_Shortcode::account_content();
