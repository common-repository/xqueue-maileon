<?php
/**
 * This file contains the profile update functionality
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
 * The class used to handle profile updating
 */
class XQ_Maileon_Profile_Update extends XQ_Maileon_Connector_Utils {
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

		add_action( 'wp_enqueue_scripts', array( 'XQ_Maileon_Profile_Update', 'enqueue_scripts' ) );

		add_shortcode( 'maileon-contact-update', array( 'XQ_Maileon_Profile_Update', 'shortcode' ) );

		// if the <form> element is POSTed, run the following code.
		$request_method = filter_var( isset( $_SERVER['REQUEST_METHOD'] ) ? wp_unslash( $_SERVER['REQUEST_METHOD'] ) : 'GET' );
		$form_type      = filter_input( INPUT_POST, 'form_type' );

		if ( 'POST' === $request_method && 'update' === $form_type ) {
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
				( true === $info['maileon']['error'] ? $info['maileon']['message'] : get_option( 'success_update_message', __( 'You have succesfully subscribed to our newsletter, please check your inbox for the confirmation mail.', 'xq_maileon' ) ) );

			// Reset cid and cs in session.
			$_SESSION['cid'] = '';
			$_SESSION['cs']  = '';

			if ( $success ) {
				// If there wasn't an error display the OK page or only the message.
				$page_title = get_option( 'PAGE_UPDATE_OK' );
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
			} else {
				// Else display the error page or the error message and the form.
				$template_id = ( ! empty( $form_data['template'] ) ) ? $form_data['template'] : '';

				$page_title = get_option( 'PAGE_UPDATE_ERROR' );
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
							self::$message_to_display = self::show_form( $message, $form_data, $template_id );
						}
					}
				} else {
					self::$message_to_display = self::show_form( $message, $form_data, $template_id );
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
			wp_enqueue_script(
				'recaptchav3',
				'https://www.google.com/recaptcha/api.js?render=' . $site_key,
				array(),
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
	 * @return string HTML code to replace the shortcode with.
	 */
	public static function shortcode( $attributes ) {
		// When there are no parameters, $attributes is an empty string, no array!
		if ( empty( $attributes ) ) {
			$attributes = array();
		}

		// Prepare the template file name.
		$template_id = null;
		if ( ! empty( $attributes['template'] ) ) {
			$template_id = $attributes['template'];
		}

		$request_method = filter_var( isset( $_SERVER['REQUEST_METHOD'] ) ? wp_unslash( $_SERVER['REQUEST_METHOD'] ) : 'GET' );

		$posted_cid     = filter_input( INPUT_POST, 'cid', FILTER_SANITIZE_NUMBER_INT );
		$posted_cs      = filter_input( INPUT_POST, 'cs' );
		$has_posted_cid = ! ( empty( $posted_cid ) && empty( $posted_cs ) );

		if ( 'GET' === $request_method || ( $has_posted_cid && ! self::is_currently_posted( $attributes ) ) ) {
			$cid = $has_posted_cid ? $posted_cid : filter_input( INPUT_GET, 'cid', FILTER_SANITIZE_NUMBER_INT );
			$cs  = $has_posted_cid ? $posted_cs : filter_input( INPUT_GET, 'cs' );

			if ( ! empty( $cid ) && ! empty( $cs ) ) {
				// If this wasn't POSTed, just display the form and fill data from maileon.
				$form_fields                       = self::get_maileon_fields( $template_id );
				$standard_fields                   = $form_fields['standard'];
				$custom_fields                     = $form_fields['custom'];
				$preference_fields_with_categories = $form_fields['preference'];

				$preference_fields = array();
				if ( ! empty( $preference_fields_with_categories ) ) {
					foreach ( $preference_fields_with_categories as $preference_field ) {
						$preference_information = explode( ':', $preference_field );
						if ( count( $preference_information ) < 2 ) {
							continue;
						}

						$category_name   = $preference_information[0];
						$preference_name = $preference_information[1];

						if ( ! in_array( $category_name, $preference_fields, true ) ) {
							$preference_fields[] = $category_name;
						}
					}
				}

				$_SESSION['cid'] = $cid;
				$_SESSION['cs']  = $cs;

				$form_data = array();

				// On some servers, we experienced problems with the session, thus, also allow injecting in the form.
				$form_data['cid'] = $cid;
				$form_data['cs']  = $cs;

				$response = self::get_maileon_contact( $cid, $cs, $standard_fields, $custom_fields, false, $preference_fields );
				if ( $response->isSuccess() ) {
					$contact = $response->getResult();

					$form_data['email'] = $contact->email;

					// This plugin assumes we work with DOI or DOI-PLUS
					// There are only two options: doiplus=true or false => No permission can not happen as cid and cs come from a mail => permission should be there
					// However, SOI, COI or OTHER might be possible but should not be used. If customer complains, this needs to be extended, here.
					// E.g. by setting default permission in settings.
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$form_data['doiplus'] = Permission::$DOI_PLUS->getCode() === $contact->permission->getCode();

					foreach ( $contact->standard_fields as $field_name => $value ) {
						$form_data[ 'standard_' . $field_name ] = $value;
					}

					foreach ( $contact->custom_fields as $field_name => $value ) {
						$form_data[ 'custom_' . $field_name ] = $value;
					}

					foreach ( $contact->preferences as $preference ) {
						$form_data[ 'preference_' . $preference->category . ':' . $preference->name ] = $preference->value;
					}

					$attributes['form_type'] = 'update';

					return self::show_form( null, $form_data, $template_id, $attributes );
				} else {
					// Not posted and no cid/cs given, whoe error.
					$message = __( 'Not matching parameters.', 'xq_maileon' );
					return self::show_message( $message );
				}
			} else {
				// Not posted and no cid/cs given, whoe error.
				$message = __( 'Missing parameters.', 'xq_maileon' );
				return self::show_message( $message );
			}
		} elseif ( ! empty( self::$message_to_display ) && self::is_currently_posted( $attributes ) ) {
			// if there is a message to display and this is the currently posted form display the message.
			return self::$message_to_display;
		} else {
			// Not a GET and no POST response.
			$message = __( 'Cannot display update form.', 'xq_maileon' );
			return self::show_message( $message );
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

		$email = isset( $form_data['email'] ) ? trim( filter_var( $form_data['email'], FILTER_SANITIZE_EMAIL ) ) : '';

		$captcha = null;
		if ( 'true' === get_option( 'captcha_enabled' ) ) {
			$captcha = isset( $form_data['maileon-captcha'] ) ? trim( htmlspecialchars( filter_var( $form_data['maileon-captcha'] ) ) ) : '';
		} elseif ( 'true' === get_option( 'maileon_recaptcha_enabled' ) ) {
			$captcha = isset( $form_data['recaptcha'] ) ? trim( htmlspecialchars( filter_var( $form_data['recaptcha'] ) ) ) : '';
		}
		$info['input'] = self::validate_values( $email, $captcha );

		// if the address passes the quality check too, register the contact.
		if ( false === $info['input']['error'] ) {
			$info['maileon'] = self::update_contact( $form_data );
		}

		return $info;
	}

	/**
	 * Attempts to register a contact through Maileon, and returns success information
	 *
	 * @param array $form_data The data including all form fields.
	 * @return array Information
	 */
	public static function update_contact( $form_data ) {
		$attributes               = array();
		$attributes['debug']      = ( ! empty( $form_data['debug'] ) ) ? htmlspecialchars( filter_var( $form_data['debug'] ) ) : 'false';
		$attributes['permission'] = ( ! empty( $form_data['permission'] ) ) ? filter_var( $form_data['permission'] ) : '1';

		$info = array(
			'error'   => false,
			'message' => get_option( 'success_update_message', __( 'You have succesfully updated your proile.', 'xq_maileon' ) ),
		);

		// Get the email from field 'email'.
		$email = $form_data['email'];

		// Update permission.
		$doiplus = $form_data['doiplus'];

		$standard_fields   = self::extract_maileon_fields( $form_data, 'standard' );
		$custom_fields     = self::extract_maileon_fields( $form_data, 'custom' );
		$preference_fields = self::extract_maileon_fields( $form_data, 'preference' );

		try {
			$cid = filter_var( isset( $_SESSION['cid'] ) ? $_SESSION['cid'] : null );
			$cs  = filter_var( isset( $_SESSION['cs'] ) ? $_SESSION['cs'] : null );

			// call the maileon api.
			$result = self::perform_update_contact(
				( ! empty( $cid ) ) ? $cid : $form_data['cid'],
				( ! empty( $cs ) ) ? $cs : $form_data['cs'],
				$email,
				'true' === $doiplus,
				$standard_fields,
				$custom_fields,
				$attributes,
				$preference_fields
			);
		} catch ( MaileonAPIException $ex ) {
			// we only display a general 'error' error message to users
			// in case of exceptions.
			$info['error']   = true;
			$info['message'] = get_option( 'error_update_message', __( 'An error occured with Maileon. Please try again later.', 'xq_maileon' ) );

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
				$info['error']   = true;
				$info['message'] = get_option( 'error_update_message', __( 'An error occured with Maileon. Please try again later.', 'xq_maileon' ) );

				ob_start();
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
				var_dump( $result );
				$content = ob_get_clean();

				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Call to maileon failed: ' . $content );

			}
			$info['response'] = $result;
		}

		return $info;
	}

	/**
	 * Attempts to update a contact using the UpdateContact Maileon call
	 *
	 * @param string  $cid Contact ID.
	 * @param string  $cs Contact checksum.
	 * @param string  $email Contact email.
	 * @param boolean $doiplus DOI plus process.
	 * @param array   $standard_fields Map of standard fields and values.
	 * @param array   $custom_fields Map of CF and values.
	 * @param array   $attributes Additional attributes for the form.
	 * @param array   $preference_fields Map of contact preferences and values.
	 * @return MaileonAPIResult
	 */
	private static function perform_update_contact( $cid, $cs, $email, $doiplus, $standard_fields, $custom_fields, $attributes, $preference_fields = null ) {
		$debug = 'true' === $attributes['debug'];

		$contacts_service = new ContactsService( self::get_maileon_config() );
		$contacts_service->setDebug( $debug );

		// set up the contact object: name and email address.
		$contact     = new Contact();
		$contact->id = $cid;

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$contact->permission = $doiplus ? Permission::$DOI_PLUS : Permission::$DOI;
		$contact->email      = $email;

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

				$category_name          = $preference_information[0];
				$preference_name        = $preference_information[1];
				$contact->preferences[] = new Preference( $preference_name, null, $category_name, $value );
			}
		}

		$result = $contacts_service->updateContact( $contact, $cs );

		if ( $debug ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( $result );
			die( 'debug parameter set' );
		}

		return $result;
	}

	/**
	 * Attempts to get a contact using ID + cs
	 *
	 * @param string  $cid Contact ID.
	 * @param string  $cs Contact checksum.
	 * @param array   $standard_fields Map of standard fields and values.
	 * @param array   $custom_fields Map of CF and values.
	 * @param boolean $debug Boolean that enables or disables debug output for rest service.
	 * @param array   $preference_categories Map of contact preferences and values.
	 * @return MaileonAPIResult
	 */
	private static function get_maileon_contact( $cid, $cs, $standard_fields, $custom_fields, $debug = false, $preference_categories = array() ) {
		$contacts_service = new ContactsService( self::get_maileon_config() );
		if ( $debug ) {
			$contacts_service->setDebug( true );
		}

		$result = $contacts_service->getContact( $cid, $cs, $standard_fields, $custom_fields, false, $preference_categories );

		if ( $debug ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( $result );
			die( 'debug parameter set' );
		}

		return $result;
	}
}
