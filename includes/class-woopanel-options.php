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
			'accent'            => '#0d7a6f',
			'accent_bg'         => '#e4f2ef',
			'accent_mode'       => 'custom', // 'custom' | 'auto' (follow site theme)
			'replace_dashboard' => 1,
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
		self::maybe_upgrade();
	}

	/**
	 * One-time default swaps across versions.
	 *
	 * v1.0 shipped a purple default accent. Existing installs still carry it
	 * in the DB, so a plain defaults() change never reaches them. We only
	 * replace the color when it is byte-for-byte the old shipped default —
	 * a store owner who picked a custom accent is left untouched.
	 */
	public static function maybe_upgrade() {
		$version = (string) get_option( 'woopanel_theme_default_version', '' );
		$saved   = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$dirty = false;

		if ( '2.0' !== $version ) {
			$accent = isset( $saved['accent'] ) ? strtolower( trim( (string) $saved['accent'] ) ) : '';
			$bg     = isset( $saved['accent_bg'] ) ? strtolower( trim( (string) $saved['accent_bg'] ) ) : '';
			// Shipped defaults across versions: v1.0 purple, v1.2 brick.
			$legacy = array( '#7c3aed' => '#f5f3ff', '#9a3412' => '#fbf1ea' );
			if ( isset( $legacy[ $accent ] ) && $legacy[ $accent ] === $bg ) {
				$saved['accent']    = '#0d7a6f';
				$saved['accent_bg'] = '#e4f2ef';
				$dirty = true;
			}
		}

		if ( version_compare( $version, '3.0', '<' ) ) {
			// v1.4: takeover became the product's core promise. A stored 0
			// predates the feature being on-by-default — it was never an
			// explicit opt-out, so flip it once. Turning it off afterwards
			// sticks (marker already at 3.0).
			if ( array_key_exists( 'replace_dashboard', $saved ) && empty( $saved['replace_dashboard'] ) ) {
				$saved['replace_dashboard'] = 1;
				$dirty = true;
			}
		}

		if ( $dirty ) {
			update_option( self::OPTION_KEY, $saved );
		}
		if ( '3.0' !== $version ) {
			update_option( 'woopanel_theme_default_version', '3.0' );
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
				case 'accent_mode':
					$out[ $key ] = in_array( $input[ $key ], array( 'custom', 'auto' ), true ) ? $input[ $key ] : 'custom';
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
