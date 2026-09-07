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
		$options = WooPanel_Options::get();
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
								<input type="checkbox" name="<?php echo esc_attr( WooPanel_Options::OPTION_KEY ); ?>[replace_dashboard]" value="1" <?php checked( $options['replace_dashboard'], 1 ); ?>>
								<?php esc_html_e( 'Replace the default WooCommerce My Account dashboard area with WooPanel (endpoints keep working).', 'woopanel' ); ?>
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
		<?php
	}
}
