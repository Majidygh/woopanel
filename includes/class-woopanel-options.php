<?php
/**
 * Plugin options.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * Options storage and defaults.
 */
class WooPanel_Options {

	const OPTION_KEY = 'woopanel_options';

	/**
	 * Option defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'accent'            => '#7c3aed',
			'accent_bg'         => '#f5f3ff',
			'replace_dashboard' => 0,
			'orders_per_page'   => 8,
			'panel_title'       => __( 'My Panel', 'woopanel' ),
			'welcome_text'      => __( 'Hello', 'woopanel' ),
		);
	}

	/**
	 * Get merged options.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Insert default options on activation.
	 */
	public static function add_default_options() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
		}
	}

	/**
	 * Sanitize callback for register_setting.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$out = array();
		foreach ( $defaults as $key => $default ) {
			if ( ! array_key_exists( $key, $input ) ) {
				$out[ $key ] = $default;
				continue;
			}
			switch ( $key ) {
				case 'accent':
				case 'accent_bg':
					$out[ $key ] = WooPanel_Render::sanitize_hex_color( $input[ $key ], $default );
					break;
				case 'replace_dashboard':
					$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
					break;
				case 'orders_per_page':
					$out[ $key ] = min( 50, max( 1, absint( $input[ $key ] ) ) );
					break;
				default:
					$out[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}
		return $out;
	}
}
