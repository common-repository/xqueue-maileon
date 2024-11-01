<?php
/**
 * This file contains the base form functionality
 *
 * @package xqueue-maileon
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

// Require the the base class.
require_once 'class-xq-maileon-connector-utils.php';

use de\xqueue\maileon\api\client\contacts\ContactsService;
use de\xqueue\maileon\api\client\contacts\Contact;
use de\xqueue\maileon\api\client\contacts\Permission;
use de\xqueue\maileon\api\client\contacts\Preference;
use de\xqueue\maileon\api\client\contacts\SynchronizationMode;
use de\xqueue\maileon\api\client\MaileonAPIException;
use de\xqueue\maileon\api\client\MaileonAPIResult;

/**
 * The base form functionality class
 */
class XQ_Maileon extends XQ_Maileon_Connector_Utils {
	/**
	 * The message to be displayed
	 *
	 * @var string
	 */
	protected static $message_to_display = '';

	/**
	 * Initialize the autoloader and the maileon static classes
	 */
	public static function init() {
		// Exit function if not on front-end.
		if ( is_admin() ) {
			return;
		}

		if ( headers_sent() && '' === session_id() ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Unable to initialize session as headers were already sent. Features like sesseion based captcha will not work, use reCaptcha instead.' );
		}

		if ( ! headers_sent() && '' === session_id() ) {
			session_start();
		}

		// Regular subscription form.
		wp_register_script(
			'maileon-contact-form-js',
			plugins_url( '../js/maileon-contact-form.js', __FILE__ ),
			array( 'jquery' ),
			XQ_MAILEON_PLUGIN_VERSION,
			array( 'in_footer' => false )
		);
		wp_register_style(
			'maileon-contact-form-css',
			plugins_url( '../css/maileon-contact-form.css', __FILE__ ),
			array(),
			XQ_MAILEON_PLUGIN_VERSION
		);

		add_action( 'wp_enqueue_scripts', array( 'XQ_Maileon', 'enqueue_scripts' ) );

		add_shortcode( 'maileon-contact', array( 'XQ_Maileon', 'shortcode' ) );

		// if the <form> element is POSTed, run the following code.
		$request_method = filter_var( isset( $_SERVER['REQUEST_METHOD'] ) ? wp_unslash( $_SERVER['REQUEST_METHOD'] ) : 'GET' );
		$form_type      = filter_input( INPUT_POST, 'form_type' );

		if ( 'POST' === $request_method && 'new' === $form_type ) {
			// sanitize the form data, validate and check the input,
			// and register the email address.
			$nonce     = wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'xq_maileon' );
			$form_data = self::sanitize_form_data( $_POST );

			// Check if there are hardcoded standard and custom fields, add them to the form data for later processing.
			$info = self::form_posted( $form_data );

			// Check if there was an 'input' or maileon 'error'.
			$success = $nonce && false === $info['input']['error'] && false === $info['maileon']['error'];

			// Set the message to the 'input' or 'maileon' error message, or to the
			// Defined success message if there weren't any errors.
			$message = true === $info['input']['error'] ?
				$info['input']['message'] :
				( true === $info['maileon']['error'] ? $info['maileon']['message'] : get_option( 'success_message', __( 'You have succesfully subscribed to our newsletter, please check your inbox for the confirmation mail.', 'xq_maileon' ) ) );

			if ( $success ) {
				// Overrides.
				if ( ! empty( $form_data['ok_page_id'] ) ) {
					wp_safe_redirect( get_permalink( $form_data['ok_page_id'] ) );
					exit;
				} else {
					// If there wasn't an error display the OK page or only the message.
					$page_title = get_option( 'PAGE_OK' );
					if ( ! empty( $page_title ) ) {
						$page = self::get_page_by_title( $page_title );
						if ( ! empty( $page ) ) {
							wp_safe_redirect( get_permalink( $page->ID ) );
							exit;
						} else {
							// As providing the title is maybe not fitting, give possibility to provide path.
							$page = get_page_by_path( $page_title );
							if ( ! empty( $page ) ) {
								wp_safe_redirect( get_permalink( $page->ID ) );
								exit;
							} else {
								// Fallback if page is specified but not found.
								// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
								error_log( sprintf( 'Unable to find page with title \'%s\' (successful subscription). Displaying message instead. Please fix the entry in your Maileon configuration.', $page_title ) );
								self::$message_to_display = self::show_message( $message );
							}
						}
					} else {
						self::$message_to_display = self::show_message( $message );
					}
				}
			} elseif ( ! empty( $form_data['error_page_id'] ) ) {
				wp_safe_redirect( get_permalink( $form_data['ok_page_id'] ) );
				exit;
			} else {
				// Else display the error page or the error message and the form.
				$template = ( ! empty( $form_data['template'] ) ) ? $form_data['template'] : '';

				$page_title = get_option( 'PAGE_ERROR' );
				if ( ! empty( $page_title ) ) {
					$page = self::get_page_by_title( $page_title );
					if ( ! empty( $page ) ) {
						wp_safe_redirect( get_permalink( $page->ID ) );
						exit;
					} else {
						// As providing the title is maybe not fitting, give possibility to provide path.
						$page = get_page_by_path( $page_title );
						if ( ! empty( $page ) ) {
							wp_safe_redirect( get_permalink( $page->ID ) );
							exit;
						} else {
							// Fallback if page is specified but not found.
							// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
							error_log( sprintf( 'Unable to find page with title \'%s\' (successful subscription). Displaying message instead. Please fix the entry in your Maileon configuration.', $page_title ) );
							self::$message_to_display = self::show_form( $message, $form_data, $template );
						}
					}
				} else {
					self::$message_to_display = self::show_form( $message, $form_data, $template );
				}
			}
		}
	}

	/**
	 * Enqueues the registered JS/style scripts
	 */
	public static function enqueue_scripts() {
		// Regular newsletter subscription for Maileon.
		wp_enqueue_script( 'maileon-contact-form-js' );
		wp_enqueue_style( 'maileon-contact-form-css' );

		// Add the script required for using Addresscheck, if access data has been filled.
		if ( 'true' === get_option( 'adc_enabled' ) && ! empty( get_option( 'adc_api_user' ) ) && ! empty( get_option( 'adc_api_key' ) ) ) {
			// Register script for ADC.
			wp_register_script(
				'maileon-contact-form-adc',
				plugins_url( '../js/maileon-contact-form-adc.js', __FILE__ ),
				array( 'jquery' ),
				XQ_MAILEON_PLUGIN_VERSION,
				array( 'in_footer' => false )
			);

			wp_enqueue_script( 'maileon-contact-form-adc' );
			$email_field = get_option( 'adc_email_field', 'maileon_contact_form_email' );
			wp_localize_script(
				'maileon-contact-form-adc',
				'xqueue_adc_ajax',
				array(
					'url'             => '?xq_adc_ajax',
					'adc_input_delay' => get_option( 'adc_input_delay' ),
					'adc_email_field' => $email_field,
				)
			);
		}

		// Add the script required for using reCaptcha, if access data has been filled.
		if ( 'true' === get_option( 'maileon_recaptcha_enabled' ) && ! empty( get_option( 'maileon_recaptcha_site_key' ) ) && ! empty( get_option( 'maileon_recaptcha_secret_key' ) ) ) {
			$site_key = get_option( 'maileon_recaptcha_site_key' );
			// Load the personalized recaptcha script.
			wp_register_script(
				'recaptchav3',
				'https://www.google.com/recaptcha/api.js?render=' . $site_key,
				array( 'jquery' ),
				XQ_MAILEON_PLUGIN_VERSION,
				array( 'in_footer' => false )
			);

			// Register script for reCaptcha.
			wp_register_script(
				'maileon-contact-form-recaptcha',
				plugins_url( '../js/maileon-contact-form-recaptcha.js', __FILE__ ),
				array( 'jquery', 'recaptchav3' ),
				XQ_MAILEON_PLUGIN_VERSION,
				array( 'in_footer' => false )
			);
			wp_enqueue_script( 'maileon-contact-form-recaptcha' );
			wp_localize_script( 'maileon-contact-form-recaptcha', 'xqueue_recaptcha', array( 'site_key' => $site_key ) );
		}

		// provide the WordPress AJAX url for the javascript file.
		wp_localize_script( 'maileon-contact-form-js', 'maileon_ajax', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * The shortcode function
	 *
	 * @param array $attributes Attributes of the shortcode.
	 * @return string HTML code to replace the shortcode with
	 */
	public static function shortcode( $attributes ) {
		if ( is_admin() ) {
			return;
		}

		// messageToDisplay is filled when there was a post (and no redirect) => return it. Otherwise return the form.
		// also check if this is the form currently being posted before returning the message.
		if ( ! empty( self::$message_to_display ) && self::is_currently_posted( $attributes ) ) {
			return self::$message_to_display;
		} else {
			// In case no attribute is set, instantiate here.
			if ( ! isset( $attributes ) || 'string' === gettype( $attributes ) ) {
				$attributes = array();
			}

			// Prepare the template file name.
			$template = null;
			if ( ! empty( $attributes['template'] ) ) {
				$template = $attributes['template'];
			}

			$attributes['form_type'] = 'new';

			// If this wasn't POSTed, just display the form.
			return self::show_form( null, null, $template, $attributes );
		}
	}

	/**
	 * The function that gets called when the form was POSTed to this page/post
	 *
	 * @param array $form_data The form data.
	 * @return array Information about the succes of maileon operations
	 */
	public static function form_posted( $form_data ) {
		$info = array(
			'input'   => array(
				'error'   => false,
				'message' => '',
			),
			'maileon' => array(
				'error'   => false,
				'message' => '',
			),
		);

		$email = isset( $form_data['email'] ) ? trim( $form_data['email'] ) : '';

		$captcha = null;
		if ( 'true' === get_option( 'captcha_enabled' ) ) {
			$captcha = isset( $form_data['maileon-captcha'] ) ? trim( $form_data['maileon-captcha'] ) : '';
		} elseif ( 'true' === get_option( 'maileon_recaptcha_enabled' ) ) {
			$captcha = isset( $form_data['recaptcha'] ) ? trim( $form_data['recaptcha'] ) : '';
		}

		$info['input'] = self::validate_values( $email, $captcha );

		// if the address passes the quality check too, register the contact.
		if ( false === $info['input']['error'] ) {
			$info['maileon'] = self::register_contact( $form_data );
		}

		return $info;
	}

	/**
	 * Attempts to register a contact through Maileon, and returns success information
	 *
	 * @param array $form_data The data including all form fields.
	 * @return array Information
	 */
	public static function register_contact( $form_data ) {
		$attributes                      = array();
		$attributes['debug']             = ( ! empty( $form_data['debug'] ) ) ? $form_data['debug'] : 'false';
		$attributes['doi']               = ( ! empty( $form_data['doi'] ) ) ? $form_data['doi'] : 'true';
		$attributes['doiplus']           = ( ! empty( $form_data['doiplus'] ) ) ? $form_data['doiplus'] : 'false';
		$attributes['doimailing']        = ( ! empty( $form_data['doimailing'] ) ) ? $form_data['doimailing'] : '';
		$attributes['permission']        = ( ! empty( $form_data['permission'] ) ) ? $form_data['permission'] : '1';
		$attributes['sync_mode']         = ( ! empty( $form_data['sync_mode'] ) ) ? $form_data['sync_mode'] : '1';
		$attributes['src']               = ( ! empty( $form_data['src'] ) ) ? $form_data['src'] : 'wordpress_plugin';
		$attributes['subscription_page'] = ( ! empty( $form_data['subscription_page'] ) ) ? $form_data['subscription_page'] : '';

		$info = array(
			'error'   => false,
			'message' => get_option( 'success_message', __( 'You have succesfully subscribed to our newsletter, please check your inbox for the confirmation mail.', 'xq_maileon' ) ),
		);

		// Get the email from field 'email'.
		$email = $form_data['email'];

		$standard_fields   = self::extract_maileon_fields( $form_data, 'standard' );
		$custom_fields     = self::extract_maileon_fields( $form_data, 'custom' );
		$preference_fields = self::extract_maileon_fields( $form_data, 'preference' );

		try {
			// call the maileon api.
			$result = self::create_contact(
				$email,
				'true' === $attributes['doi'],
				'true' === $attributes['doiplus'],
				$attributes['doimailing'],
				intval( $attributes['permission'] ),
				intval( $attributes['sync_mode'] ),
				$standard_fields,
				$custom_fields,
				'true' === $attributes['debug'] || 'true' === get_option( 'maileon_debug' ),
				$preference_fields,
				$attributes['src'],
				$attributes['subscription_page']
			);
		} catch ( MaileonAPIException $ex ) {
			// we only display a general 'error' error message to users
			// in case of exceptions.
			$info['error']   = true;
			$info['message'] = get_option( 'error_maileon', __( 'An error occured with Maileon. Please try again later.', 'xq_maileon' ) );

			ob_start();
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( $ex );
			$content = ob_get_clean();

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Call to maileon failed: ' . $content );
		}

		if ( ! empty( $result ) ) {
			if ( ! $result->isSuccess() ) {
				// if we didn't get a result that also means a communication problem.
				$info['error'] = true;

				ob_start();
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
				var_dump( $result );
				$content = ob_get_clean();

				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Call to maileon failed: ' . $content );

				if ( get_option( 'maileon_debug' ) ) {
					$info['message'] = 'Call to maileon failed: ' . $content;
				} else {
					$info['message'] = get_option( 'error_maileon', __( 'An error occured with Maileon. Please try again later.', 'xq_maileon' ) );
				}
			}
			$info['response'] = $result;
		}

		return $info;
	}

	/**
	 * Attempts to register a contact using the CreateContact Maileon call
	 *
	 * @param string  $email Contact email.
	 * @param boolean $doi use doi process or not.
	 * @param boolean $doiplus use doi+ or not.
	 * @param string  $doimailing the key of the DOI mailing to use.
	 * @param int     $permission_code initial permission (code).
	 * @param int     $sync_mode_code Sync mode (code).
	 * @param array   $standard_fields Map of standard fields and values.
	 * @param array   $custom_fields  Map of CF and values.
	 * @param boolean $debug Boolean that enables or disables debug output for rest service.
	 * @param array   $preference_fields Map of PreferenceCategory:PreferenceName and values.
	 * @param string  $src The subscription source.
	 * @param string  $subscription_page The subscription page.
	 * @return MaileonAPIResult
	 */
	private static function create_contact( $email, $doi, $doiplus, $doimailing, $permission_code, $sync_mode_code, $standard_fields, $custom_fields, $debug = false, $preference_fields = null, $src = '', $subscription_page = '' ) {
		$contacts_service = new ContactsService( self::get_maileon_config() );

		$contacts_service->setDebug( $debug );

		if ( ! empty( $sync_mode_code ) ) {
			$sync_mode = SynchronizationMode::getSynchronizationMode( $sync_mode_code );
		} else {
			// Default is now: update.
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$sync_mode = SynchronizationMode::$UPDATE;
		}

		if ( ! empty( $permission_code ) ) {
			$permission = Permission::getPermission( $permission_code );
		} else {
			// Default is now: update.
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$permission = Permission::$NONE;
		}

		// set up the contact object: name and email address.
		$contact = new Contact();

		$contact->email           = $email;
		$contact->standard_fields = $standard_fields;
		$contact->custom_fields   = $custom_fields;

		// Split category:preference.
		$contact->preferences = array();
		if ( ! empty( $preference_fields ) ) {
			foreach ( $preference_fields as $preference_field => $value ) {
				$preference_information = explode( ':', $preference_field );
				if ( count( $preference_information ) < 2 ) {
					continue;
				}

				$category_name   = $preference_information[0];
				$preference_name = $preference_information[1];

				$contact->preferences[] = new Preference( $preference_name, null, $category_name, $value );
			}
		}

		if ( $doi ) {
			// if there should be a DOI process, start one.
			$contact->permission = $permission;

			$result = $contacts_service->createContact( $contact, $sync_mode, $src, $subscription_page, $doi, $doiplus, $doimailing );
		} else {
			// otherwise just register the user with DOI+ permission.
			$contact->permission = $permission;

			$result = $contacts_service->createContact( $contact, $sync_mode, $src, $subscription_page );
		}

		if ( $debug ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( $result );
			die( 'debug parameter set' );
		}

		return $result;
	}
}
