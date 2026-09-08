<?php
/**
 * Read-only WooCommerce data layer for the panel.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * All WooPanel data reads go through this class.
 */
class WooPanel_Data {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woo() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_orders' );
	}

	/**
	 * Require WooCommerce, dying with a friendly admin notice area otherwise.
	 *
	 * @return bool
	 */
	public static function require_woo() {
		return self::is_woo();
	}

	/**
	 * Dashboard statistics for the current customer.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_stats( $user_id ) {
		$defaults = array(
			'orders_count'  => 0,
			'total_spent'   => 0.0,
			'avg_order'     => 0.0,
			'downloads'     => 0,
		);

		if ( ! self::is_woo() ) {
			return $defaults;
		}

		$stats = array(
			'orders_count'  => (int) wc_get_customer_order_count( $user_id ),
			'total_spent'   => (float) wc_get_customer_total_spent( $user_id ),
			'downloads'     => 0,
		);

		$stats['avg_order'] = $stats['orders_count'] > 0 ? $stats['total_spent'] / $stats['orders_count'] : 0.0;

		if ( function_exists( 'wc_get_customer_available_downloads' ) ) {
			$downloads          = wc_get_customer_available_downloads( $user_id );
			$stats['downloads'] = is_array( $downloads ) ? count( $downloads ) : 0;
		}

		return $stats;
	}

	/**
	 * Recent orders for a customer.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $limit   How many.
	 * @param array $args    Extra wc_get_orders args (offset, status...).
	 * @return WC_Order[]
	 */
	public static function get_orders( $user_id, $limit = 8, $args = array() ) {
		if ( ! self::is_woo() ) {
			return array();
		}

		$defaults = array(
			'customer_id' => $user_id,
			'limit'       => $limit,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'type'        => 'shop_order',
		);

		// HPOS-safe: use offsets, not paged (paged assumes posts table paging).
		if ( isset( $args['offset'] ) ) {
			$defaults['offset'] = max( 0, absint( $args['offset'] ) );
			unset( $args['offset'] );
		}

		return wc_get_orders( array_merge( $defaults, $args ) );
	}

	/**
	 * Total number of orders for pagination.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Extra args (status filter).
	 * @return int
	 */
	public static function count_orders( $user_id, $args = array() ) {
		if ( ! self::is_woo() ) {
			return 0;
		}
		$query = array_merge(
			array(
				'customer_id' => $user_id,
				'limit'       => 1,
				'type'        => 'shop_order',
				'return'      => 'ids',
			),
			$args,
			array( 'paginate' => true )
		);
		$result = wc_get_orders( $query );
		return is_object( $result ) && isset( $result->total ) ? (int) $result->total : 0;
	}

	/**
	 * Order rows prepared for templates.
	 *
	 * @param WC_Order[] $orders Orders.
	 * @return array[] Each row: id, number, status, status_label, status_class, date, total.
	 */
	public static function order_rows( $orders ) {
		$rows = array();
		foreach ( $orders as $order ) {
			$status = $order->get_status();
			$items_summary = array();
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				$thumb   = $product ? $product->get_image( array( 40, 40 ), array( 'class' => 'wpl-item-thumb', 'alt' => esc_attr( $item->get_name() ) ) ) : '';
				$items_summary[] = array(
					'name'  => $item->get_name(),
					'qty'   => $item->get_quantity(),
					'thumb' => $thumb,
				);
			}
			$rows[] = array(
				'id'           => $order->get_id(),
				'number'       => $order->get_order_number(),
				'status'       => $status,
				'status_label' => woopanel_status_label( $status ),
				'status_class' => woopanel_status_class( $status ),
				'date'         => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
				'total'        => $order->get_formatted_order_total(),
				'item_count'   => $order->get_item_count(),
				'items'        => $items_summary,
			);
		}
		return $rows;
	}

	/**
	 * Available customer downloads.
	 *
	 * @param int $user_id User ID.
	 * @return array[]
	 */
	public static function get_downloads( $user_id ) {
		if ( ! self::is_woo() || ! function_exists( 'wc_get_customer_available_downloads' ) ) {
			return array();
		}
		$out = array();
		foreach ( wc_get_customer_available_downloads( $user_id ) as $dl ) {
			$out[] = array(
				'name'          => $dl['product_name'],
				'file'          => $dl['file']['name'],
				'url'           => $dl['download_url'],
				'remaining'     => null === $dl['downloads_remaining'] ? '' : (string) $dl['downloads_remaining'],
				'expires'       => '' === $dl['access_expires'] || null === $dl['access_expires'] ? '' : date_i18n( get_option( 'date_format' ), strtotime( (string) $dl['access_expires'] ) ),
				'order_id'      => $dl['order_id'],
			);
		}
		return $out;
	}

	/**
	 * Tracking (post barcode) rows for a customer's orders.
	 *
	 * Reads the `post_barcode` order meta — the same field filled in the
	 * WooCommerce order edit screen — for the customer's recent orders and
	 * builds an Iranian Post tracking deep link per code.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   How many recent orders to scan.
	 * @return array Each: order_id, number, date, status, code, url.
	 */
	public static function get_tracking( $user_id, $limit = 25 ) {
		$rows = array();
		if ( ! self::is_woo() ) {
			return $rows;
		}
		$orders = self::get_orders( $user_id, $limit );
		foreach ( $orders as $order ) {
			$code = trim( (string) $order->get_meta( 'post_barcode', true ) );
			if ( '' === $code ) {
				// Common carrier-metabox key names as fallbacks.
				foreach ( array( 'tracking_code', '_tracking_code', 'wc_tracking_code' ) as $alt ) {
					$candidate = trim( (string) $order->get_meta( $alt, true ) );
					if ( '' !== $candidate ) {
						$code = $candidate;
						break;
					}
				}
			}
			// Codes are alphanumeric (Iran Post: 14/20/24 digits, sometimes letters+digits).
			if ( '' === $code || ! preg_match( '/^[A-Za-z0-9\-_]{6,32}$/', $code ) ) {
				continue;
			}
			$rows[] = array(
				'order_id' => $order->get_id(),
				'number'   => $order->get_order_number(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
				'status'   => $order->get_status(),
				'code'     => $code,
				'url'      => 'https://tracking.post.ir/?id=' . rawurlencode( $code ),
			);
		}
		return apply_filters( 'woopanel_tracking_rows', $rows, $user_id );
	}

	/**
	 * Address fields shared by view + edit forms.
	 *
	 * @param string $type 'billing' or 'shipping'.
	 * @return array[] field definitions
	 */
	public static function address_fields( $type ) {
		if ( ! function_exists( 'WC' ) || ! wc()->countries ) {
			return array();
		}
		$fields = wc()->countries->get_address_fields( '', $type . '_' );
		$out    = array();
		foreach ( $fields as $key => $field ) {
			if ( empty( $field['label'] ) ) {
				continue;
			}
			$out[] = array(
				'key'      => $key,
				'label'    => $field['label'],
				'required' => ! empty( $field['required'] ),
				'type'     => ! empty( $field['type'] ) ? $field['type'] : 'text',
			);
		}
		return $out;
	}

	/**
	 * Saved address values for the current user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    'billing' or 'shipping'.
	 * @return array
	 */
	public static function get_address( $user_id, $type ) {
		$values = array();
		$fields = self::address_fields( $type );

		$customer = class_exists( 'WC_Customer' ) ? new WC_Customer( $user_id ) : null;
		foreach ( $fields as $field ) {
			$prop = str_replace( $type . '_', '', $field['key'] );
			if ( $customer ) {
				$get = 'get_' . $type . '_' . $prop;
				if ( method_exists( $customer, $get ) ) {
					$values[ $field['key'] ] = (string) $customer->{$get}();
					continue;
				}
			}
			$values[ $field['key'] ] = get_user_meta( $user_id, $field['key'], true );
		}
		return $values;
	}

	/**
	 * Panel navigation items.
	 *
	 * @param string $current Current view.
	 * @return array[] each: slug, label, icon
	 */
	public static function nav_items( $current, $user_id = 0 ) {
		$items = array(
			array( 'slug' => 'dashboard', 'label' => __( 'Dashboard', 'woopanel' ), 'icon' => 'grid' ),
			array( 'slug' => 'orders',    'label' => __( 'Orders', 'woopanel' ),    'icon' => 'bag' ),
			array( 'slug' => 'tracking',  'label' => __( 'Tracking', 'woopanel' ),  'icon' => 'truck' ),
			array( 'slug' => 'downloads', 'label' => __( 'Downloads', 'woopanel' ), 'icon' => 'download' ),
			array( 'slug' => 'address',   'label' => __( 'Addresses', 'woopanel' ), 'icon' => 'pin' ),
			array( 'slug' => 'account',   'label' => __( 'Account', 'woopanel' ),   'icon' => 'user' ),
		);

		// In takeover mode on the real My Account page, link to Woo's own
		// endpoint URLs (/my-account/orders/) so the address bar, browser
		// history and order-email links all agree with the panel nav.
		$takeover = class_exists( 'WooPanel_Takeover' )
			&& WooPanel_Takeover::active()
			&& function_exists( 'is_account_page' )
			&& is_account_page()
			&& function_exists( 'wc_get_account_endpoint_url' );
		$endpoints = array(
			'dashboard' => false,
			'orders'    => 'orders',
			'downloads' => 'downloads',
			'address'   => 'edit-address',
			'account'   => 'edit-account',
		);

		foreach ( $items as $i => $item ) {
			$items[ $i ]['active'] = ( $item['slug'] === $current );
			if ( $takeover && array_key_exists( $item['slug'], $endpoints ) ) {
				$ep               = $endpoints[ $item['slug'] ];
				$items[ $i ]['url'] = false === $ep ? wc_get_account_endpoint_url( 'dashboard' ) : wc_get_account_endpoint_url( $ep );
			} else {
				// WooPanel-native views (e.g. tracking) use query-string nav —
				// no rewrite endpoint needed, so no rule flush ever.
				$items[ $i ]['url'] = woopanel_panel_url( array( 'woopanel_view' => $item['slug'] ) );
			}
		}
		return apply_filters( 'woopanel_nav_items', $items, $current, $user_id );
	}
}
