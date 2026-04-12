<?php

namespace JET_ABAF\Workflows\Actions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Send_Email extends Base {

	/**
	 * Get action ID.
	 *
	 * Returns the unique identifier for this action.
	 *
	 * @since 3.7.0
	 *
	 * @return string Action ID.
	 */
	public function get_id() {
		return 'send-email';
	}

	/**
	 * Get action name.
	 *
	 * Returns the human-readable name for this action.
	 *
	 * @since 3.7.0
	 *
	 * @return string Action name.
	 */
	public function get_name() {
		return __( 'Send Email', 'jet-booking' );
	}

	/**
	 * Performs the action.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function do_action() {

		$booking = null;

		if ( ! empty( $this->booking['booking_id'] ) ) {
			$booking = jet_abaf_get_booking( $this->booking['booking_id'] );
		}

		$email_to        = jet_abaf()->macros->macros_handler->do_macros( $this->get_settings( 'email_to' ), $booking );
		$email_subject   = jet_abaf()->macros->macros_handler->do_macros( $this->get_settings( 'email_subject' ), $booking );
		$email_from      = jet_abaf()->macros->macros_handler->do_macros( $this->get_settings( 'email_from' ), $booking );
		$email_from_name = jet_abaf()->macros->macros_handler->do_macros( $this->get_settings( 'email_from_name' ), $booking );
		$email_message   = jet_abaf()->macros->macros_handler->do_macros( $this->get_settings( 'email_message' ), $booking );

		$this->update_settings( 'email_from', $email_from );
		$this->update_settings( 'email_from_name', $email_from_name );

		$this->send_mail( $email_to, $email_subject, $email_message );

	}

	/**
	 * Sends an email using WordPress' function.
	 *
	 * This function prepares and sends an email to the specified recipient.
	 *
	 * @since 3.7.0
	 *
	 * @param string $to      The recipient's email address.
	 * @param string $subject The email subject.
	 * @param string $message The email message content.
	 *
	 * @return void
	 */
	public function send_mail( $to, $subject, $message ) {

		add_filter( 'wp_mail_from', [ $this, 'get_from_address' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		add_filter( 'wp_mail_content_type', [ $this, 'get_content_type' ] );

		do_action( 'jet-booking/workflows/send-email/send-before', $this );

		$content_type = $this->get_content_type();

		if ( 'text/html' === $content_type ) {
			$message = make_clickable( wpautop( $message ) );
		}

		$message = str_replace( '&#038;', '&amp;', $message );
		$message = stripcslashes( $message );

		$sent = wp_mail( $to, $subject, $message, $this->get_headers() );

		if ( ! $sent ) {
			error_log( $message );
		}

		remove_filter( 'wp_mail_from', [ $this, 'get_from_address' ] );
		remove_filter( 'wp_mail_from_name', [ $this, 'get_from_name' ] );
		remove_filter( 'wp_mail_content_type', [ $this, 'get_content_type' ] );

		do_action( 'jet-booking/workflows/send-email/send-after', $this );
	}

	/**
	 * Get headers.
	 *
	 * Get headers for the email.
	 *
	 * @since 3.7.0
	 *
	 * @return string The headers for the email.
	 */
	public function get_headers() {

		$headers = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
		$headers .= "Reply-To: {$this->get_from_address()}\r\n";
		$headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";

		return apply_filters( 'jet-booking/workflows/send-email/headers', $headers, $this );

	}

	/**
	 * Get from address.
	 *
	 * Returns the email address of the sender.
	 *
	 * @since 3.7.0
	 *
	 * @return string The email address to use as the sender.
	 */
	public function get_from_address() {

		$address = $this->get_settings( 'email_from' );

		if ( empty( $address ) || ! is_email( $address ) ) {
			$address = get_option( 'admin_email' );
		}

		return apply_filters( 'jet-booking/workflows/send-email/from-address', $address, $this );

	}

	/**
	 * Get from name.
	 *
	 * Returns the sender's name for the email.
	 *
	 * @since 3.7.0
	 *
	 * @return string The sender's name for the email.
	 */
	public function get_from_name() {

		$name = $this->get_settings( 'email_from_name' );
		$name = ! empty( $name ) ? $name : get_bloginfo( 'name' );

		return apply_filters( 'jet-booking/workflows/send-email/from-name', wp_specialchars_decode( $name ), $this );

	}

	/**
	 * Get content type.
	 *
	 * Returns the content type for the email.
	 *
	 * @since 3.7.0
	 *
	 * @return string The content type for the email.
	 */
	public function get_content_type() {
		return apply_filters( 'jet-booking/workflows/send-email/content-type', 'text/html', $this );
	}

	/**
	 * Registers action controls for the workflow.
	 *
	 * This method is responsible for adding controls to the send email action.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public function register_action_controls() {
		include JET_ABAF_PATH . 'includes/workflows/templates/actions/send-email-controls.php';
	}

}