<?php
/**
 * Replacement for WooCommerce's myaccount/dashboard.php.
 *
 * Covers themes that call woocommerce_account_content() directly (bypassing
 * the hook swap): Woo loads dashboard.php, and this renders WooPanel instead.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

WooPanel_Shortcode::account_content();
