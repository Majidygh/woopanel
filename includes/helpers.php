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

/* ---------------------------------------------------------------------
 * Site-theme color detection ("match my site" mode)
 * ------------------------------------------------------------------- */

/**
 * Is a hex color effectively gray (no usable brand hue)?
 *
 * @param string $hex Like #aabbcc.
 * @return bool
 */
function woopanel_is_neutral_hex( $hex ) {
	$r = hexdec( substr( $hex, 1, 2 ) );
	$g = hexdec( substr( $hex, 3, 2 ) );
	$b = hexdec( substr( $hex, 5, 2 ) );
	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	if ( $max > 240 && $min > 225 ) {
		return true; // near-white
	}
	if ( $max < 28 ) {
		return true; // near-black
	}
	return ( $max - $min ) < 24; // low chroma
}

/**
 * HSL components of a hex color (h 0-360, s/l 0-1).
 *
 * @param string $hex Like #aabbcc.
 * @return array
 */
function woopanel_hex_to_hsl( $hex ) {
	$r = hexdec( substr( $hex, 1, 2 ) ) / 255;
	$g = hexdec( substr( $hex, 3, 2 ) ) / 255;
	$b = hexdec( substr( $hex, 5, 2 ) ) / 255;
	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$l   = ( $max + $min ) / 2;
	if ( $max === $min ) {
		return array( 0, 0, $l );
	}
	$d   = $max - $min;
	$s   = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
	$hr  = ( $max === $r ) ? ( ( $g - $b ) / $d + ( $g < $b ? 6 : 0 ) ) : ( ( $max === $g ) ? ( ( $b - $r ) / $d + 2 ) : ( ( $r - $g ) / $d + 4 ) );
	return array( $hr * 60, $s, $l );
}

/**
 * Derive a soft background tint from an accent hex (same hue, pale).
 *
 * @param string $hex Like #aabbcc.
 * @return string
 */
function woopanel_derive_soft( $hex ) {
	list( $h, $s ) = woopanel_hex_to_hsl( $hex );
	$s = max( 0.28, min( 0.6, $s ) );
	// hsl -> hex at L=0.95.
	$c = $s * ( 1 - abs( 2 * 0.95 - 1 ) );
	$x = $c * ( 1 - abs( fmod( $h / 60, 2 ) - 1 ) );
	$m = 0.95 - $c / 2;
	$rgb = $h < 60 ? array( $c, $x, 0 ) : ( $h < 120 ? array( $x, $c, 0 ) : ( $h < 180 ? array( 0, $c, $x ) : ( $h < 240 ? array( 0, $x, $c ) : ( $h < 300 ? array( $x, 0, $c ) : array( $c, 0, $x ) ) ) ) );
	return sprintf( '#%02x%02x%02x', (int) round( ( $rgb[0] + $m ) * 255 ), (int) round( ( $rgb[1] + $m ) * 255 ), (int) round( ( $rgb[2] + $m ) * 255 ) );
}

/**
 * Detect the active site theme's brand color.
 *
 * Order: theme.json palette (primary > accent > secondary > first vivid),
 * then common customizer mods, then a frequency scan of the theme's own
 * stylesheets. Result cached for a day; the theme's palette is the input,
 * so a theme switch clears it.
 *
 * @return string|false Hex like #3b82f6 or false when nothing usable found.
 */
function woopanel_site_accent() {
	$theme = wp_get_theme()->get_stylesheet();
	$cache = get_transient( 'woopanel_site_accent' );
	if ( is_array( $cache ) && isset( $cache[ $theme ] ) ) {
		return $cache[ $theme ];
	}

	$found = false;

	// 1) Theme.json / global styles palette.
	if ( function_exists( 'wp_get_global_settings' ) ) {
		$palette = wp_get_global_settings( array( 'settings', 'color', 'palette' ) );
		if ( is_array( $palette ) ) {
			$pick = array();
			foreach ( $palette as $entry ) {
				if ( ! is_array( $entry ) || empty( $entry['color'] ) || empty( $entry['slug'] ) ) {
					continue;
				}
				$hex = strtolower( trim( $entry['color'] ) );
				if ( ! preg_match( '/^#[0-9a-f]{6}$/', $hex ) || woopanel_is_neutral_hex( $hex ) ) {
					continue;
				}
				$pick[ $entry['slug'] ] = $hex;
			}
			foreach ( array( 'primary', 'accent', 'brand', 'secondary', 'highlight' ) as $slug ) {
				if ( isset( $pick[ $slug ] ) ) {
					$found = $pick[ $slug ];
					break;
				}
			}
			if ( ! $found && ! empty( $pick ) ) {
				$found = reset( $pick );
			}
		}
	}

	// 2) Customizer theme mods used by popular themes.
	if ( ! $found ) {
		foreach ( array( 'accent_color', 'primary_color', 'theme_color', 'color_scheme' ) as $mod ) {
			$val = get_theme_mod( $mod, '' );
			if ( is_string( $val ) && preg_match( '/^#[0-9a-f]{6}$/i', $val ) && ! woopanel_is_neutral_hex( strtolower( $val ) ) ) {
				$found = strtolower( $val );
				break;
			}
		}
	}

	// 3) Frequency scan of the theme's CSS files (most-used vivid hex wins).
	if ( ! $found ) {
		$dir = wp_get_theme()->get_stylesheet_directory();
		$all = array();
		foreach ( array( 'style.css', 'assets/css/*.css', 'css/*.css' ) as $glob ) {
			foreach ( (array) glob( trailingslashit( $dir ) . $glob ) as $file ) {
				$css = (string) file_get_contents( $file, false, null, 0, 262144 ); // First 256KB is plenty.
				if ( preg_match_all( '/#([0-9a-fA-F]{6})\b/', $css, $m ) ) {
					foreach ( $m[1] as $hex ) {
						$all[] = '#' . strtolower( $hex );
					}
				}
			}
		}
		$counts = array();
		foreach ( $all as $hex ) {
			if ( ! woopanel_is_neutral_hex( $hex ) ) {
				$counts[ $hex ] = ( isset( $counts[ $hex ] ) ? $counts[ $hex ] : 0 ) + 1;
			}
		}
		if ( $counts ) {
			arsort( $counts );
			$found = (string) key( $counts );
		}
	}

	/**
	 * Filter the auto-detected site accent color.
	 *
	 * @param string|false $found Detected hex or false.
	 */
	$found = apply_filters( 'woopanel_site_accent', $found );

	$cache         = is_array( $cache ) ? $cache : array();
	$cache[ $theme ] = $found;
	set_transient( 'woopanel_site_accent', $cache, DAY_IN_SECONDS );
	return $found;
}

/**
 * Clear the site-accent cache when the theme changes.
 */
add_action( 'switch_theme', function () {
	delete_transient( 'woopanel_site_accent' );
} );
