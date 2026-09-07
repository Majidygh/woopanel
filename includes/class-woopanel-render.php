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
		if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
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
			':root{--wpl-accent:%1$s;--wpl-accent-soft:%2$s;--wpl-accent-rgb:%3$d %4$d %5$d;}',
			$accent,
			$bg,
			hexdec( substr( $accent, 1, 2 ) ),
			hexdec( substr( $accent, 3, 2 ) ),
			hexdec( substr( $accent, 5, 2 ) )
		);
		wp_add_inline_style( 'woopanel', $css );

		wp_enqueue_script( 'woopanel', WOOPANEL_URL . 'assets/woopanel.js', array(), WOOPANEL_VERSION, true );
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
	 * True on the default WooCommerce My Account dashboard endpoint with takeover on.
	 *
	 * @return bool
	 */
	private static function is_my_account_takeover() {
		$options = woopanel_get_options();
		return ! empty( $options['replace_dashboard'] )
			&& function_exists( 'is_account_page' )
			&& is_account_page()
			&& function_exists( 'is_wc_endpoint_url' )
			&& ! is_wc_endpoint_url();
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

		ob_start();
		?>
		<div class="wpl-panel wpl-panel--<?php echo esc_attr( $context ); ?>" data-woopanel>
			<div class="wpl-shell">
				<?php self::sidebar( $view, $user, $options ); ?>
				<main class="wpl-main">
					<div class="wpl-main__top">
						<h2 class="wpl-main__title">
						<?php
						$nav = WooPanel_Data::nav_items( $view );
						foreach ( $nav as $item ) {
							if ( $item['active'] ) {
								echo esc_html( $item['label'] );
							}
						}
						?>
						</h2>
						<div class="wpl-theme" data-wpl-theme>
							<button type="button" class="wpl-theme__btn" data-wpl-theme-set="light" aria-label="<?php esc_attr_e( 'Light theme', 'woopanel' ); ?>"><?php echo woopanel_icon( 'sun', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
							<button type="button" class="wpl-theme__btn" data-wpl-theme-set="system" aria-label="<?php esc_attr_e( 'System theme', 'woopanel' ); ?>"><?php echo woopanel_icon( 'monitor', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
							<button type="button" class="wpl-theme__btn" data-wpl-theme-set="dark" aria-label="<?php esc_attr_e( 'Dark theme', 'woopanel' ); ?>"><?php echo woopanel_icon( 'moon', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
						</div>
					</div>

					<?php if ( '' !== $notice[1] ) : ?>
						<div class="wpl-alert wpl-alert--<?php echo esc_attr( $notice[0] ); ?>"><?php echo woopanel_icon( 'success' === $notice[0] ? 'check' : 'alert', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $notice[1] ); ?></div>
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
	 */
	private static function sidebar( $view, $user, $options ) {
		$display = $user ? ( $user->display_name ? $user->display_name : $user->user_login ) : '';
		?>
		<aside class="wpl-sidebar">
			<div class="wpl-brand">
				<div class="wpl-brand__logo">W</div>
				<div class="wpl-brand__text">
					<strong><?php echo esc_html( $options['panel_title'] ); ?></strong>
					<span class="wpl-brand__user"><?php echo wp_kses( sprintf( /* translators: %s: user display name */ __( 'Welcome, %s', 'woopanel' ), '<bdi>' . esc_html( $display ) . '</bdi>' ), array( 'bdi' => array() ) ); ?></span>
				</div>
			</div>
			<nav class="wpl-nav">
				<?php foreach ( WooPanel_Data::nav_items( $view ) as $item ) : ?>
					<a class="wpl-nav__item<?php echo $item['active'] ? ' wpl-nav__item--active' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>"
						href="<?php echo esc_url( $item['url'] ); ?>">
						<?php echo woopanel_icon( $item['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( $item['label'] ); ?></span>
						<?php echo woopanel_icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<div class="wpl-sidebar__foot">
				<?php if ( $user ) : ?>
					<span class="wpl-sidebar__avatar"><?php echo esc_html( mb_substr( $display, 0, 1, 'UTF-8' ) ); ?></span>
					<span class="wpl-sidebar__uname"><?php echo esc_html( $display ); ?></span>
				<?php endif; ?>
				<a class="wpl-nav__item wpl-nav__item--out" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					<svg class="wpl-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
					<span><?php esc_html_e( 'Log out', 'woopanel' ); ?></span>
				</a>
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
		$redirect = ! empty( $atts['redirect'] ) ? $atts['redirect'] : woopanel_panel_url();
		$account  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url( $redirect );

		ob_start();
		?>
		<div class="wpl-panel"><div class="wpl-login">
			<div class="wpl-login__icon"><?php echo woopanel_icon( 'user', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
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
		$stats   = WooPanel_Data::get_stats( $user_id );
		$orders  = WooPanel_Data::order_rows( WooPanel_Data::get_orders( $user_id, 4 ) );
		$welcome = $options['welcome_text'];

		$cards = array(
			array( 'icon' => 'bag',    'label' => __( 'Orders', 'woopanel' ),                'value' => number_format_i18n( $stats['orders_count'] ) ),
			array( 'icon' => 'wallet', 'label' => __( 'Total spent', 'woopanel' ),           'value' => function_exists( 'wc_price' ) ? wc_price( $stats['total_spent'] ) : $stats['total_spent'] ),
			array( 'icon' => 'card',   'label' => __( 'Average order', 'woopanel' ),         'value' => function_exists( 'wc_price' ) ? wc_price( $stats['avg_order'] ) : $stats['avg_order'] ),
			array( 'icon' => 'file',   'label' => __( 'Available downloads', 'woopanel' ),   'value' => number_format_i18n( $stats['downloads'] ) ),
		);

		ob_start();
		?>
		<div class="wpl-hero wpl-hero--glam">
			<h3 class="wpl-hero__title"><?php echo wp_kses( $welcome . '، <bdi>' . ( $user ? esc_html( $user->display_name ) : '' ) . '</bdi> 👋', array( 'bdi' => array() ) ); ?></h3>
			<p class="wpl-hero__text"><?php esc_html_e( 'Here is a summary of your account.', 'woopanel' ); ?></p>
		</div>
		<div class="wpl-cards">
			<?php foreach ( $cards as $card ) : ?>
				<div class="wpl-card">
					<div class="wpl-card__icon"><?php echo woopanel_icon( $card['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="wpl-card__body">
						<span class="wpl-card__label"><?php echo esc_html( $card['label'] ); ?></span>
						<span class="wpl-card__value"><?php echo wp_kses_post( $card['value'] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="wpl-section">
			<div class="wpl-section__head">
				<h4><?php esc_html_e( 'Recent orders', 'woopanel' ); ?></h4>
				<a class="wpl-link" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'orders' ) ) ); ?>"><?php esc_html_e( 'View all', 'woopanel' ); ?></a>
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
		$status   = isset( $_GET['woopanel_status'] ) ? sanitize_key( wp_unslash( $_GET['woopanel_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter.
		$paged    = isset( $_GET['woopanel_paged'] ) ? max( 1, absint( $_GET['woopanel_paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pager.

		$query_args = array();
		if ( $status && in_array( $status, array_keys( wc_get_order_statuses() ), true ) ) {
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
				<?php $furl = woopanel_panel_url( array( 'woopanel_view' => 'orders', 'woopanel_status' => $slug, 'woopanel_paged' => '' ) ); ?>
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
	 * Orders table markup.
	 *
	 * @param array[] $orders Prepared rows.
	 * @return string
	 */
	private static function orders_table( $orders ) {
		ob_start();
		if ( empty( $orders ) ) {
			?>
			<div class="wpl-empty">
				<?php echo woopanel_icon( 'box', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p><?php esc_html_e( 'No orders found.', 'woopanel' ); ?></p>
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
						<th><?php esc_html_e( 'Date', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Status', 'woopanel' ); ?></th>
						<th><?php esc_html_e( 'Total', 'woopanel' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $row ) : ?>
						<tr>
							<td><a class="wpl-orderlink" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'order', 'woopanel_order' => (string) $row['id'] ) ) ); ?>">#<?php echo esc_html( woopanel_localize_digits( $row['number'] ) ); ?></a></td>
							<td><?php echo esc_html( $row['date'] ); ?></td>
							<td><span class="wpl-badge <?php echo esc_attr( $row['status_class'] ); ?>"><?php echo esc_html( $row['status_label'] ); ?></span></td>
							<td><?php echo wp_kses_post( $row['total'] ); ?></td>
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
		$oid = isset( $_GET['woopanel_order'] ) ? absint( $_GET['woopanel_order'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, ownership verified below.
		$order = $oid ? wc_get_order( $oid ) : false;

		// Ownership gate: the order must belong to this user, full stop.
		if ( ! $order || (int) $order->get_user_id() !== $user_id ) {
			return '<div class="wpl-alert wpl-alert--error">' . esc_html__( 'Order not found.', 'woopanel' ) . '</div>';
		}

		$status = $order->get_status();
		ob_start();
		?>
		<a class="wpl-back" href="<?php echo esc_url( woopanel_panel_url( array( 'woopanel_view' => 'orders', 'woopanel_status' => '', 'woopanel_paged' => '' ) ) ); ?>"><?php echo woopanel_icon( 'chevron', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php esc_html_e( 'Back to orders', 'woopanel' ); ?></a>

		<div class="wpl-orderhead">
			<div>
				<h3 class="wpl-orderhead__num">#<?php echo esc_html( woopanel_localize_digits( $order->get_order_number() ) ); ?></h3>
				<p class="wpl-orderhead__date"><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '' ); ?></p>
			</div>
			<span class="wpl-badge <?php echo esc_attr( woopanel_status_class( $status ) ); ?>"><?php echo esc_html( woopanel_status_label( $status ) ); ?></span>
		</div>

		<div class="wpl-table-wrap">
			<table class="wpl-table">
				<thead>
					<tr><th><?php esc_html_e( 'Product', 'woopanel' ); ?></th><th><?php esc_html_e( 'Quantity', 'woopanel' ); ?></th><th><?php esc_html_e( 'Total', 'woopanel' ); ?></th></tr>
				</thead>
				<tbody>
					<?php foreach ( $order->get_items() as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item->get_name() ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $item->get_quantity() ) ); ?></td>
							<td><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
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
					array( __( 'Tax', 'woopanel' ), wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ),
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
					?>
					<div class="wpl-orderfoot__row">
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
					<h4 class="wpl-addrcard__title"><?php echo esc_html( $label ); ?></h4>
					<address class="wpl-addrcard__body"><?php echo wp_kses_post( wc()->countries ? wc()->countries->get_formatted_address( $addr ) : '' ); ?></address>
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
				<?php echo woopanel_icon( 'download', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p><?php esc_html_e( 'No downloads available yet.', 'woopanel' ); ?></p>
			</div>
			<?php
			return (string) ob_get_clean();
		}
		?>
		<div class="wpl-dlgrid">
			<?php foreach ( $downloads as $dl ) : ?>
				<div class="wpl-dlcard">
					<div class="wpl-dlcard__icon"><?php echo woopanel_icon( 'file', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<strong class="wpl-dlcard__name"><?php echo esc_html( $dl['name'] ); ?></strong>
					<span class="wpl-dlcard__file"><?php echo esc_html( $dl['file'] ); ?></span>
					<span class="wpl-dlcard__meta">
						<?php
						if ( '' !== $dl['remaining'] ) {
							echo esc_html( sprintf( /* translators: %s: remaining download count */ __( 'Remaining: %s', 'woopanel' ), $dl['remaining'] ) );
						}
						if ( $dl['expires'] ) {
							echo esc_html( ' — ' . sprintf( /* translators: %s: expiry date */ __( 'Expires: %s', 'woopanel' ), $dl['expires'] ) );
						}
						?>
					</span>
					<a class="wpl-btn wpl-btn--sm" href="<?php echo esc_url( $dl['url'] ); ?>"><?php esc_html_e( 'Download', 'woopanel' ); ?></a>
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
		foreach ( array( 'billing', 'shipping' ) as $type ) {
			$fields = WooPanel_Data::address_fields( $type );
			$values = WooPanel_Data::get_address( $user_id, $type );
			$label  = 'billing' === $type ? __( 'Billing address', 'woopanel' ) : __( 'Shipping address', 'woopanel' );
			?>
			<div class="wpl-address" data-wpl-address>
				<div class="wpl-address__head">
					<h4><?php echo esc_html( $label ); ?></h4>
					<button type="button" class="wpl-btn wpl-btn--ghost wpl-btn--sm" data-wpl-toggle="wpl-address__form--<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Edit', 'woopanel' ); ?></button>
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
						echo '<span>' . implode( '<br>', $lines ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- lines pre-escaped.
					}
					?>
				</div>
				<form class="wpl-address__form wpl-address__form--<?php echo esc_attr( $type ); ?>" method="post" hidden>
					<?php wp_nonce_field( 'woopanel_address', 'woopanel_nonce' ); ?>
					<input type="hidden" name="woopanel_form" value="address">
					<input type="hidden" name="address_type" value="<?php echo esc_attr( $type ); ?>">
					<div class="wpl-grid2">
						<?php foreach ( $fields as $field ) : ?>
							<?php
							if ( 'state' === $field['type'] ) {
								// Country-dependent state dropdowns: use Woo's own walker.
								$country_key = $type . '_country';
								$country     = ! empty( $values[ $country_key ] ) ? $values[ $country_key ] : ( function_exists( 'wc_get_base_location' ) ? wc_get_base_location()['country'] : '' );
								$args        = array(
									'id'       => 'woopanel_' . $field['key'],
									'name'     => 'woopanel_' . $field['key'],
									'value'    => isset( $values[ $field['key'] ] ) ? $values[ $field['key'] ] : '',
								);
								$states = $country && function_exists( 'WC' ) && wc()->countries ? wc()->countries->get_states( $country ) : array();
								if ( $states ) {
									echo '<div class="wpl-field"><label>' . esc_html( $field['label'] ) . '</label><select ' . esc_attr( 'name="' . $args['name'] . '"' ) . '>';
									foreach ( $states as $code => $state_name ) {
										printf( '<option value="%s" %s>%s</option>', esc_attr( $code ), selected( $args['value'], $code, false ), esc_html( $state_name ) );
									}
									echo '</select></div>';
								} else {
									echo '<div class="wpl-field"><label>' . esc_html( $field['label'] ) . '</label><input type="text" ' . esc_attr( 'name="' . $args['name'] . '"' ) . ' value="' . esc_attr( $args['value'] ) . '"></div>';
								}
								continue;
							}
							?>
							<div class="wpl-field">
								<label><?php echo esc_html( $field['label'] ); ?><?php echo $field['required'] ? ' *' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
								<input type="<?php echo esc_attr( 'email' === $field['type'] ? 'email' : ( 'tel' === $field['type'] ? 'tel' : 'text' ) ); ?>"
									name="woopanel_<?php echo esc_attr( $field['key'] ); ?>"
									value="<?php echo esc_attr( isset( $values[ $field['key'] ] ) ? $values[ $field['key'] ] : '' ); ?>">
							</div>
						<?php endforeach; ?>
					</div>
					<button type="submit" class="wpl-btn wpl-btn--sm"><?php esc_html_e( 'Save address', 'woopanel' ); ?></button>
				</form>
			</div>
			<?php
		}
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
				<?php wp_nonce_field( 'woopanel_account', 'woopanel_nonce' ); ?>
				<input type="hidden" name="woopanel_form" value="account">
				<h4 class="wpl-accountcard__title"><?php esc_html_e( 'Account details', 'woopanel' ); ?></h4>
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
				<button type="submit" class="wpl-btn"><?php esc_html_e( 'Save details', 'woopanel' ); ?></button>
			</form>

			<form class="wpl-accountcard" method="post">
				<?php wp_nonce_field( 'woopanel_password', 'woopanel_nonce' ); ?>
				<input type="hidden" name="woopanel_form" value="password">
				<h4 class="wpl-accountcard__title"><?php esc_html_e( 'Change password', 'woopanel' ); ?></h4>
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
				<button type="submit" class="wpl-btn"><?php esc_html_e( 'Change password', 'woopanel' ); ?></button>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

add_action( 'wp_enqueue_scripts', array( 'WooPanel_Render', 'enqueue_assets' ) );
