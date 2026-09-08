<?php
/**
 * POST form handlers: addresses, account, password.
 * Redirect-based (PRG pattern) — no AJAX, no nonce-in-URL forwarding risk.
 *
 * @package WooPanel
 */

defined( 'ABSPATH' ) || exit;

/**
 * All WooPanel form submissions go through this class.
 */
class WooPanel_Forms {

	/**
	 * Hook the POST handlers.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'handle_post' ) );
	}

	/**
	 * True when the current request is a WooPanel form submission.
	 *
	 * @return bool
	 */
	private static function is_submit() {
		return 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' )
			&& isset( $_POST['woopanel_form'] );
	}

	/**
	 * Route POST submissions to their handler.
	 */
	public static function handle_post() {
		if ( ! self::is_submit() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$form = isset( $_POST['woopanel_form'] ) ? sanitize_key( wp_unslash( $_POST['woopanel_form'] ) ) : '';

		if ( ! isset( $_POST['woopanel_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woopanel_nonce'] ) ), 'woopanel_' . $form ) ) {
			self::redirect( 'err_form', 'password' === $form ? 'account' : $form );
		}

		$user_id = get_current_user_id();

		switch ( $form ) {
			case 'address':
				self::save_address( $user_id );
				break;
			case 'account':
				self::save_account( $user_id );
				break;
			case 'password':
				self::save_password( $user_id );
				break;
			default:
				self::redirect( 'err_form' );
		}
		exit;
	}

	/**
	 * Redirect back to the panel with a message code.
	 *
	 * @param string $code Message code.
	 * @param string $view Optional view to return to.
	 */
	private static function redirect( $code, $view = '' ) {
		$args = array( 'woopanel_msg' => $code );
		if ( $view ) {
			$args['woopanel_view'] = $view;
		}
		wp_safe_redirect( woopanel_panel_url( $args ) );
		exit;
	}

	/**
	 * Billing / shipping address save (WooCommerce-native CRUD).
	 *
	 * @param int $user_id User ID.
	 */
	public static function save_address( $user_id ) {
		$type  = isset( $_POST['address_type'] ) ? sanitize_key( wp_unslash( $_POST['address_type'] ) ) : 'billing';
		$type  = in_array( $type, array( 'billing', 'shipping' ), true ) ? $type : 'billing';
		$view  = 'address';
		$found = false;

		$customer = new WC_Customer( $user_id );

		// Whitelist keys from WooCommerce's own address field definitions.
		foreach ( WooPanel_Data::address_fields( $type ) as $field ) {
			$post_key = 'woopanel_' . $field['key'];
			if ( ! isset( $_POST[ $post_key ] ) ) {
				continue;
			}
			$found = true;
			$raw   = wp_unslash( $_POST[ $post_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per type below.
			$value = 'checkbox' === $field['type'] ? ( empty( $raw ) ? 0 : 1 ) : sanitize_text_field( $raw );

			$setter = 'set_' . $type . '_' . str_replace( $type . '_', '', $field['key'] );
			if ( method_exists( $customer, $setter ) ) {
				$customer->{$setter}( $value );
			} else {
				update_user_meta( $user_id, $field['key'], $value );
			}
		}

		if ( $found ) {
			$customer->save();
		}

		/**
		 * Fires after a WooPanel address is saved.
		 *
		 * @param int    $user_id User ID.
		 * @param string $type    Address type.
		 */
		do_action( 'woopanel_address_saved', $user_id, $type );

		self::redirect( $found ? 'address_saved' : 'err_address', $view );
	}

	/**
	 * Account details (first/last name + email) save.
	 *
	 * @param int $user_id User ID.
	 */
	public static function save_account( $user_id ) {
		$first = isset( $_POST['woopanel_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['woopanel_first_name'] ) ) : '';
		$last  = isset( $_POST['woopanel_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['woopanel_last_name'] ) ) : '';
		$email = isset( $_POST['woopanel_email'] ) ? sanitize_email( wp_unslash( $_POST['woopanel_email'] ) ) : '';

		if ( ! $email || ! is_email( $email ) || sanitize_email( $email ) !== $email ) {
			self::redirect( 'err_email', 'account' );
		}

		$exists = email_exists( $email );
		if ( $exists && (int) $exists !== $user_id ) {
			self::redirect( 'err_email', 'account' );
		}

		$user = get_userdata( $user_id );
		// Keep display_name in step with the name fields (same policy as Woo core).
		$display = trim( $first . ' ' . $last );
		if ( '' === $display ) {
			$display = $user ? $user->user_login : '';
		}

		$updated = wp_update_user(
			array(
				'ID'           => $user_id,
				'user_email'   => $email,
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => $display,
			)
		);
		if ( is_wp_error( $updated ) ) {
			self::redirect( 'err_email', 'account' );
		}

		// WooCommerce caches customer data; keep it in sync.
		if ( class_exists( 'WC_Customer' ) ) {
			$customer = new WC_Customer( $user_id );
			$customer->set_billing_email( $email );
			$customer->set_first_name( $first );
			$customer->set_last_name( $last );
			$customer->save();
		}

		/**
		 * Fires after WooPanel account details are saved.
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'woopanel_account_saved', $user_id );

		self::redirect( 'account_saved', 'account' );
	}

	/**
	 * Password change (requires the current password).
	 *
	 * @param int $user_id User ID.
	 */
	public static function save_password( $user_id ) {
		$current = isset( $_POST['woopanel_current_pass'] ) ? (string) wp_unslash( $_POST['woopanel_current_pass'] ) : '';
		$new1    = isset( $_POST['woopanel_new_pass'] ) ? (string) wp_unslash( $_POST['woopanel_new_pass'] ) : '';
		$new2    = isset( $_POST['woopanel_new_pass2'] ) ? (string) wp_unslash( $_POST['woopanel_new_pass2'] ) : '';

		$user = get_userdata( $user_id );

		if ( ! $user || ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
			self::redirect( 'err_password_current', 'account' );
		}
		if ( $new1 !== $new2 ) {
			self::redirect( 'err_password_match', 'account' );
		}
		if ( strlen( $new1 ) < 8 ) {
			self::redirect( 'err_password_short', 'account' );
		}

		wp_set_password( $new1, $user_id );

		// Refresh the in-memory user cache; the current session cookie stays valid.
		wp_set_current_user( $user_id );

		self::redirect( 'password_changed', 'account' );
	}
}

WooPanel_Forms::init();
