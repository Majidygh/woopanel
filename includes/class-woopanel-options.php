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
		/**
		 * Empty-string defaults for text options mean "use the translated
		 * default at render time" — keeps panel_title/welcome_text translatable
		 * even after the options row was first written under another locale.
		 */
		return array(
			'accent'            => '#7c3aed',
			'accent_bg'         => '#f5f3ff',
			'replace_dashboard' => 0,
			'orders_per_page'   => 8,
			'panel_title'       => '',
			'welcome_text'      => '',
		);
	}

	/**
	 * Resolved option getter: empty text options fall back to translations.
	 *
	 * @return array
	 */
	public static function get_resolved() {
		$options = self::get();
		if ( '' === trim( (string) $options['panel_title'] ) ) {
			$options['panel_title'] = __( 'My Panel', 'woopanel' );
		}
		if ( '' === trim( (string) $options['welcome_text'] ) ) {
			$options['welcome_text'] = __( 'Hello', 'woopanel' );
		}
		return $options;
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
