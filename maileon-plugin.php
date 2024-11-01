<?php
/**
 * Plugin Name: Maileon Newsletter Subscription
 * Description: A simple plugin providing a shortcode for newsletter subscription and profile update pages
 * Version: 2.16.3
 * Author: XQueue GmbH <integrations@xqueue.com>
 *
 * From version 2.12.0 we use wp4xq as method prefix to avoid conflicts (WordPress for XQueue)
 *
 * @package xqueue-maileon
 */

// Make sure we don't expose any info if called directly.
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! defined( 'XQ_MAILEON_PLUGIN_VERSION' ) ) {
	define( 'XQ_MAILEON_PLUGIN_VERSION', '2.16.0' );
}

/**
 * Load translation files
 *
 * @return void
 */
function wp4xq_multilingual_init() {
	load_plugin_textdomain( 'xq_maileon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wp4xq_multilingual_init' );

require_once 'class/class-xq-maileon-admin.php';
require_once 'class/class-xq-maileon-footer.php';
require_once 'class/class-xq-maileon-profile-update.php';
require_once 'class/class-xq-maileon.php';


// Add class and action for ADC.
require_once 'class/class-xq-adc.php';
add_action( 'init', array( 'XQ_Adc', 'init' ) );


// Admin settings.
$admin_config = new XQ_Maileon_Admin();
register_activation_hook( __FILE__, array( $admin_config, 'init' ) );
add_action( 'admin_menu', array( $admin_config, 'admin_menu' ) );
add_action( 'admin_init', array( $admin_config, 'init' ) );

add_action( 'init', array( 'XQ_Maileon', 'init' ) );

add_action( 'init', array( 'XQ_Maileon_Profile_Update', 'init' ) );

add_action( 'wp_footer', array( 'XQ_Maileon_Footer', 'show_footer' ) );
add_action( 'wp_ajax_nopriv_maileon_close_footer', array( 'XQ_Maileon_Footer', 'close_footer' ) );
add_action( 'wp_ajax_maileon_close_footer', array( 'XQ_Maileon_Footer', 'close_footer' ) );
add_action( 'xq_maileon_captcha', array( 'XQ_Maileon_Connector_Utils', 'show_captcha' ) );

wp4xq_check_if_curl_is_installed();

/**
 * For some customers it was not possible to forward them to a page after subscribing to mailein (using wp_redirect)
 * as headers were already sent by another plugin (?).
 * According to Stackoverflow, buffering will prevent this from happening (see referenced link in the below article)
 * See https://wordpress.stackexchange.com/questions/81566/wp-redirect-headers-already-sent-after-front-end-submission-form
 * To not completely destroy serverside caching, we only register this when POSTing data
 *
 * @return void
 */
function wp4xq_register_app_output_buffer() {
	if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
		$method = filter_var( isset( $_SERVER['REQUEST_METHOD'] ) ? wp_unslash( $_SERVER['REQUEST_METHOD'] ) : 'GET' );
		if ( strtoupper( $method ) === 'POST' ) {
			ob_start();
		}
	}
}
add_action( 'init', 'wp4xq_register_app_output_buffer' );


/**
 * Check if cURL is installed, else: show warning
 *
 * @return void
 */
function wp4xq_check_if_curl_is_installed() {
	if ( ! function_exists( 'curl_version' ) ) {
		add_action( 'admin_notices', 'wp4xq_no_curl_admin_error' );
	}
}

/**
 * Display an admin notice for missing curl
 *
 * @return void
 */
function wp4xq_no_curl_admin_error() {
	?>
	<div class="notice error xqError" >
		<p><b>Maileon Newsletter Subscription:</b><br /> cURL for PHP seems to be not installed or disabled. It is required to use the Maileon REST-API, please install or enable cURL and make sure the firewall allows outgoing connections to
			'[https:\\]api.maileon.com:443'.</p>
	</div>
	<?php
}

// ------------------------------ Sidebar ------------------------------
// Add the sidebar widget.
require_once 'class-xq-maileon-subscription-widget.php';

/**
 * Register the subscription widget
 *
 * @return void
 */
function wp4xq_register_xq_subscription_widget() {
	register_widget( 'XQ_Maileon_Subscription_Widget' );
}
add_action( 'widgets_init', 'wp4xq_register_xq_subscription_widget' );

// Add the AJAX code for the sidebar ajax call_user_func.
add_action( 'wp_ajax_xq_subscription', 'wp4xq_prefix_ajax_xq_subscription' );
add_action( 'wp_ajax_nopriv_xq_subscription', 'wp4xq_prefix_ajax_xq_subscription' );

/**
 * Add the AJAX code for the sidebar ajax call_user_func
 *
 * @return void
 */
function wp4xq_prefix_ajax_xq_subscription() {
	// Get the jQuery serialized form data.
	$form_data = array();
	parse_str( filter_input( INPUT_POST, 'data', FILTER_DEFAULT ), $form_data );
	$result = XQ_Maileon::register_contact( $form_data );
	wp_send_json( $result );
}
// ------------------------------ End Sidebar ------------------------------
