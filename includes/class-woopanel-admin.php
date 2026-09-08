<?php
/**
 * Admin settings page.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings > WooPanel.
 */
class WooPanel_Admin {

	/**
	 * Register the options page.
	 */
	public static function register_menu() {
		add_options_page(
			__( 'WooPanel', 'woopanel' ),
			__( 'WooPanel', 'woopanel' ),
			'manage_options',
			'woopanel',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'woopanel',
			WooPanel_Options::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'WooPanel_Options', 'sanitize' ),
				'default'           => WooPanel_Options::defaults(),
			)
		);
	}

	/**
	 * Render the settings screen.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = WooPanel_Options::get(); // Raw values: resolved() would bake translations into the DB on save.
		$woo     = WooPanel_Data::is_woo();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooPanel', 'woopanel' ); ?></h1>

			<?php if ( ! $woo ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'WooCommerce is not active. WooPanel needs WooCommerce to display customer data.', 'woopanel' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'woopanel' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="woopanel-accent"><?php esc_html_e( 'Accent color', 'woopanel' ); ?></label></th>
						<td>
							<input type="color" id="woopanel-accent" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[accent]" value="<?php echo esc_attr( $options['accent'] ); ?>">
							<p class="description"><?php esc_html_e( 'Used for buttons, links and active states.', 'woopanel' ); ?></p>
							<p style="margin-top:10px;">
							<?php
							$presets = array(
								__( 'Royal violet', 'woopanel' ) => '#7c3aed',
								__( 'Persian turquoise', 'woopanel' ) => '#0d7a6f',
								__( 'Caspian blue', 'woopanel' ) => '#1d4ed8',
								__( 'Saffron', 'woopanel' ) => '#b45309',
								__( 'Pomegranate', 'woopanel' ) => '#be123c',
								__( 'Forest', 'woopanel' ) => '#15803d',
								__( 'Midnight', 'woopanel' ) => '#4338ca',
								__( 'Wine', 'woopanel' ) => '#9d174d',
							);
							foreach ( $presets as $label => $hex ) :
								?>
								<button type="button" class="button wpl-preset" data-hex="<?php echo esc_attr( $hex ); ?>" title="<?php echo esc_attr( $label ); ?>" style="width:34px;height:26px;padding:0;margin:0 4px 4px 0;background:<?php echo esc_attr( $hex ); ?>;border-color:<?php echo esc_attr( $hex ); ?>;">&nbsp;</button>
							<?php endforeach; ?>
							</p>
							<p class="description"><?php esc_html_e( 'One-click palettes. The soft background tint adjusts automatically.', 'woopanel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="woopanel-accent-bg"><?php esc_html_e( 'Accent background', 'woopanel' ); ?></label></th>
						<td>
							<input type="color" id="woopanel-accent-bg" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[accent_bg]" value="<?php echo esc_attr( $options['accent_bg'] ); ?>">
							<p class="description"><?php esc_html_e( 'Soft background tint for icon chips and hero.', 'woopanel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Layout width', 'woopanel' ); ?></th>
						<td>
							<fieldset>
								<label style="display:block;margin-bottom:8px;">
									<input type="radio" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[layout_mode]" value="full" <?php checked( $options['layout_mode'], 'full' ); ?>>
									<strong><?php esc_html_e( 'Full-width (Screen wide - Recommended)', 'woopanel' ); ?></strong>
									<span class="description" style="display:block;margin-right:20px;"><?php esc_html_e( 'The panel expands across the full screen width and gives your dashboard maximum breathing room without being squeezed by narrow theme containers.', 'woopanel' ); ?></span>
								</label>
								<label style="display:block;">
									<input type="radio" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[layout_mode]" value="boxed" <?php checked( $options['layout_mode'], 'boxed' ); ?>>
									<strong><?php esc_html_e( 'Boxed (Constrained to theme container)', 'woopanel' ); ?></strong>
									<span class="description" style="display:block;margin-right:20px;"><?php esc_html_e( 'The panel remains confined inside your active WordPress theme content width.', 'woopanel' ); ?></span>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="woopanel-default-theme"><?php esc_html_e( 'Default theme', 'woopanel' ); ?></label></th>
						<td>
							<select id="woopanel-default-theme" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[default_theme]">
								<option value="system" <?php selected( $options['default_theme'], 'system' ); ?>><?php esc_html_e( 'Follow system theme', 'woopanel' ); ?></option>
								<option value="light" <?php selected( $options['default_theme'], 'light' ); ?>><?php esc_html_e( 'Light theme', 'woopanel' ); ?></option>
								<option value="dark" <?php selected( $options['default_theme'], 'dark' ); ?>><?php esc_html_e( 'Dark theme', 'woopanel' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Default color mode before user selects their preference in the sidebar.', 'woopanel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Corner radius', 'woopanel' ); ?></th>
						<td>
							<label style="margin-inline-end:16px;">
								<input type="radio" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[radius_style]" value="rounded" <?php checked( $options['radius_style'], 'rounded' ); ?>>
								<?php esc_html_e( 'Rounded (18px - Modern)', 'woopanel' ); ?>
							</label>
							<label style="margin-inline-end:16px;">
								<input type="radio" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[radius_style]" value="smooth" <?php checked( $options['radius_style'], 'smooth' ); ?>>
								<?php esc_html_e( 'Smooth (10px)', 'woopanel' ); ?>
							</label>
							<label>
								<input type="radio" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[radius_style]" value="sharp" <?php checked( $options['radius_style'], 'sharp' ); ?>>
								<?php esc_html_e( 'Sharp (4px)', 'woopanel' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Dashboard stat cards', 'woopanel' ); ?></th>
						<td>
							<fieldset>
								<label style="display:block;margin-bottom:6px;">
									<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[show_card_orders]" value="1" <?php checked( ! empty( $options['show_card_orders'] ) ); ?>>
									<?php esc_html_e( 'Show orders count', 'woopanel' ); ?>
								</label>
								<label style="display:block;margin-bottom:6px;">
									<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[show_card_spent]" value="1" <?php checked( ! empty( $options['show_card_spent'] ) ); ?>>
									<?php esc_html_e( 'Show total spent', 'woopanel' ); ?>
								</label>
								<label style="display:block;margin-bottom:6px;">
									<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[show_card_avg]" value="1" <?php checked( ! empty( $options['show_card_avg'] ) ); ?>>
									<?php esc_html_e( 'Show average order', 'woopanel' ); ?>
								</label>
								<label style="display:block;">
									<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[show_card_downloads]" value="1" <?php checked( ! empty( $options['show_card_downloads'] ) ); ?>>
									<?php esc_html_e( 'Show downloads count', 'woopanel' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Order items thumbnails', 'woopanel' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[show_item_thumbs]" value="1" <?php checked( ! empty( $options['show_item_thumbs'] ) ); ?>>
								<?php esc_html_e( 'Show product images in orders list and invoice', 'woopanel' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="woopanel-tracking-url"><?php esc_html_e( 'Tracking URL template', 'woopanel' ); ?></label></th>
						<td>
							<input type="text" class="large-text" id="woopanel-tracking-url" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[tracking_url]" value="<?php echo esc_attr( $options['tracking_url'] ); ?>">
							<p class="description"><?php esc_html_e( 'Use %s where the tracking barcode should be inserted. Defaults to Iran Post (https://tracking.post.ir/?id=%s).', 'woopanel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="woopanel-title"><?php esc_html_e( 'Panel title', 'woopanel' ); ?></label></th>
						<td><input type="text" class="regular-text" id="woopanel-title" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[panel_title]" value="<?php echo esc_attr( $options['panel_title'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="woopanel-welcome"><?php esc_html_e( 'Welcome word', 'woopanel' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="woopanel-welcome" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[welcome_text]" value="<?php echo esc_attr( $options['welcome_text'] ); ?>">
							<p class="description"><?php esc_html_e( 'Shown before the user name on the dashboard, e.g. Hello.', 'woopanel' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="woopanel-per-page"><?php esc_html_e( 'Orders per page', 'woopanel' ); ?></label></th>
						<td>
							<input type="number" min="1" max="50" id="woopanel-per-page" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[orders_per_page]" value="<?php echo esc_attr( $options['orders_per_page'] ); ?>" class="small-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'My Account integration', 'woopanel' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[replace_dashboard]" value="0">
								<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[replace_dashboard]" value="1" <?php checked( $options['replace_dashboard'], 1 ); ?>>
								<?php esc_html_e( 'Replace the WooCommerce My Account area with WooPanel (on by default). Orders, downloads, addresses and account pages all render the panel; theme side-menus are hidden automatically.', 'woopanel' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Usage', 'woopanel' ); ?></h2>
			<p><?php esc_html_e( 'Add this shortcode to any page:', 'woopanel' ); ?> <code>[woopanel]</code></p>
			<p><?php esc_html_e( 'Open a specific view:', 'woopanel' ); ?> <code>[woopanel view="orders"]</code></p>
		</div>
		<script>
		(function () {
			var accent = document.getElementById('woopanel-accent');
			var bg = document.getElementById('woopanel-accent-bg');
			if (!accent || !bg) return;
			function soft(hex) {
				var n = hex.replace('#', '');
				var r = parseInt(n.slice(0, 2), 16), g = parseInt(n.slice(2, 4), 16), b = parseInt(n.slice(4, 6), 16);
				function f(c) { return Math.round(c + (255 - c) * 0.9); }
				function h(c) { return ('0' + f(c).toString(16)).slice(-2); }
				return '#' + h(r) + h(g) + h(b);
			}
			// Preset swatches: set accent + derived tint.
			document.querySelectorAll('.wpl-preset').forEach(function (btn) {
				btn.addEventListener('click', function () {
					accent.value = btn.getAttribute('data-hex');
					bg.value = soft(accent.value);
					accent.dispatchEvent(new Event('input', { bubbles: true }));
				});
			});
			// Keep the tint in sync when the picker changes.
			accent.addEventListener('input', function () { bg.value = soft(accent.value); });
		})();
		</script>
		<?php
	}
}
