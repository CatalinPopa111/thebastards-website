<?php
namespace JET_ABAF\Multivendors;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Restrictions {

	public function __construct() {

		// Restrict admin menu for a custom user role.
		add_action( 'admin_menu', [ $this, 'restrict_admin_menu' ], 9999 );
		// Remove the new content admin bar for a custom user role.
		add_action( 'admin_bar_menu', [ $this, 'restrict_admin_bar_menu' ], 9999 );
		// Restrict dashboard widgets for a custom user role.
		add_action( 'wp_dashboard_setup', [ $this, 'restrict_dashboard_setup' ], 9999 );
		// Limit custom role users to only see their own posts in allowed post types.
		add_action( 'pre_get_posts', [ $this, 'restrict_posts_view' ] );
		// Shop orders empty trash for a custom role (Legacy).
		add_action( 'load-edit.php', [ $this, 'restrict_shop_order_post_deletion' ] );
		// Hide WooCommerce create shop order button for a custom role.
		add_action( 'admin_head', [ $this, 'hide_create_shop_order_button' ] );

		// Limit custom role users to only see related orders (HPOS).
		add_filter( 'woocommerce_orders_table_query_clauses', [ $this, 'restrict_wc_order_list_filter' ] );

	}

	/**
	 * Restrict admin menu.
	 *
	 * Adjusts the admin menu to only display allowed menu items for users with a specific role.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function restrict_admin_menu() {

		// Only apply to a custom user role.
		if ( ! jet_abaf()->vendors->is_booking_vendor( wp_get_current_user() ) ) {
			return;
		}

		global $menu, $submenu;

		foreach ( $menu as $key => $value ) {
			if ( ! in_array( $value[2], jet_abaf()->vendors->get_allowed_pages(), true ) ) {
				remove_menu_page( $value[2] );
			}
		}

		foreach ( $submenu as $parent => $items ) {
			foreach ( $items as $key => $value ) {
				if ( ! in_array( $value[2], jet_abaf()->vendors->get_allowed_pages(), true ) ) {
					remove_submenu_page( $parent, $value[2] );
				}
			}
		}

	}

	/**
	 * Restrict admin bar menu.
	 *
	 * Removes specific items from the WordPress admin bar menu for users with a custom capability.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WordPress Admin Bar instance.
	 *
	 * @return void
	 */
	public function restrict_admin_bar_menu( $wp_admin_bar ) {

		if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
			return;
		}

		if ( jet_abaf()->vendors->is_booking_vendor( wp_get_current_user() ) ) {
			$wp_admin_bar->remove_node( 'new-content' );
		}

	}

	/**
	 * Restrict dashboard setup.
	 *
	 * Resets the dashboard meta boxes and reconfigures the displayed widgets for a user
	 * with a specific role in the WordPress admin area.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function restrict_dashboard_setup() {

		// Only apply to a custom user role.
		if ( ! jet_abaf()->vendors->is_booking_vendor( wp_get_current_user() ) ) {
			return;
		}

		global $wp_meta_boxes;

		// Completely reset the dashboard meta boxes for the vendor role.
		$wp_meta_boxes['dashboard'] = [];

		// Then re-add allowed only widgets.
		if ( is_blog_admin() ) {
			wp_add_dashboard_widget( 'dashboard_activity', __( 'Activity' ), 'wp_dashboard_site_activity' );
		}

		wp_add_dashboard_widget( 'dashboard_primary', __( 'WordPress Events and News' ), 'wp_dashboard_events_news' );

	}

	/**
	 * Restrict posts view.
	 *
	 * Modifies the query object to restrict the visibility of posts for the user with custom capability
	 * in the admin area.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param \WP_Query $query WordPress query object.
	 *
	 * @return void
	 */
	public function restrict_posts_view( $query ) {

		if ( ! is_admin() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );

		if ( ! in_array( $post_type, jet_abaf()->vendors->get_post_types(), true ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			return;
		}

		if ( 'shop_order' === $post_type ) {
			$meta_query = (array) $query->get( 'meta_query' );

			$meta_query[] = [
				'key'     => '__jet_booking_vendor',
				'value'   => $user_id,
				'compare' => '=',
			];

			$query->set( 'meta_query', [ 'relation' => 'AND', ...$meta_query ] );
		} else {
			$query->set( 'author', $user_id );
		}

	}

	/**
	 * Restrict WooCommerce order list filter.
	 *
	 * Modifies the SQL clauses to filter orders based on the current user's booking vendor capability.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @param array $clauses An array of SQL clauses used for fetching WooCommerce orders.
	 *
	 * @return array Modified SQL clauses with additional filtering for booking vendors.
	 */
	public function restrict_wc_order_list_filter( $clauses ) {

		$user_id = get_current_user_id();

		if ( ! jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			return $clauses;
		}

		global $wpdb;

		$clauses['join']  .= " INNER JOIN {$wpdb->prefix}wc_orders_meta AS meta ON meta.order_id = {$wpdb->prefix}wc_orders.id ";
		$clauses['where'] .= " AND meta.meta_key = '__jet_booking_vendor' AND meta.meta_value = '{$user_id}' ";

		return $clauses;

	}

	/**
	 * Restrict shop order post deletion.
	 *
	 * Prevents unauthorized deletion of trashed shop order posts based on the user role and associated vendor.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function restrict_shop_order_post_deletion() {

		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $_GET['post_type'] ) || 'shop_order' !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( empty( $_GET['delete_all'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$user_id = get_current_user_id();

		if ( ! jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			return;
		}

		global $wpdb;

		$wpdb->query( $wpdb->prepare( "
			DELETE posts, postmeta
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS postmeta ON postmeta.post_id = posts.ID
			WHERE posts.post_type = 'shop_order'
				AND posts.post_status = 'trash'
				AND postmeta.meta_key = '__jet_booking_vendor'
				AND postmeta.meta_value = %d
		", $user_id ) );

		wp_safe_redirect( admin_url( 'edit.php?post_status=trash&post_type=shop_order' ) );
		exit;

	}

	/**
	 * Hide WooCommerce create shop order button.
	 *
	 * Hides the "Create" button on the shop orders page for vendors.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function hide_create_shop_order_button() {

		$user_id = get_current_user_id();

		if ( ! jet_abaf()->vendors->is_booking_vendor( $user_id ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( $screen && ( 'shop_order' === $screen->id || 'edit-shop_order' === $screen->id || 'woocommerce_page_wc-orders' === $screen->id ) ) {
			echo '<style> .page-title-action { display: none !important; } </style>';
		}

	}

}