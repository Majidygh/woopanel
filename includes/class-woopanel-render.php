<?php
/**
 * HTML rendering + front-end assets.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * Panel rendering.
 */
class WooPanel_Render {

	/**
	 * Sanitize a hex color with a fallback (native sanitize_hex_color dies outside customize).
	 *
	 * @param string $color   Raw color.
	 * @param string $default Fallback.
	 * @return string
	 */
	public static function sanitize_hex_color( $color, $default = '#7c3aed' ) {
		$color = is_string( $color ) ? trim( $color ) : '';
		if ( preg_match( '/^#([A-Fa-f0-9]{3})$/', $color ) ) {
			// Expand shorthand (#abc -> #aabbcc) so fixed-offset RGB parsing stays valid.
			return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
		}
		if ( preg_match( '/^#[A-Fa-f0-9]{6}$/', $color ) ) {
			return $color;
		}
		return $default;
	}

	/**
	 * Enqueue front-end styles on panel pages only.
	 */
	public static function enqueue_assets() {
		if ( ! self::is_panel_page() ) {
			return;
		}
		wp_enqueue_style( 'woopanel', WOOPANEL_URL . 'assets/woopanel.css', array(), WOOPANEL_VERSION );
		wp_style_add_data( 'woopanel', 'rtl', 'replace' );

		$options = woopanel_get_options();
		$accent  = self::sanitize_hex_color( $options['accent'], '#7c3aed' );
		$bg      = self::sanitize_hex_color( $options['accent_bg'], '#f5f3ff' );

		$css = sprintf(
			'.wpl-panel{--wpl-accent:%1$s;--wpl-accent-soft:%2$s;--wpl-accent-rgb:%3$d %4$d %5$d;}',
			$accent,
			$bg,
			hexdec( substr( $accent, 1, 2 ) ),
			hexdec( substr( $accent, 3, 2 ) ),
			hexdec( substr( $accent, 5, 2 ) )
		);
		wp_add_inline_style( 'woopanel', $css );

		wp_enqueue_script( 'woopanel', WOOPANEL_URL . 'assets/woopanel.js', array(), WOOPANEL_VERSION, true );

		// Reuse WooCommerce's own country→state dropdown logic on the address forms.
		if ( wp_script_is( 'wc-country-select', 'registered' ) ) {
			wp_enqueue_script( 'wc-country-select' );
		}
	}

	/**
	 * Is the current page a WooPanel page?
	 *
	 * @return bool
	 */
	public static function is_panel_page() {
		return is_singular() && ( has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'woopanel' ) || self::is_my_account_takeover() );
	}

	/**
	 * True on the WooCommerce My Account pages served by the WooPanel takeover
	 * (dashboard and every routed endpoint).
	 *
	 * @return bool
	 */
	private static function is_my_account_takeover() {
		$options = woopanel_get_options();
		if ( empty( $options['replace_dashboard'] )
			|| ! function_exists( 'is_account_page' )
			|| ! is_account_page() ) {
			return false;
		}
		// Logged-out visitors see Woo's own login/register on the account page.
		if ( ! is_user_logged_in() ) {
			return false;
		}
		// Endpoints WooPanel doesn't own (view-order, wishlists, …) stay Woo-native.
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
			// WooPanel-native views reachable via query string on the account
			// page (e.g. ?woopanel_view=tracking) are panel pages too.
			if ( isset( $_GET['woopanel_view'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation state.
				$wv = sanitize_key( wp_unslash( $_GET['woopanel_view'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( in_array( $wv, array( 'dashboard', 'orders', 'downloads', 'address', 'account', 'order', 'tracking' ), true ) ) {
					return true;
				}
			}
			global $wp;
			$map = class_exists( 'WooPanel_Takeover' ) ? WooPanel_Takeover::endpoint_map() : array();
			unset( $map[''] );
			foreach ( array_keys( (array) $wp->query_vars ) as $key ) {
				if ( isset( $map[ $key ] ) ) {
					return true;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * Render the whole panel for a view.
	 *
	 * @param string $view    View slug.
	 * @param array  $atts    Shortcode attributes.
	 * @param string $context 'shortcode' or 'takeover'.
	 * @return string
	 */
	public static function panel( $view, $atts = array(), $context = 'shortcode' ) {
		if ( ! WooPanel_Data::require_woo() ) {
			return '<div class="wpl-panel"><div class="wpl-alert wpl-alert--info">' . esc_html__( 'WooPanel requires WooCommerce to be installed and active.', 'woopanel' ) . '</div></div>';
		}

		if ( ! is_user_logged_in() ) {
			return self::login_prompt( $atts );
		}

		$options = woopanel_get_options();
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		$msg     = isset( $_GET['woopanel_msg'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice code.
		$notice  = $msg ? woopanel_notice_for( $msg ) : array( 'info', '' );

		$layout_mode   = isset( $options['layout_mode'] ) ? $options['layout_mode'] : 'full';
		$radius_style  = isset( $options['radius_style'] ) ? $options['radius_style'] : 'rounded';
		$default_theme = isset( $options['default_theme'] ) ? $options['default_theme'] : 'system';
		$panel_classes = array(
			'wpl-panel',
			'wpl-panel--' . sanitize_html_class( $context ),
			'wpl-panel--layout-' . sanitize_html_class( $layout_mode ),
			'wpl-panel--radius-' . sanitize_html_class( $radius_style ),
		);
		if ( 'full' === $layout_mode ) {
			$panel_classes[] = 'wpl-panel--full-width';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $panel_classes ) ); ?>" data-woopanel data-wpl-layout="<?php echo esc_attr( $layout_mode ); ?>" data-wpl-radius="<?php echo esc_attr( $radius_style ); ?>" data-wpl-default-theme="<?php echo esc_attr( $default_theme ); ?>">
			<div class="wpl-shell">
				<?php self::sidebar( $view, $user, $options, $user_id ); ?>
				<main class="wpl-main">
					<div class="wpl-main__top">
						<div class="wpl-main__titles">
							<h2 class="wpl-main__title">
							<?php
							$nav = WooPanel_Data::nav_items( $view, $user_id );
							foreach ( $nav as $item ) {
								if ( $item['active'] ) {
									echo esc_html( $item['label'] );
								}
							}
							?>
							</h2>
						</div>
					</div>

					<?php if ( '' !== $notice[1] ) : ?>
						<div class="wpl-alert wpl-alert--<?php echo esc_attr( $notice[0] ); ?>">
							<span class="wpl-alert__icon"><?php echo woopanel_icon( 'success' === $notice[0] ? 'check' : 'alert', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="wpl-alert__msg"><?php echo esc_html( $notice[1] ); ?></span>
						</div>
					<?php endif; ?>

					<?php
					//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- views escape internally.
					echo self::view( $view, $user_id, $user, $options );
					?>
				</main>
			</div>
		</div>
		<?php
		$html = (string) ob_get_clean();
		// Persian/Arabic digit shaping for RTL locales (text nodes only).
		return woopanel_localize_digits_html( $html );
	}

	/**
	 * Sidebar navigation.
	 *
	 * @param string   $view    Current view.
	 * @param WP_User  $user    Current user.
	 * @param array    $options Options.
	 * @param int      $user_id Current user ID.
	 */
	private static function sidebar( $view, $user, $options, $user_id = 0 ) {
		$display   = $user ? ( $user->display_name ? $user->display_name : $user->user_login ) : '';
		$email     = $user ? $user->user_email : '';
		$nav_items = WooPanel_Data::nav_items( $view, $user_id );
		?>
		<aside class="wpl-sidebar">
			<div class="wpl-sidebar__brand">
				<div class="wpl-brand">
					<div class="wpl-brand__logo" aria-hidden="true"><?php echo esc_html( mb_substr( trim( (string) $options['panel_title'] ), 0, 1, 'UTF-8' ) ?: 'W' ); ?></div>
					<div class="wpl-brand__text">
						<span class="wpl-brand__title"><?php echo esc_html( $options['panel_title'] ); ?></span>
						<span class="wpl-brand__badge"><?php esc_html_e( 'Customer Portal', 'woopanel' ); ?></span>
					</div>
				</div>
			</div>

			<nav class="wpl-nav">
				<div class="wpl-nav__list">
					<?php foreach ( $nav_items as $item ) : ?>
						<a class="wpl-nav__item<?php echo $item['active'] ? ' wpl-nav__item--active' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>"
							href="<?php echo esc_url( $item['url'] ); ?>">
							<span class="wpl-nav__icon"><?php echo woopanel_icon( $item['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="wpl-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
							<?php if ( ! empty( $item['badge'] ) ) : ?>
								<span class="wpl-nav__badge"><?php echo esc_html( woopanel_localize_digits( $item['badge'] ) ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			</nav>

			<div class="wpl-sidebar__footer">
				<div class="wpl-theme-wrap">
					<div class="wpl-theme" data-wpl-theme role="group" aria-label="<?php esc_attr_e( 'Panel appearance', 'woopanel' ); ?>">
						<button type="button" class="wpl-theme__btn" data-wpl-theme-set="light" title="<?php esc_attr_e( 'Light theme', 'woopanel' ); ?>" aria-label="<?php esc_attr_e( 'Light theme', 'woopanel' ); ?>"><?php echo woopanel_icon( 'sun', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Light', 'woopanel' ); ?></span></button>
						<button type="button" class="wpl-theme__btn" data-wpl-theme-set="system" title="<?php esc_attr_e( 'System theme', 'woopanel' ); ?>" aria-label="<?php esc_attr_e( 'System theme', 'woopanel' ); ?>"><?php echo woopanel_icon( 'monitor', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Auto', 'woopanel' ); ?></span></button>
						<button type="button" class="wpl-theme__btn" data-wpl-theme-set="dark" title="<?php esc_attr_e( 'Dark theme', 'woopanel' ); ?>" aria-label="<?php esc_attr_e( 'Dark theme', 'woopanel' ); ?>"><?php echo woopanel_icon( 'moon', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Dark', 'woopanel' ); ?></span></button>
					</div>
				</div>

				<div class="wpl-usercard">
					<div class="wpl-usercard__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $display, 0, 1, 'UTF-8' ) ); ?></div>
					<div class="wpl-usercard__info">
						<span class="wpl-usercard__name"><?php echo esc_html( $display ); ?></span>
						<span class="wpl-usercard__email"><?php echo esc_html( $email ); ?></span>
					</div>
					<a class="wpl-usercard__logout" title="<?php esc_attr_e( 'Log out', 'woopanel' ); ?>" aria-label="<?php esc_attr_e( 'Log out', 'woopanel' ); ?>" href="<?php echo esc_url( function_exists( 'wc_logout_url' ) ? wc_logout_url( woopanel_panel_url() ) : wp_logout_url( woopanel_panel_url() ) ); ?>">
						<?php echo woopanel_icon( 'logout', 17 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</div>
			</div>
		</aside>
		<?php
	}

	/**
	 * Render one view by slug.
	 *
	 * @param string  $view    View slug.
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view( $view, $user_id, $user, $options ) {
		$method = 'view_' . str_replace( '-', '_', $view );
		if ( ! method_exists( __CLASS__, $method ) ) {
			$method = 'view_dashboard';
		}
		/**
		 * Filter the rendered view HTML.
		 *
		 * @param string $html   View output.
		 * @param string $view   View slug.
		 * @param int    $user_id Current user ID.
		 */
		return apply_filters( 'woopanel_view_html', self::{$method}( $user_id, $user, $options ), $view, $user_id );
	}

	/**
	 * Login prompt for guests.
	 *
	 * @param array $atts Shortcode atts.
	 * @return string
	 */
	private static function login_prompt( $atts ) {
		$redirect = ! empty( $atts['redirect'] ) ? esc_url_raw( $atts['redirect'] ) : woopanel_panel_url();
		$account  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url( $redirect );

		ob_start();
		?>
		<div class="wpl-panel"><div class="wpl-login">
			<div class="wpl-login__icon"><?php echo woopanel_icon( 'user', 28 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<h3 class="wpl-login__title"><?php esc_html_e( 'Login required', 'woopanel' ); ?></h3>
			<p class="wpl-login__text"><?php esc_html_e( 'Please log in to view your panel.', 'woopanel' ); ?></p>
			<a class="wpl-btn" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'Log in', 'woopanel' ); ?></a>
		</div></div>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------------------
	 * Views
	 * ------------------------------------------------------------------- */

	/**
	 * Dashboard view.
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_dashboard( $user_id, $user, $options ) {
		$stats    = WooPanel_Data::get_stats( $user_id );
		$orders   = WooPanel_Data::order_rows( WooPanel_Data::get_orders( $user_id, 5 ) );
		$welcome  = $options['welcome_text'];
		$tracking = WooPanel_Data::get_tracking( $user_id, 1 );
		$display  = $user ? ( $user->display_name ? $user->display_name : $user->user_login ) : '';

		$cards = array();
		if ( ! empty( $options['show_card_orders'] ) ) {
			$cards[] = array(
				'icon'  => 'bag',
				'label' => __( 'Orders', 'woopanel' ),
				'value' => number_format_i18n( $stats['orders_count'] ),
				'hint'  => __( 'Total placed orders', 'woopanel' ),
			);
		}
		if ( ! empty( $options['show_card_spent'] ) ) {
			$cards[] = array(
				'icon'  => 'wallet',
				'label' => __( 'Total spent', 'woopanel' ),
				'value' => function_exists( 'wc_price' ) ? wc_price( $stats['total_spent'] ) : $stats['total_spent'],
				'hint'  => __( 'Successful payments', 'woopanel' ),
			);
		}
		if ( ! empty( $options['show_card_avg'] ) ) {
			$cards[] = array(
				'icon'  => 'card',
				'label' => __( 'Average order', 'woopanel' ),
				'value' => function_exists( 'wc_price' ) ? wc_price( $stats['avg_order'] ) : $stats['avg_order'],
				'hint'  => __( 'Per purchase value', 'woopanel' ),
			);
		}
		if ( ! empty( $options['show_card_downloads'] ) ) {
			$cards[] = array(
				'icon'  => 'file',
				'label' => __( 'Available downloads', 'woopanel' ),
				'value' => number_format_i18n( $stats['downloads'] ),
				'hint'  => __( 'Digital assets', 'woopanel' ),
			);
		}

		ob_start();
		?>
		<div class="wpl-welcome-card">
			<div class="wpl-welcome-card__content">
				<div class="wpl-welcome-card__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $display, 0, 1, 'UTF-8' ) ); ?></div>
				<div class="wpl-welcome-card__text">
					<h3 class="wpl-welcome-card__greeting">
						<?php echo wp_kses( esc_html( $welcome ) . '، <bdi>' . esc_html( $display ) . '</bdi>', array( 'bdi' => array() ) ); ?>
					</h3>
					<p class="wpl-welcome-card__sub"><?php esc_html_e( 'Here is a summary of your account.', 'woopanel' ); ?></p>
				</div>
			</div>
			<div class="wpl-welcome-card__badge">
				<span class="wpl-pulse-dot" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Customer Portal', 'woopanel' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $tracking ) ) : $track = $tracking[0]; ?>
			<div class="wpl-track-banner">
				<div class="wpl-track-banner__icon"><?php echo woopanel_icon( 'truck', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="wpl-track-banner__info">
					<span class="wpl-track-banner__title">
						<?php
						printf(
							/* translators: %s: order number link */
							esc_html__( 'Shipment for order %s is in transit', 'woopanel' ),
							'<a class="wpl-orderlink" href="' . esc_url( woopanel_panel_url( array( 'woopanel_view' => 'order', 'woopanel_order' => (string) $track['order_id'] ) ) ) . '">#' . esc_html( woopanel_localize_digits( $track['number'] ) ) . '</a>'
						);
						?>
					</span>
					<div class="wpl-track-banner__meta">
						<span class="wpl-track-banner__label"><?php esc_html_e( 'Tracking code', 'woopanel' ); ?>:</span>
						<code class="wpl-code-pill" dir="ltr"><?php echo woopanel_keep_latin( $track['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></code>
					</div>
				</div>
				<div class="wpl-track-banner__actions">
					<button type="button" class="wpl-btn wpl-btn--xs wpl-copycode" data-wpl-copy="<?php echo esc_attr( $track['code'] ); ?>">
						<?php echo woopanel_icon( 'copy', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span class="wpl-copy-text" data-done-text="<?php esc_attr_e( 'Copied!', 'woopanel' ); ?>"><?php esc_html_e( 'Copy', 'woopanel' ); ?></span>
					</button>
					<a class="wpl-btn wpl-btn--xs" href="<?php echo esc_url( $track['url'] ); ?>" target="_blank" rel="noopener">
						<?php echo woopanel_icon( 'external', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php esc_html_e( 'Track shipment', 'woopanel' ); ?></span>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<div class="wpl-cards">
			<?php foreach ( $cards as $card ) : ?>
				<div class="wpl-card">
					<div class="wpl-card__icon"><?php echo woopanel_icon( $card['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="wpl-card__body">
						<span class="wpl-card__label"><?php echo esc_html( $card['label'] ); ?></span>
						<span class="wpl-card__value"><?php echo wp_kses_post( $card['value'] ); ?></span>
						<span class="wpl-card__hint"><?php echo esc_html( $card['hint'] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wpl-section">
			<div class="wpl-section__head">
				<div class="wpl-section__title-group">
					<span class="wpl-section__icon"><?php echo woopanel_icon( 'bag', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h4><?php esc_html_e( 'Recent orders', 'woopanel' ); ?></h4>
				</div>
				<a class="wpl-link" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'orders' ) ) ); ?>">
					<span><?php esc_html_e( 'View all', 'woopanel' ); ?></span>
					<?php echo woopanel_icon( 'chevron', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			</div>
			<?php echo self::orders_table( $orders ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Orders view (list + filter + pagination).
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_orders( $user_id, $user, $options ) {
		$per_page = max( 1, (int) $options['orders_per_page'] );
		$status   = isset( $_GET['woopanel_status'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['woopanel_paged'] ) ? max( 1, absint( $_GET['woopanel_paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = array();
		if ( $status && array_key_exists( 'wc-' . $status, wc_get_order_statuses() ) ) {
			$query_args['status'] = $status;
		}

		$total  = WooPanel_Data::count_orders( $user_id, $query_args );
		$pages  = max( 1, (int) ceil( $total / $per_page ) );
		$paged  = min( $paged, $pages );
		$orders = WooPanel_Data::order_rows(
			WooPanel_Data::get_orders( $user_id, $per_page, array_merge( $query_args, array( 'offset' => ( $paged - 1 ) * $per_page ) ) )
		);

		$filters = array(
			''            => __( 'All', 'woopanel' ),
			'processing'  => woopanel_status_label( 'processing' ),
			'completed'   => woopanel_status_label( 'completed' ),
			'on-hold'     => woopanel_status_label( 'on-hold' ),
			'cancelled'   => woopanel_status_label( 'cancelled' ),
		);

		ob_start();
		?>
		<div class="wpl-filters">
			<?php foreach ( $filters as $slug => $label ) : ?>
				<?php $furl = woopanel_panel_url( array( 'woopanel_view' => 'orders', 'woopanel_status' => '' === $slug ? false : $slug, 'woopanel_paged' => false ) ); ?>
				<a class="wpl-chip<?php echo ( $status === $slug ) ? ' wpl-chip--active' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>" href="<?php echo esc_url( $furl ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>

		<?php echo self::orders_table( $orders ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( $pages > 1 ) : ?>
			<div class="wpl-pager">
				<?php
				for ( $p = 1; $p <= $pages; $p++ ) {
					$purl   = woopanel_panel_url( array( 'woopanel_view' => 'orders', 'woopanel_status' => $status, 'woopanel_paged' => (string) $p ) );
					$active = ( $p === $paged ) ? ' wpl-pager__page--active' : '';
					printf(
						'<a class="wpl-pager__page%1$s" href="%2$s">%3$s</a>',
						esc_attr( $active ),
						esc_url( $purl ),
						esc_html( number_format_i18n( $p ) )
					);
				}
				?>
			</div>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Orders table markup with item thumbnails and view actions.
	 *
	 * @param array[] $orders Prepared rows.
	 * @return string
	 */
	private static function orders_table( $orders ) {
		ob_start();
		if ( empty( $orders ) ) {
			$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
			?>
			<div class="wpl-empty">
				<div class="wpl-empty__icon"><?php echo woopanel_icon( 'box', 36 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<h4 class="wpl-empty__title"><?php esc_html_e( 'No orders found.', 'woopanel' ); ?></h4>
				<p class="wpl-empty__text"><?php esc_html_e( 'You have not placed any orders yet. Visit our store to find what you need.', 'woopanel' ); ?></p>
				<a class="wpl-btn wpl-btn--sm" href="<?php echo esc_url( $shop_url ); ?>"><?php echo woopanel_icon( 'store', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php esc_html_e( 'Go to shop', 'woopanel' ); ?></span></a>
			</div>
			<?php
			return (string) ob_get_clean();
		}
		?>
		<div class="wpl-table-wrap">
			<table class="wpl-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Items', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Date', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Status', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Total', 'woopanel' ); ?></th>
						<th class="wpl-table__action-col"><?php esc_html_e( 'Action', 'woopanel' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $row ) : ?>
						<tr>
							<td class="wpl-table__order-id">
								<a class="wpl-orderlink" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'order', 'woopanel_order' => (string) $row['id'] ) ) ); ?>">
									#<?php echo esc_html( woopanel_localize_digits( $row['number'] ) ); ?>
								</a>
							</td>
							<td class="wpl-table__items">
								<div class="wpl-items-preview">
									<?php
									$thumbs_shown = 0;
									if ( ! empty( $row['items'] ) ) {
										foreach ( $row['items'] as $it ) {
											if ( ! empty( $it['thumb'] ) && $thumbs_shown < 3 ) {
												echo '<span class="wpl-thumb-wrap" title="' . esc_attr( $it['name'] ) . '">' . $it['thumb'] . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
												$thumbs_shown++;
											}
										}
									}
									if ( $thumbs_shown === 0 ) {
										echo '<span class="wpl-items-count">' . sprintf( /* translators: %d: count */ esc_html__( '%d item(s)', 'woopanel' ), (int) $row['item_count'] ) . '</span>';
									} elseif ( count( $row['items'] ) > $thumbs_shown ) {
										echo '<span class="wpl-thumb-more">+' . esc_html( woopanel_localize_digits( count( $row['items'] ) - $thumbs_shown ) ) . '</span>';
									}
									?>
								</div>
							</td>
							<td><?php echo esc_html( $row['date'] ); ?></td>
							<td><span class="wpl-badge <?php echo esc_attr( $row['status_class'] ); ?>"><?php echo esc_html( $row['status_label'] ); ?></span></td>
							<td class="wpl-table__total"><?php echo wp_kses_post( $row['total'] ); ?></td>
							<td class="wpl-table__action-col">
								<a class="wpl-btn wpl-btn--ghost wpl-btn--xs" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'order', 'woopanel_order' => (string) $row['id'] ) ) ); ?>">
									<?php esc_html_e( 'View', 'woopanel' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Single order detail view.
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_order( $user_id, $user, $options ) {
		$oid = isset( $_GET['woopanel_order'] ) ? absint( $_GET['woopanel_order'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = $oid ? wc_get_order( $oid ) : false;

		// Ownership gate: the order must belong to this user, full stop.
		if ( ! $order || (int) $order->get_user_id() !== $user_id ) {
			return '<div class="wpl-alert wpl-alert--error">' . esc_html__( 'Order not found.', 'woopanel' ) . '</div>';
		}

		$status = $order->get_status();
		$code   = self::order_tracking_code( $order );
		ob_start();
		?>
		<div class="wpl-order-topbar">
			<a class="wpl-back" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'orders', 'woopanel_status' => false, 'woopanel_paged' => false ) ) ); ?>">
				<?php echo woopanel_icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php esc_html_e( 'Back to orders', 'woopanel' ); ?></span>
			</a>
			<button type="button" class="wpl-btn wpl-btn--ghost wpl-btn--xs" data-wpl-print>
				<?php echo woopanel_icon( 'printer', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php esc_html_e( 'Print invoice', 'woopanel' ); ?></span>
			</button>
		</div>

		<div class="wpl-orderhead">
			<div class="wpl-orderhead__main">
				<span class="wpl-orderhead__badge-row">
					<span class="wpl-badge <?php echo esc_attr( woopanel_status_class( $status ) ); ?>">
						<?php echo esc_html( woopanel_status_label( $status ) ); ?>
					</span>
				</span>
				<h3 class="wpl-orderhead__num">
					<?php
					printf(
						/* translators: %s: order number */
						esc_html__( 'Order #%s', 'woopanel' ),
						esc_html( woopanel_localize_digits( $order->get_order_number() ) )
					);
					?>
				</h3>
				<p class="wpl-orderhead__date">
					<?php echo woopanel_icon( 'calendar', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '' ); ?></span>
				</p>
			</div>
			<div class="wpl-orderhead__total">
				<span class="wpl-orderhead__total-label"><?php esc_html_e( 'Total amount', 'woopanel' ); ?></span>
				<span class="wpl-orderhead__total-val"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
			</div>
		</div>

		<?php if ( '' !== $code ) : ?>
			<div class="wpl-trackcard wpl-trackcard--prominent">
				<div class="wpl-trackcard__icon"><?php echo woopanel_icon( 'truck', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="wpl-trackcard__body">
					<span class="wpl-trackcard__label"><?php esc_html_e( 'Tracking code', 'woopanel' ); ?>:</span>
					<code class="wpl-trackcard__code" dir="ltr"><?php echo woopanel_keep_latin( $code ); // phpcs:ignore WordPress.Security.EscapeOutput ?></code>
				</div>
				<div class="wpl-trackcard__actions">
					<button type="button" class="wpl-btn wpl-btn--sm wpl-copycode" data-wpl-copy="<?php echo esc_attr( $code ); ?>">
						<?php echo woopanel_icon( 'copy', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span class="wpl-copy-text" data-done-text="<?php esc_attr_e( 'Copied!', 'woopanel' ); ?>"><?php esc_html_e( 'Copy', 'woopanel' ); ?></span>
					</button>
					<?php
					$tpl       = ! empty( $options['tracking_url'] ) ? $options['tracking_url'] : 'https://tracking.post.ir/?id=%s';
					$track_url = ( false !== strpos( $tpl, '%s' ) ) ? sprintf( $tpl, rawurlencode( $code ) ) : $tpl . rawurlencode( $code );
					?>
					<a class="wpl-btn wpl-btn--sm wpl-trackcard__go" href="<?php echo esc_url( $track_url ); ?>" target="_blank" rel="noopener">
						<?php echo woopanel_icon( 'external', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php esc_html_e( 'Track shipment', 'woopanel' ); ?></span>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<div class="wpl-table-wrap">
			<table class="wpl-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Quantity', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Total', 'woopanel' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $order->get_items() as $item ) :
						$product = $item->get_product();
						$thumb   = $product ? $product->get_image( array( 44, 44 ), array( 'class' => 'wpl-item-thumb', 'alt' => esc_attr( $item->get_name() ) ) ) : '';
						?>
						<tr>
							<td class="wpl-table__product-cell">
								<div class="wpl-item-meta">
									<?php if ( $thumb ) : ?>
										<span class="wpl-thumb-wrap"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<?php endif; ?>
									<div class="wpl-item-desc">
										<span class="wpl-item-name"><?php echo esc_html( $item->get_name() ); ?></span>
										<?php
										$meta_data = $item->get_formatted_meta_data( '' );
										if ( ! empty( $meta_data ) ) {
											echo '<span class="wpl-item-variations">';
											foreach ( $meta_data as $meta ) {
												echo esc_html( $meta->display_key . ': ' . wp_strip_all_tags( $meta->display_value ) ) . ' ';
											}
											echo '</span>';
										}
										?>
									</div>
								</div>
							</td>
							<td><span class="wpl-qty-badge"><?php echo esc_html( number_format_i18n( $item->get_quantity() ) ); ?></span></td>
							<td class="wpl-table__total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="wpl-orderfoot">
			<div class="wpl-orderfoot__totals">
				<?php
				$totals = array(
					array( __( 'Subtotal', 'woopanel' ), $order->get_subtotal_to_display() ),
					array( __( 'Shipping', 'woopanel' ), $order->get_shipping_total() > 0 || $order->get_shipping_tax() > 0 ? wc_price( $order->get_shipping_total() + $order->get_shipping_tax(), array( 'currency' => $order->get_currency() ) ) : '' ),
					array( __( 'Tax', 'woopanel' ), (float) $order->get_total_tax() > 0 ? wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) : '' ),
				);
				$discount = $order->get_total_discount();
				if ( $discount > 0 ) {
					$totals[] = array( __( 'Discount', 'woopanel' ), '-' . wc_price( $discount, array( 'currency' => $order->get_currency() ) ) );
				}
				$totals[] = array( __( 'Total', 'woopanel' ), $order->get_formatted_order_total() );
				foreach ( $totals as $row ) :
					if ( '' === $row[1] || '0.00' === (string) $row[1] ) {
						continue;
					}
					$is_total = __( 'Total', 'woopanel' ) === $row[0];
					?>
					<div class="wpl-orderfoot__row<?php echo $is_total ? ' wpl-orderfoot__row--total' : ''; ?>">
						<span><?php echo esc_html( $row[0] ); ?></span>
						<strong><?php echo wp_kses_post( $row[1] ); ?></strong>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="wpl-addrgroup">
			<?php
			foreach ( array(
				'billing'  => __( 'Billing address', 'woopanel' ),
				'shipping' => __( 'Shipping address', 'woopanel' ),
			) as $type => $label ) :
				$addr = 'billing' === $type ? $order->get_address( 'billing' ) : $order->get_address( 'shipping' );
				if ( empty( array_filter( $addr ) ) ) {
					continue;
				}
				?>
				<div class="wpl-addrcard">
					<div class="wpl-addrcard__head">
						<span class="wpl-addrcard__icon"><?php echo woopanel_icon( 'pin', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h4 class="wpl-addrcard__title"><?php echo esc_html( $label ); ?></h4>
					</div>
					<address class="wpl-addrcard__body"><?php echo wp_kses_post( wc()->countries ? wc()->countries->get_formatted_address( $addr ) : '' ); ?></address>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Post-tracking code for an order (set in the order edit screen as
	 * `post_barcode`), with the common carrier-plugin keys as fallbacks.
	 *
	 * @param WC_Order $order Order.
	 * @return string Raw code or ''.
	 */
	public static function order_tracking_code( $order ) {
		$code = trim( (string) $order->get_meta( 'post_barcode', true ) );
		if ( '' === $code ) {
			foreach ( array( 'tracking_code', '_tracking_code', 'wc_tracking_code' ) as $alt ) {
				$candidate = trim( (string) $order->get_meta( $alt, true ) );
				if ( '' !== $candidate ) {
					$code = $candidate;
					break;
				}
			}
		}
		if ( '' === $code || ! preg_match( '/^[A-Za-z0-9\-_]{6,32}$/', $code ) ) {
			return '';
		}
		return $code;
	}

	/**
	 * Tracking view: every shipped order with a post barcode.
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_tracking( $user_id, $user, $options ) {
		$rows = WooPanel_Data::get_tracking( $user_id, 30 );
		ob_start();
		if ( empty( $rows ) ) {
			?>
			<div class="wpl-empty">
				<div class="wpl-empty__icon"><?php echo woopanel_icon( 'truck', 36 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<h4 class="wpl-empty__title"><?php esc_html_e( 'No tracked shipments yet.', 'woopanel' ); ?></h4>
				<p class="wpl-empty__text"><?php esc_html_e( 'You will see a tracking code here once your order is shipped.', 'woopanel' ); ?></p>
			</div>
			<?php
			return (string) ob_get_clean();
		}
		?>
		<div class="wpl-trackgrid">
			<?php foreach ( $rows as $row ) : ?>
				<div class="wpl-trackcard">
					<div class="wpl-trackcard__icon"><?php echo woopanel_icon( 'truck', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="wpl-trackcard__body">
						<div class="wpl-trackcard__header">
							<span class="wpl-trackcard__order-title">
								<?php
								printf(
									/* translators: %s: order number link */
									esc_html__( 'Order %s', 'woopanel' ),
									'<a class="wpl-orderlink" href="' . esc_url( woopanel_panel_url( array( 'woopanel_view' => 'order', 'woopanel_order' => (string) $row['order_id'] ) ) ) . '">#' . esc_html( woopanel_localize_digits( $row['number'] ) ) . '</a>'
								);
								?>
							</span>
							<span class="wpl-badge <?php echo esc_attr( woopanel_status_class( $row['status'] ) ); ?>"><?php echo esc_html( woopanel_status_label( $row['status'] ) ); ?></span>
						</div>
						<div class="wpl-trackcard__code-wrap">
							<code class="wpl-trackcard__code" dir="ltr"><?php echo woopanel_keep_latin( $row['code'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></code>
							<?php if ( '' !== $row['date'] ) : ?>
								<span class="wpl-trackcard__date">
									<?php echo woopanel_icon( 'calendar', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( $row['date'] ); ?></span>
								</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="wpl-trackcard__actions">
						<button type="button" class="wpl-btn wpl-btn--sm wpl-copycode" data-wpl-copy="<?php echo esc_attr( $row['code'] ); ?>">
							<?php echo woopanel_icon( 'copy', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span class="wpl-copy-text" data-done-text="<?php esc_attr_e( 'Copied!', 'woopanel' ); ?>"><?php esc_html_e( 'Copy', 'woopanel' ); ?></span>
						</button>
						<a class="wpl-btn wpl-btn--sm wpl-trackcard__go" href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener">
							<?php echo woopanel_icon( 'external', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span><?php esc_html_e( 'Track shipment', 'woopanel' ); ?></span>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Downloads view.
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_downloads( $user_id, $user, $options ) {
		$downloads = WooPanel_Data::get_downloads( $user_id );
		ob_start();
		if ( empty( $downloads ) ) {
			?>
			<div class="wpl-empty">
				<div class="wpl-empty__icon"><?php echo woopanel_icon( 'download', 36 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<h4 class="wpl-empty__title"><?php esc_html_e( 'No downloads available yet.', 'woopanel' ); ?></h4>
				<p class="wpl-empty__text"><?php esc_html_e( 'When you purchase downloadable products, they will be listed here.', 'woopanel' ); ?></p>
			</div>
			<?php
			return (string) ob_get_clean();
		}
		?>
		<div class="wpl-dlgrid">
			<?php foreach ( $downloads as $dl ) : ?>
				<div class="wpl-dlcard">
					<div class="wpl-dlcard__icon"><?php echo woopanel_icon( 'file', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="wpl-dlcard__body">
						<strong class="wpl-dlcard__name"><?php echo esc_html( $dl['name'] ); ?></strong>
						<span class="wpl-dlcard__file" dir="ltr"><?php echo esc_html( $dl['file'] ); ?></span>
						<div class="wpl-dlcard__meta">
							<?php
							if ( '' !== $dl['remaining'] ) {
								echo '<span class="wpl-dlcard__stat">' . esc_html( sprintf( /* translators: %s: remaining download count */ __( 'Remaining: %s', 'woopanel' ), woopanel_localize_digits( $dl['remaining'] ) ) ) . '</span>';
							}
							if ( $dl['expires'] ) {
								echo '<span class="wpl-dlcard__stat">' . esc_html( sprintf( /* translators: %s: expiry date */ __( 'Expires: %s', 'woopanel' ), $dl['expires'] ) ) . '</span>';
							}
							?>
						</div>
					</div>
					<a class="wpl-btn wpl-btn--sm wpl-dlcard__btn" href="<?php echo esc_url( $dl['url'] ); ?>">
						<?php echo woopanel_icon( 'download', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php esc_html_e( 'Download', 'woopanel' ); ?></span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Addresses view (view + inline edit forms).
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_address( $user_id, $user, $options ) {
		ob_start();
		?>
		<div class="wpl-addresses-grid">
			<?php
			foreach ( array( 'billing', 'shipping' ) as $type ) {
				$fields = WooPanel_Data::address_fields( $type );
				$values = WooPanel_Data::get_address( $user_id, $type );
				$label  = 'billing' === $type ? __( 'Billing address', 'woopanel' ) : __( 'Shipping address', 'woopanel' );
				$base   = 'woopanel_' . $type;
				?>
				<div class="wpl-address" data-wpl-address>
					<div class="wpl-address__head">
						<div class="wpl-address__title-wrap">
							<span class="wpl-address__icon"><?php echo woopanel_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<h4><?php echo esc_html( $label ); ?></h4>
						</div>
						<button type="button" class="wpl-btn wpl-btn--ghost wpl-btn--xs" aria-expanded="false"
							data-wpl-toggle="<?php echo esc_attr( $base . '_form' ); ?>"
							data-wpl-label-open="<?php esc_attr_e( 'Edit', 'woopanel' ); ?>"
							data-wpl-label-close="<?php esc_attr_e( 'Close', 'woopanel' ); ?>"><?php esc_html_e( 'Edit', 'woopanel' ); ?></button>
					</div>
					<div class="wpl-address__view">
						<?php
						$lines = array();
						foreach ( $fields as $field ) {
							if ( ! empty( $values[ $field['key'] ] ) && 'email' !== $field['type'] ) {
								$lines[] = esc_html( $field['label'] . ': ' . $values[ $field['key'] ] );
							}
						}
						if ( empty( $lines ) ) {
							echo '<span class="wpl-address__empty">' . esc_html__( 'No address saved yet.', 'woopanel' ) . '</span>';
						} else {
							echo '<span>' . implode( '<br>', $lines ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</div>
					<form class="wpl-address__form" id="<?php echo esc_attr( $base . '_form' ); ?>" method="post" hidden>
						<input type="hidden" name="woopanel_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woopanel_address' ) ); ?>">
						<input type="hidden" name="woopanel_form" value="address">
						<input type="hidden" name="address_type" value="<?php echo esc_attr( $type ); ?>">
						<div class="wpl-grid2 <?php echo esc_attr( 'billing' === $type ? 'woocommerce-billing-fields' : 'woocommerce-shipping-fields' ); ?>">
							<?php foreach ( $fields as $field ) : ?>
								<?php
								$fkey   = $field['key'];
								$fid    = $base . '_' . $fkey;
								$fname  = 'woopanel_' . $fkey;
								$fval   = isset( $values[ $fkey ] ) ? $values[ $fkey ] : '';
								$req    = ! empty( $field['required'] );
								if ( 'country' === $field['type'] ) {
									echo '<div class="wpl-field"><label for="' . esc_attr( $fkey ) . '">' . esc_html( $field['label'] ) . ( $req ? ' *' : '' ) . '</label>';
									echo '<select id="' . esc_attr( $fkey ) . '" name="' . esc_attr( $fname ) . '" class="country_to_state" rel="' . esc_attr( $type . '_state' ) . '">';
									foreach ( wc()->countries ? wc()->countries->get_allowed_countries() : array() as $code => $name ) {
										printf( '<option value="%s"%s>%s</option>', esc_attr( $code ), selected( $fval, $code, false ), esc_html( $name ) );
									}
									echo '</select></div>';
									continue;
								}
								if ( 'state' === $field['type'] ) {
									$country_key = $type . '_country';
									$country     = ! empty( $values[ $country_key ] ) ? $values[ $country_key ] : '';
									$states      = $country && wc()->countries ? wc()->countries->get_states( $country ) : array();
									echo '<div class="wpl-field form-row"><label for="' . esc_attr( $fkey ) . '">' . esc_html( $field['label'] ) . ( $req ? ' *' : '' ) . '</label>';
									if ( $states ) {
										echo '<select id="' . esc_attr( $fkey ) . '" name="' . esc_attr( $fname ) . '" class="state_select">';
										echo '<option value="">' . esc_html__( 'Select an option', 'woopanel' ) . '</option>';
										foreach ( $states as $code => $state_name ) {
											printf( '<option value="%s"%s>%s</option>', esc_attr( $code ), selected( $fval, $code, false ), esc_html( $state_name ) );
										}
										echo '</select>';
									} else {
										echo '<input type="text" id="' . esc_attr( $fkey ) . '" name="' . esc_attr( $fname ) . '" class="input-text" value="' . esc_attr( $fval ) . '">';
									}
									echo '</div>';
									continue;
								}
								$ftype = in_array( $field['type'], array( 'email', 'tel' ), true ) ? $field['type'] : 'text';
								?>
								<div class="wpl-field">
									<label for="<?php echo esc_attr( $fid ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $req ? ' *' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
									<input type="<?php echo esc_attr( $ftype ); ?>"
										id="<?php echo esc_attr( $fid ); ?>"
										name="<?php echo esc_attr( $fname ); ?>"
										value="<?php echo esc_attr( $fval ); ?>"
										<?php echo $req ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="wpl-form-actions">
							<button type="submit" class="wpl-btn wpl-btn--sm"><?php esc_html_e( 'Save address', 'woopanel' ); ?></button>
						</div>
					</form>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Account view (profile + password).
	 *
	 * @param int     $user_id Current user ID.
	 * @param WP_User $user    Current user.
	 * @param array   $options Options.
	 * @return string
	 */
	private static function view_account( $user_id, $user, $options ) {
		ob_start();
		?>
		<div class="wpl-account">
			<form class="wpl-accountcard" method="post">
				<input type="hidden" name="woopanel_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woopanel_account' ) ); ?>">
				<input type="hidden" name="woopanel_form" value="account">
				<div class="wpl-accountcard__head">
					<span class="wpl-accountcard__icon"><?php echo woopanel_icon( 'user', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h4 class="wpl-accountcard__title"><?php esc_html_e( 'Account details', 'woopanel' ); ?></h4>
				</div>
				<div class="wpl-grid2">
					<div class="wpl-field">
						<label><?php esc_html_e( 'First name', 'woopanel' ); ?></label>
						<input type="text" name="woopanel_first_name" value="<?php echo esc_attr( $user->first_name ); ?>">
					</div>
					<div class="wpl-field">
						<label><?php esc_html_e( 'Last name', 'woopanel' ); ?></label>
						<input type="text" name="woopanel_last_name" value="<?php echo esc_attr( $user->last_name ); ?>">
					</div>
					<div class="wpl-field wpl-field--full">
						<label><?php esc_html_e( 'Email', 'woopanel' ); ?></label>
						<input type="email" name="woopanel_email" value="<?php echo esc_attr( $user->user_email ); ?>">
					</div>
				</div>
				<div class="wpl-form-actions">
					<button type="submit" class="wpl-btn wpl-btn--sm"><?php esc_html_e( 'Save details', 'woopanel' ); ?></button>
				</div>
			</form>

			<form class="wpl-accountcard" method="post">
				<input type="hidden" name="woopanel_nonce" value="<?php echo esc_attr( wp_create_nonce( 'woopanel_password' ) ); ?>">
				<input type="hidden" name="woopanel_form" value="password">
				<div class="wpl-accountcard__head">
					<span class="wpl-accountcard__icon"><?php echo woopanel_icon( 'shield', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h4 class="wpl-accountcard__title"><?php esc_html_e( 'Change password', 'woopanel' ); ?></h4>
				</div>
				<div class="wpl-grid2">
					<div class="wpl-field wpl-field--full">
						<label><?php esc_html_e( 'Current password', 'woopanel' ); ?></label>
						<input type="password" name="woopanel_current_pass" autocomplete="current-password">
					</div>
					<div class="wpl-field">
						<label><?php esc_html_e( 'New password', 'woopanel' ); ?></label>
						<input type="password" name="woopanel_new_pass" autocomplete="new-password">
					</div>
					<div class="wpl-field">
						<label><?php esc_html_e( 'Repeat new password', 'woopanel' ); ?></label>
						<input type="password" name="woopanel_new_pass2" autocomplete="new-password">
					</div>
				</div>
				<div class="wpl-form-actions">
					<button type="submit" class="wpl-btn wpl-btn--sm"><?php esc_html_e( 'Change password', 'woopanel' ); ?></button>
				</div>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

add_action( 'wp_enqueue_scripts', array( 'WooPanel_Render', 'enqueue_assets' ) );
