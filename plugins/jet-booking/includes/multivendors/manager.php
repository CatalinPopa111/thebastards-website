<?php
namespace JET_ABAF\Multivendors;

use WP_Roles;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Manager {

	/**
	 * Holds the user role identifier.
	 *
	 * @var string
	 */
	public $role = 'jet_booking_vendor';

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	public $settings;

	public function __construct() {

		// Pre-select user role based on URL parameter.
		add_action( 'admin_footer-user-new.php', [ $this, 'preselect_user_role' ] );

		$this->settings = new Settings();

		new Capabilities();
		new Restrictions();

	}

	/**
	 * Create custom user roles.
	 *
	 * Creates a new user role with specific permissions and adds custom
	 * capabilities for managing assigned post types.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function create_roles() {

		$this->remove_roles();

		add_role( $this->role, __( 'JetBooking Vendor', 'jet-booking' ), $this->get_base_capabilities() );

		if ( $vendor = get_role( $this->role ) ) {
			foreach ( $this->get_post_type_capabilities() as $cap ) {
				$vendor->add_cap( $cap );
			}
		}

		if ( $admin = get_role( 'administrator' ) ) {
			$admin->add_cap( \JET_ABAF\Capabilities::CAP_MANAGE_POST );
			$admin->add_cap( \JET_ABAF\Capabilities::CAP_MANAGE_OTHERS_POST );
		}

	}

	/**
	 * Remove user roles.
	 *
	 * Removes a specific user role if it exists in the system.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function remove_roles() {

		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$wp_roles->remove_cap( 'administrator', \JET_ABAF\Capabilities::CAP_MANAGE_POST );
		$wp_roles->remove_cap( 'administrator', \JET_ABAF\Capabilities::CAP_MANAGE_OTHERS_POST );

		if ( $wp_roles->is_role( $this->role ) ) {
			remove_role( $this->role );
		}

	}

	/**
	 * Check if the user is a booking vendor.
	 *
	 * Determines whether the given user has the role associated with booking vendors.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param \WP_User|int $user User object or user ID to check.
	 *
	 * @return bool True if the user is a booking vendor, false otherwise.
	 */
	public function is_booking_vendor( $user ) {

		if ( ! is_object( $user ) ) {
			$user = get_userdata( $user );
		}

		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		return in_array( $this->role, $user->roles, true );

	}

	/**
	 * Get vendor.
	 *
	 * Retrieves the vendor (post author) of a given post if the post exists
	 * and the author is a booking vendor.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param int|\WP_Post $post Post ID or post object.
	 *
	 * @return string|null The post author's ID if they are a booking vendor, or null if not applicable.
	 */
	public function get_vendor( $post ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return null;
		}

		if ( ! $this->is_booking_vendor( $post->post_author ) ) {
			return null;
		}

		return $post->post_author;

	}

	/**
	 * Get vendors list.
	 *
	 * Retrieves a list of vendors based on the specified user role.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array Associative array of vendors with user IDs as keys and display names as values.
	 */
	public function get_vendors_list() {

		$results = [];

		foreach ( get_users( [ 'role' => $this->role ] ) as $user ) {
			$results[ $user->ID ] = $user->display_name;
		}

		return $results;

	}

	/**
	 * Get extended vendor list.
	 *
	 * Retrieves a list of vendors with detailed information including their posts, bookings,
	 * and profile links.
	 *
	 * @since   4.0.0
	 * @access  public
	 *
	 * @return array
	 */
	public function get_extended_vendor_list() {

		$results = [];
		$users   = get_users( [ 'role' => $this->role ] );

		if ( empty( $users ) ) {
			return $results;
		}

		$post_types = jet_abaf()->settings->get( 'apartment_post_type' );

		foreach ( $users as $user ) {
			$posts = [];

			foreach ( $post_types as $post_type ) {
				$post_type_obj = get_post_type_object( $post_type );

				if ( ! $post_type_obj ) {
					continue;
				}

				$count = count_user_posts( $user->ID, $post_type );

				$posts[ $post_type ] = [
					'name'  => $post_type_obj->labels->name ?? $post_type,
					'count' => $count,
				];

				if ( $count > 0 ) {
					$posts[ $post_type ]['url'] = add_query_arg( [
						'post_type' => $post_type,
						'author'    => $user->ID,
					], admin_url( 'edit.php' ) );
				}
			}

			$bookings_count    = count( jet_abaf_get_bookings( [ 'booking_vendor' => $user->ID ] ) );
			$bookings['count'] = $bookings_count;

			if ( $bookings_count > 0 ) {
				$bookings['url'] = add_query_arg( [
					'page'   => 'jet-abaf-bookings',
					'vendor' => $user->ID,
				], admin_url( 'admin.php' ) );
			}

			$results[ $user->ID ] = [
				'username' => $user->user_login,
				'name'     => trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name,
				'email'    => $user->user_email,
				'posts'    => $posts,
				'bookings' => $bookings,
				'profile'  => get_edit_user_link( $user->ID ),
			];
		}

		return $results;

	}

	/**
	 * Get base capabilities.
	 *
	 * Retrieves the default set of capabilities assigned to the base user role.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array Associative array of capability names and their assigned boolean values.
	 */
	public function get_base_capabilities() {
		return [
			'read'                                  => true,
			'upload_files'                          => true,
			'edit_posts'                            => true,
			'edit_published_posts'                  => true,
			'delete_posts'                          => true,
			'delete_published_posts'                => true,
			\JET_ABAF\Capabilities::CAP_MANAGE_POST => true,
		];
	}

	/**
	 * Get post type capabilities.
	 *
	 * Retrieves the capabilities for the specified post types.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param string|array|null $post_types Optional. A single post type slug, an array of post type slugs,
	 *                                      or null to retrieve all registered post types. Default null.
	 *
	 * @return array An array of capability strings associated with the specified post types.
	 */
	public function get_post_type_capabilities( $post_types = null ) {

		if ( null === $post_types ) {
			$post_types = $this->get_post_types();
		}

		$capabilities = [];

		foreach ( $this->get_capability_types( $post_types ) as $capability_type ) {
			$capabilities[] = "edit_{$capability_type}";
			$capabilities[] = "edit_{$capability_type}s";
			$capabilities[] = "edit_published_{$capability_type}s";
			$capabilities[] = "delete_{$capability_type}";
			$capabilities[] = "delete_{$capability_type}s";
			$capabilities[] = "delete_published_{$capability_type}s";

			if ( 'shop_order' === $capability_type ) {
				$capabilities[] = "edit_others_{$capability_type}s";
				$capabilities[] = "delete_others_{$capability_type}s";
			}
		}

		return $capabilities;

	}

	/**
	 * Get post types.
	 *
	 * Retrieves and merges the specified post types with default additional types.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array|null $post_types An array of post types.
	 *
	 * @return array Merged array of provided post types and additional default types.
	 */
	public function get_post_types( $post_types = null ) {

		if ( null === $post_types ) {
			$post_types = jet_abaf()->settings->get( 'apartment_post_type' );

		}

		$post_types        = jet_abaf()->tools->ensure_array( $post_types );
		$related_post_type = jet_abaf()->settings->get( 'related_post_type' );

		if ( $related_post_type ) {
			$post_types[] = $related_post_type;
		}

		if ( jet_abaf()->settings->wc_integration_enabled() || 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			$post_types[] = 'shop_order';
		}

		$post_types[] = 'attachment';

		return array_unique( array_filter( $post_types ) );

	}

	/**
	 * Get capability types.
	 *
	 * Retrieves a unique list of capability types associated with the specified post types.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array|null $post_types An array of post type.
	 *
	 * @return array An array of unique capability types as strings.
	 */
	public function get_capability_types( $post_types = null ) {

		if ( null === $post_types ) {
			$post_types = $this->get_post_types();
		}

		$capability_types = [];

		foreach ( $post_types as $post_type ) {
			$object = get_post_type_object( $post_type );

			if ( ! $object ) {
				continue;
			}

			$capability_type = $object->capability_type ?? null;

			if ( is_array( $capability_type ) && ! empty( $capability_type ) ) {
				$capability_type = reset( $capability_type );
			}

			if ( ! empty( $capability_type ) && 'post' !== $capability_type ) {
				$capability_types[] = $capability_type;
			}
		}

		return array_unique( $capability_types );

	}

	/**
	 * Get allowed pages.
	 *
	 * Retrieves a list of pages that the current user is allowed to access based on the registered post types.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return array List of allowed page URLs.
	 */
	public function get_allowed_pages() {

		$allowed_pages = [
			// Dashboard
			'index.php',

			// Media
			'async-upload.php',
			'upload.php',

			// Users
			'profile.php',

			// About
			'about.php',
			'contribute.php',
			'credits.php',
			'freedoms.php',
			'privacy.php',

			// JetBooking
			'jet-abaf-bookings',
			'jet-abaf-calendars',
			'jet-abaf-settings',
		];

		if ( jet_abaf()->wc->can_edit_wc_order() ) {
			array_push( $allowed_pages, 'woocommerce', 'wc-orders' );
		}

		foreach ( $this->get_post_types() as $post_type ) {
			if ( 'post' === $post_type ) {
				$allowed_pages[] = 'edit.php';
			} else {
				$allowed_pages[] = "edit.php?post_type={$post_type}";
			}
		}

		return $allowed_pages;

	}

	/**
	 * Preselects a user role in an admin interface.
	 *
	 * Checks if a user role is provided via query parameters, validates it, and preselects
	 * the role on the interface using JavaScript.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void This method does not return a value.
	 */
	public function preselect_user_role() {

		$role = isset( $_GET['role'] ) ? sanitize_key( $_GET['role'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $role ) ) {
			return;
		}

		if ( $role !== $this->role ) {
			return;
		}

		$wp_roles = wp_roles();

		if ( ! $wp_roles->is_role( $role ) ) {
			return;
		}

		echo '
			<script>
                jQuery( document ).ready( function( $ ) {
                    $( "#role" ).val( "' . esc_js( $role ) . '" );
                } );
            </script>
        ';

	}

}
