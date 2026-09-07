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
	 * @param array $atts Shortcode atts.
	 * @return string
	 */
	private static function resolve_view( $atts ) {
		$allowed = array( 'dashboard', 'orders', 'downloads', 'address', 'account', 'order' );
		$view    = isset( $_GET['woopanel_view'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_view'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation state.
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

		$view = $atts['view'] ? sanitize_key( $atts['view'] ) : self::resolve_view( $atts );

		return WooPanel_Render::panel( $view, $atts, 'shortcode' );
	}

	/**
	 * Hooked into woocommerce_account_dashboard — replaces the default dashboard.
	 *
	 * @return void
	 */
	public static function render_account_takeover() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation state.
		$view = isset( $_GET['woopanel_view'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_view'] ) ) : 'dashboard';
		$view = in_array( $view, array( 'dashboard', 'orders', 'downloads', 'address', 'account', 'order' ), true ) ? $view : 'dashboard';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- panel escapes internally.
		echo WooPanel_Render::panel( $view, array(), 'takeover' );
	}
}
