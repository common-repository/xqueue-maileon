<?php
/**
 * Contains the class for footer functionality
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

/**
 * This file is heavily outdated and should be fixed
 */
class XQ_Maileon_Footer {

	/**
	 * Echoes the Maileon popup footer
	 */
	public static function show_footer() {
		// Don't show if the user is logged in.
		if ( is_user_logged_in() ) {
			return;
		}

		// Don't show if it's not enabled in the options.
		if ( ! get_option( 'xq_maileon_footer_enabled', false ) ) {
			return;
		}

		// Don't show if it has already been declared shown in this session.
		if ( isset( $_SESSION['maileon-footer-shown'] ) ) {
			return;
		}

		// Don't show on the page where we post the data.
		if ( is_page( get_option( 'footer_default_post_id' ) ) ) {
			return;
		}

		// Generate the 'captcha'.
		XQ_Maileon_Connector_Utils::generate_captcha();

		// Echo the form.
		require_once __DIR__ . '/../views/maileon-contact-form-footer.php';
	}

	/**
	 * AJAX callback used for storing the closed state of the form
	 */
	public static function close_footer() {
		// We have the PHP session ID in the header, we just don't have a session
		// For this request.
		session_start();

		// Fhow_footer function checks this session variable.
		$_SESSION['maileon-footer-shown'] = true;

		// End the AJAX request and echo '1'.
		wp_send_json_success();
	}
}
