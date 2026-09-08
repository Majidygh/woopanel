<?php
/**
 * My Account takeover.
 *
 * Replaces the default WooCommerce account area with WooPanel across every
 * endpoint, using three independent layers so it survives any theme:
 *
 *  1. Template swap (woocommerce_locate_template) — works even when a theme
 *     (WoodMart, Flatsome, …) loads the core template directly.
 *  2. Hook swap (woocommerce_account_content) — the classic path.
 *  3. Endpoint routing — Woo's own URLs (/my-account/orders/) render the
 *     matching WooPanel view instead of Woo templates, so links from order
 *     emails and the theme menu keep working.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * Takeover controller.
 */
class WooPanel_Takeover {

	/**
	 * Woo endpoint slug => WooPanel view.
	 *
	 * Slugs verified against WC()->query->query_vars: orders, downloads,
	 * edit-address, edit-account, payment-methods, view-order.
	 *
	 * @return array
	 */
	public static function endpoint_map() {
		return apply_filters(
			'woopanel_endpoint_map',
			array(
				'orders'         => 'orders',
				'downloads'      => 'downloads',
				'edit-address'   => 'address',
				'edit-account'   => 'account',
				'payment-methods' => 'account',
			)
		);
	}

	/**
	 * True when the takeover is enabled and Woo is present.
	 *
	 * @return bool
	 */
	public static function active() {
		$options = woopanel_get_options();
		return ! empty( $options['replace_dashboard'] ) && WooPanel_Data::is_woo();
	}

	/**
	 * Register hooks (called once from bootstrap when active).
	 *
	 * @return void
	 */
	public static function init() {
		// 1) Swap the my-account template itself.
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'locate_template' ), 10, 3 );

		// 2) Swap the content hook (covers themes that call the hook directly).
		remove_action( 'woocommerce_account_content', 'woocommerce_account_content', 10 );
		add_action( 'woocommerce_account_content', array( 'WooPanel_Shortcode', 'account_content' ), 10 );

		// 3) Drop Woo's side menu — WooPanel renders its own navigation.
		remove_action( 'woocommerce_account_navigation', 'woocommerce_account_navigation' );

		// 4) Route Woo endpoint URLs into WooPanel views.
		add_action( 'wp', array( __CLASS__, 'route_endpoints' ), 20 );

		// 5) Belt-and-braces: hide theme account sidebars via CSS.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'hide_css' ), 20 );
	}

	/**
	 * Serve WooPanel's templates.
	 *
	 * my-account.php: full panel replaces the Woo account layout.
	 * dashboard.php: covers themes that call woocommerce_account_content()
	 * directly instead of firing the hook.
	 *
	 * Deliberately does NOT override themes that ship their own
	 * woocommerce/myaccount/my-account.php — a theme layout there is an
	 * explicit author choice; the hook swap still replaces the content.
	 *
	 * @param string $template Absolute theme path.
	 * @param string $template_name Template name relative to the templates dir.
	 * @param string $located Located template path.
	 * @return string
	 */
	public static function locate_template( $template, $template_name, $located ) {
		if ( ! is_user_logged_in() ) {
			return $template; // Guests keep Woo's login/register flow.
		}
		if ( 'myaccount/my-account.php' === $template_name && empty( $located ) ) {
			return WOOPANEL_DIR . 'templates/myaccount.php';
		}
		if ( 'myaccount/dashboard.php' === $template_name ) {
			return WOOPANEL_DIR . 'templates/dashboard.php';
		}
		return $template;
	}

	/**
	 * On an account endpoint URL, render the matching WooPanel view.
	 *
	 * @return void
	 */
	public static function route_endpoints() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return;
		}
		$map = self::endpoint_map();
		foreach ( $map as $slug => $view ) {
			if ( '' !== $slug && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( $slug ) ) {
				// Let WooPanel's own query-string nav win if present.
				if ( empty( $_GET['woopanel_view'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing.
					$_GET['woopanel_view'] = $view; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- sanitized downstream.
				}
				return;
			}
		}
	}

	/**
	 * CSS that suppresses the theme/Woo account sidebar and stretches the
	 * panel across the full page on account pages.
	 *
	 * @return void
	 */
	public static function hide_css() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
			return;
		}
		// Sidebar classes verified against core Woo + Woodmart (wd-my-account-
		// sidebar per xtemos docs), Flatsome, Astra, Divi, OceanWP.
		$css = '.woocommerce-account .woocommerce-MyAccount-navigation,'
			. '.woocommerce-account .wd-my-account-sidebar,.woocommerce-account .woodmart-my-account-sidebar,'
			. '.woocommerce-account .woodmart-account-navigation,.woocommerce-account .wd-account-navigation,'
			. '.woocommerce-account .account-sidebar,.woocommerce-account .et_myaccount__navigation,'
			. '.woocommerce-account .flatsome-account-sidebar,.woocommerce-account .astra-woocommerce-account-sidebar,'
			. '.woocommerce-account .oceanwp-account-navigation-sidebar{display:none!important}'
			// Let the content column take everything the sidebar left behind.
			. '.woocommerce-account .woocommerce-MyAccount-content{width:100%!important;max-width:100%!important;flex:1 1 100%!important}'
			// Full-bleed the panel past the theme's content container.
			. 'body.woocommerce-account{overflow-x:clip}'
			. '.wpl-panel--takeover{width:100vw;max-width:100vw;margin-inline:calc(50% - 50vw);border-radius:0;box-shadow:none}'
			. '.wpl-panel--takeover .wpl-shell{min-height:calc(100vh - 120px)}'
			// Theme page titles ("My account") duplicate the panel header.
			. '.woocommerce-account .entry-title,.woocommerce-account .page-title,.woocommerce-account .wp-block-post-title,.woocommerce-account .woodmart-title-container{display:none!important}'
			// Theme resets (button/svg display rules) must not eat the switcher.
			. '.wpl-panel .wpl-theme{display:inline-flex!important;visibility:visible!important;opacity:1!important}'
			. '.wpl-panel .wpl-theme__btn{display:grid!important;visibility:visible!important}'
			. '.wpl-panel .wpl-theme__btn svg{display:block!important;width:16px!important;height:16px!important}';
		$css = apply_filters( 'woopanel_takeover_css', $css );
		wp_add_inline_style( 'woopanel', $css );
	}
}
