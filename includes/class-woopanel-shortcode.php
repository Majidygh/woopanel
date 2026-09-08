<?php
/**
 * Shortcode.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * [woopanel] shortcode registration and My Account takeover hook.
 */
class WooPanel_Shortcode {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_shortcode( 'woopanel', array( __CLASS__, 'render' ) );
	}

	/**
	 * Resolve the requested view against the whitelist.
	 *
	 * Query-string navigation always wins over the shortcode attribute —
	 * otherwise [woopanel view="orders"] would lock the sidebar links.
	 *
	 * @param array $atts Shortcode atts.
	 * @return string
	 */
	private static function resolve_view( $atts ) {
		$allowed = array( 'dashboard', 'orders', 'downloads', 'address', 'account', 'order' );
		$view    = isset( $_GET['woopanel_view'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation state.
		if ( '' === $view && ! empty( $atts['view'] ) ) {
			$view = sanitize_key( $atts['view'] );
		}
		return in_array( $view, $allowed, true ) ? $view : 'dashboard';
	}

	/**
	 * Shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'view'     => '',
				'redirect' => '',
			),
			$atts,
			'woopanel'
		);

		$view = self::resolve_view( $atts );

		return WooPanel_Render::panel( $view, $atts, 'shortcode' );
	}

	/**
	 * Hooked into woocommerce_account_dashboard — replaces the default dashboard.
	 *
	 * @return void
	 */
	public static function render_account_takeover() {
		self::account_content();
	}

	/**
	 * Drop-in replacement for woocommerce_account_content(): dispatches Woo
	 * endpoints as usual, but renders WooPanel instead of the dashboard template.
	 *
	 * @return void
	 */
	public static function account_content() {
		global $wp;

		if ( ! empty( $wp->query_vars ) ) {
			foreach ( $wp->query_vars as $key => $value ) {
				if ( 'pagename' === $key ) {
					continue;
				}
				if ( has_action( 'woocommerce_account_' . $key . '_endpoint' ) ) {
					do_action( 'woocommerce_account_' . $key . '_endpoint', $value );
					return;
				}
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation state.
		$view = isset( $_GET['woopanel_view'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_view'] ) ) : 'dashboard';
		$view = in_array( $view, array( 'dashboard', 'orders', 'downloads', 'address', 'account', 'order' ), true ) ? $view : 'dashboard';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- panel escapes internally.
		echo WooPanel_Render::panel( $view, array(), 'takeover' );
	}
}
