<?php
/**
 * Shared helpers.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cached options accessor.
 *
 * @return array
 */
function woopanel_get_options() {
	static $cache = null;
	if ( null === $cache ) {
		$cache = WooPanel_Options::get_resolved();
	}
	return $cache;
}

/**
 * Convert Western digits to the current locale's digits (Persian: ۰-۹).
 *
 * @param string $text Text possibly containing 0-9.
 * @return string
 */
function woopanel_localize_digits( $text ) {
	if ( ! is_rtl() ) {
		return $text;
	}
	$map = array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' );
	return strtr( (string) $text, $map );
}

/**
 * Localize digits inside an HTML fragment (prices, dates).
 * Safe: digits only ever occur in text nodes of our markup.
 *
 * @param string $html Fragment with esc/kses already applied.
 * @return string
 */
function woopanel_localize_digits_html( $html ) {
	if ( ! is_rtl() ) {
		return $html;
	}
	// Convert standalone digit runs in text nodes; never touch HTML entities (&#036; etc.).
	return preg_replace_callback(
		'/(?<=>)([^<]+)(?=<)/',
		function ( $m ) {
			$converted = preg_replace_callback(
				'/&#\d+;|\d+/u',
				function ( $t ) {
					return 0 === strpos( $t[0], '&#' ) ? $t[0] : woopanel_localize_digits( $t[0] );
				},
				$m[1]
			);
			return $converted;
		},
		(string) $html
	);
}

/**
 * Human label for an order status.
 *
 * @param string $status Status slug, with or without the wc- prefix.
 * @return string
 */
function woopanel_status_label( $status ) {
	$status = 'wc-' === substr( $status, 0, 3 ) ? $status : 'wc-' . $status;
	if ( function_exists( 'wc_get_order_status_name' ) ) {
		return wc_get_order_status_name( $status );
	}
	return ucfirst( str_replace( 'wc-', '', $status ) );
}

/**
 * CSS modifier class for a status badge.
 *
 * @param string $status Status slug.
 * @return string
 */
function woopanel_status_class( $status ) {
	return 'wpl-badge--' . sanitize_html_class( str_replace( 'wc-', '', $status ) );
}

/**
 * Localized text for post/save redirect codes.
 *
 * @param string $code Message code.
 * @return array {type: string, text: string}
 */
function woopanel_notice_for( $code ) {
	$notices = array(
		'address_saved'        => array( 'success', __( 'Your address has been saved.', 'woopanel' ) ),
		'account_saved'        => array( 'success', __( 'Your account details have been saved.', 'woopanel' ) ),
		'password_changed'     => array( 'success', __( 'Your password has been changed.', 'woopanel' ) ),
		'err_email'            => array( 'error', __( 'Please enter a valid email address that is not already in use.', 'woopanel' ) ),
		'err_password_match'   => array( 'error', __( 'New password and confirmation do not match.', 'woopanel' ) ),
		'err_password_short'   => array( 'error', __( 'New password must be at least 8 characters long.', 'woopanel' ) ),
		'err_password_current' => array( 'error', __( 'Your current password is not correct.', 'woopanel' ) ),
		'err_address'          => array( 'error', __( 'The address could not be saved. Please check the form and try again.', 'woopanel' ) ),
		'err_form'             => array( 'error', __( 'Your session expired or the form was invalid. Please try again.', 'woopanel' ) ),
	);
	return isset( $notices[ $code ] ) ? $notices[ $code ] : array( 'info', '' );
}

/**
 * Inline SVG icon set (stroke style, inherits currentColor).
 *
 * @param string $name Icon name.
 * @param int    $size Pixel size.
 * @return string
 */
function woopanel_icon( $name, $size = 20 ) {
	$paths = array(
		'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'bag'      => '<path d="M6 7h12l1.2 13.1a1 1 0 0 1-1 1.1H5.8a1 1 0 0 1-1-1.1L6 7z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
		'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
		'pin'      => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
		'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/>',
		'moon'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>',
		'monitor'  => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
		'chevron'  => '<path d="m9 6 6 6-6 6"/>',
		'check'    => '<circle cx="12" cy="12" r="10"/><path d="m8.5 12.5 2.5 2.5 5-5.5"/>',
		'alert'    => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
		'box'      => '<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
		'card'     => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
		'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'wallet'   => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
		'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15h6"/>',
		'truck'    => '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
		'copy'     => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
		'external' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
	);
	$d = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['grid'];

	return sprintf(
		'<svg class="wpl-icon wpl-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$d // Static, trusted markup.
	);
}

/**
 * Build a URL to the current panel page with WooPanel query args.
 *
 * @param array $args Extra args; pass empty-string values to remove them.
 * @return string
 */
function woopanel_panel_url( $args = array() ) {
	$base = get_permalink();
	if ( ! $base ) {
		$base = home_url( '/' );
	}
	return add_query_arg( $args, $base );
}

/**
 * Render text with Western digits kept as HTML entities.
 *
 * woopanel_localize_digits_html() skips `&#nn;` sequences, so a tracking
 * code passed through this helper always stays copy-pasteable Latin digits
 * even on a Persian locale.
 *
 * @param string $text Raw code (letters/digits/dashes).
 * @return string Escaped entity string.
 */
function woopanel_keep_latin( $text ) {
	$escaped = htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	// Single pass: array str_replace would cascade into its own inserted entities.
	return preg_replace_callback(
		'/\d/',
		function ( $m ) {
			return '&#' . ( 48 + (int) $m[0] ) . ';';
		},
		$escaped
	);
}

