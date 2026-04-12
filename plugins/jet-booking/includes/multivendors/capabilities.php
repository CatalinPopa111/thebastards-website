<?php
namespace JET_ABAF\Multivendors;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Capabilities {

	public function __construct() {

		// Maps meta capabilities.
		add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
		// Filter user capabilities.
		add_filter( 'user_has_cap', [ $this, 'user_has_cap' ], 10, 4 );

		// Resync capabilities on settings update.
		add_action( 'jet-booking/settings/after-update', [ $this, 'maybe_resync_caps' ], 10, 3 );

	}

	/**
	 * Maps meta capabilities.
	 *
	 * Determines the specific capabilities required for a given meta capability.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array  $caps    Capabilities for meta capability.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id ID of the user whose capabilities are being checked.
	 * @param array  $args    Additional arguments, usually containing the post ID.
	 *
	 * @return array Filtered set of capabilities required for the user to perform the action.
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		// Only apply to a custom user role.
		if ( ! jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			return $caps;
		}

		// Handle only edit_, delete_ related capabilities.
		if ( strpos( $cap, 'edit_' ) !== 0 && strpos( $cap, 'delete_' ) !== 0 ) {
			return $caps;
		}

		if ( empty( $args[0] ) ) {
			return $caps;
		}

		if ( is_numeric( $args[0] ) && (int) $args[0] === $user_id ) {
			return $caps;
		}

		$post = get_post( $args[0] );

		if ( ! $post ) {
			return $caps;
		}

		// Check if post type is allowed.
		if ( ! in_array( $post->post_type, jet_abaf()->vendors->get_post_types(), true ) ) {
			// Don't allow access to non-allowed CPTs.
			return [ 'do_not_allow' ];
		}

		return $caps;

	}

	/**
	 * Filter user capabilities.
	 *
	 * Restricts edit_posts capability for custom vendor role to only allowed
	 * post types and admin pages.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array    $allcaps All capabilities the user has.
	 * @param array    $caps    Required capabilities being checked.
	 * @param array    $args    Arguments passed to has_cap().
	 * @param \WP_User $user    The user object.
	 *
	 * @return array Filtered capabilities.
	 */
	public function user_has_cap( $allcaps, $caps, $args, $user ) {

		// Only apply in admin area.
		if ( ! is_admin() ) {
			return $allcaps;
		}

		// Only apply to a custom user role.
		if ( ! jet_abaf()->vendors->is_booking_vendor( $user ) ) {
			return $allcaps;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return $allcaps;
		}

		$post_types = jet_abaf()->vendors->get_post_types();

		if ( empty( $post_types ) ) {
			return $allcaps;
		}

		$capability_types = jet_abaf()->vendors->get_capability_types( $post_types );
		$edit_caps        = [ 'edit_posts' ];

		foreach ( $capability_types as $capability_type ) {
			$edit_caps[] = "edit_{$capability_type}";
			$edit_caps[] = "edit_{$capability_type}s";
			$edit_caps[] = "edit_others_{$capability_type}s";
		}

		if ( ! array_intersect( $edit_caps, $caps ) ) {
			return $allcaps;
		}

		$unset_caps = false;

		global $pagenow;

		// phpcs:disable WordPress.Security.NonceVerification
		if ( in_array( $pagenow, [ 'edit.php', 'post-new.php', 'post.php' ], true ) ) {
			$post_type = 'post';

			if ( isset( $_GET['post_type'] ) ) {
				$post_type = sanitize_key( $_GET['post_type'] );
			} elseif ( isset( $_GET['post'] ) ) {
				$post_type = get_post_type( absint( $_GET['post'] ) );
			} elseif ( isset( $_POST['post_type'] ) ) {
				$post_type = sanitize_key( $_POST['post_type'] );
			} elseif ( isset( $_POST['post_ID'] ) ) {
				$post_type = get_post_type( absint( $_POST['post_ID'] ) );
			}

			if ( ! in_array( $post_type, $post_types, true ) ) {
				$unset_caps = true;
			} elseif ( 'shop_order' === $post_type ) {
				if ( 'post-new.php' === $pagenow ) {
					$unset_caps = true;
				} elseif ( 'post.php' === $pagenow ) {
					$post_id = 0;

					if ( isset( $_GET['post'] ) ) {
						$post_id = absint( $_GET['post'] );
					} elseif ( isset( $_POST['post_ID'] ) ) {
						$post_id = absint( $_POST['post_ID'] );
					}

					$booking_vendor = get_post_meta( $post_id, '__jet_booking_vendor', true );

					if ( ! $booking_vendor || (int) $booking_vendor !== $user->ID ) {
						$unset_caps = true;
					}
				}
			}
		} elseif ( 'admin.php' === $pagenow ) {
			$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

			if ( ! $page || ! in_array( $page, jet_abaf()->vendors->get_allowed_pages(), true ) ) {
				$unset_caps = true;
			} elseif ( 'wc-orders' === $page ) {
				$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';

				if ( 'edit' === $action ) {
					$post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
					$order   = function_exists( 'wc_get_order' ) ? wc_get_order( $post_id ) : null;

					if ( ! $order ) {
						$unset_caps = true;
					} else {
						$booking_vendor = $order->get_meta( '__jet_booking_vendor' );

						if ( ! $booking_vendor || (int) $booking_vendor !== $user->ID ) {
							$unset_caps = true;
						}
					}
				}
			}
		} elseif ( ! in_array( $pagenow, jet_abaf()->vendors->get_allowed_pages(), true ) ) {
			$unset_caps = true;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		if ( $unset_caps ) {
			foreach ( $edit_caps as $cap ) {
				if ( isset( $allcaps[ $cap ] ) ) {
					$allcaps[ $cap ] = false;
				}
			}
		}

		return $allcaps;

	}

	/**
	 * Resynchronizes vendor role capabilities based on updated settings.
	 *
	 * Adjusts the capabilities associated with the vendor role whenever specific
	 * configurable settings are modified.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array  $settings Current settings from the application.
	 * @param string $setting  The specific setting being updated.
	 * @param mixed  $value    The new value of the setting being updated.
	 *
	 * @return void
	 */
	public function maybe_resync_caps( $settings, $setting, $value ) {

		$sync_settings = [ 'booking_mode', 'apartment_post_type', 'related_post_type', 'wc_integration' ];

		if ( ! in_array( $setting, $sync_settings, true ) ) {
			return;
		}

		$old_value  = $settings[ $setting ] ?? null;
		$post_types = [];

		switch ( $setting ) {
			case 'apartment_post_type':
				$old_value = jet_abaf()->tools->ensure_array( $old_value );
				$value     = jet_abaf()->tools->ensure_array( $value );

				if ( $old_value !== $value ) {
					$post_types = jet_abaf()->vendors->get_post_types( $value );
				}

				break;

			case 'related_post_type':
			case 'wc_integration':
				if ( $old_value !== $value ) {
					$post_types = jet_abaf()->vendors->get_post_types();
				}

				break;

			case 'booking_mode':
				if ( $old_value !== $value ) {
					$post_types = 'plain' === $value ? $settings['apartment_post_type'] ?? [] : [ 'product' ];
					$post_types = jet_abaf()->vendors->get_post_types( $post_types );
				}

				break;

			default:
				break;
		}

		if ( ! $post_types ) {
			return;
		}

		if ( $vendor = get_role( jet_abaf()->vendors->role ) ) {
			foreach ( $vendor->capabilities as $cap => $grant ) {
				if ( ! array_key_exists( $cap, jet_abaf()->vendors->get_base_capabilities() ) ) {
					$vendor->remove_cap( $cap );
				}
			}

			foreach ( jet_abaf()->vendors->get_post_type_capabilities( $post_types ) as $cap ) {
				$vendor->add_cap( $cap );
			}
		}

	}

}