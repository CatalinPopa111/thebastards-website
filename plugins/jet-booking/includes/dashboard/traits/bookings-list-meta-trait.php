<?php
namespace JET_ABAF\Dashboard\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Bookings_List_Meta_Trait {

	/**
	 * Post-type holder.
	 *
	 * @var array
	 */
	public $post_type;


	/**
	 * Render the meta-box content.
	 *
	 * Outputs the HTML structure for the meta-box.
	 *
	 * @since 3.8.0
	 *
	 * @return void
	 */
	public function render_meta_box() {
		echo '<div id="jet_abaf_bookings_list_meta"></div>';
	}

	/**
	 * Initializes the bookings list meta functionality for specific admin pages.
	 *
	 * Enqueues necessary scripts and assets, processes bookings data, and localizes it for use in the admin interface.
	 *
	 * @since 3.8.0
	 *
	 * @param string $hook The current admin page hook identifier.
	 *
	 * @return void
	 */
	public function init_bookings_list_meta( $hook ) {

		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
			return;
		}

		if ( ! in_array( get_post_type(), $this->post_type ) ) {
			return;
		}

		$ui_data = jet_abaf()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );

		$ui->enqueue_assets();

		wp_enqueue_script(
			'jet-abaf-bookings-list-meta',
			JET_ABAF_URL . 'assets/js/admin/bookings-list-meta.js',
			[],
			JET_ABAF_VERSION,
			true
		);

		wp_enqueue_style(
			'jet-abaf-bookings-list-meta',
			JET_ABAF_URL . 'assets/css/admin/bookings-list-meta.css',
			[],
			JET_ABAF_VERSION
		);

		global $post;

		$post_id  = jet_abaf()->db->get_initial_booking_item_id( $post->ID );
		$is_order = in_array( jet_abaf()->settings->get( 'related_post_type' ), $this->post_type );

		if ( ! $is_order ) {
			$bookings = jet_abaf()->db->get_future_bookings( $post_id );
		} else {
			$bookings = jet_abaf_get_bookings( [
				'order_id' => $post_id,
				'return'   => 'arrays',
			] );
		}

		$bookings = array_map( function ( $booking ) {
			$booking['check_in_date']  = date_i18n( get_option( 'date_format' ), $booking['check_in_date'] );
			$booking['check_out_date'] = date_i18n( get_option( 'date_format' ), $booking['check_out_date'] );

			return $booking;
		}, $bookings );

		wp_localize_script( 'jet-abaf-bookings-list-meta', 'JetABAFBookingsListMetaData', [
			'api'           => jet_abaf()->rest_api->get_urls( false ),
			'bookings'      => $bookings,
			'booking_posts' => jet_abaf()->tools->get_booking_posts_list(),
			'bookings_link' => add_query_arg( [ 'page' => 'jet-abaf-bookings', ], admin_url( 'admin.php' ) ),
			'edit_link'     => add_query_arg( [ 'post' => '%id%', 'action' => 'edit', ], admin_url( 'post.php' ) ),
			'is_order'      => $is_order,
		] );

		add_action( 'admin_footer', [ $this, 'bookings_list_meta_template' ] );

	}

	/**
	 * Bookings list meta-template.
	 *
	 * Outputs the bookings list meta-template for use in the admin interface.
	 *
	 * @since 3.8.0
	 *
	 * @return void
	 */
	public function bookings_list_meta_template() {
		ob_start();
		include JET_ABAF_PATH . 'templates/admin/post-meta/bookings-list-meta.php';
		printf( '<script type="text/x-template" id="jet-abaf-bookings-list-meta">%s</script>', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}