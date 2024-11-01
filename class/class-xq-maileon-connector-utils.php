<?php
/**
 * This file contains utility functions for other classes
 *
 * @package xqueue-maileon
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

// Require the autoload file from composer.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * The utilities class
 */
class XQ_Maileon_Connector_Utils {
	/**
	 * Get a page by title
	 *
	 * @param string $title The title to query.
	 * @return WP_Post|null
	 */
	protected static function get_page_by_title( $title ) {
		$posts = get_posts(
			array(
				'title'     => $title,
				'post_type' => 'any',
			)
		);

		if ( count( $posts ) > 0 ) {
			return $posts[0];
		} else {
			return null;
		}
	}

	/**
	 * Gets the Maileon configuration
	 *
	 * @return array MaileonConfiguration
	 */
	public static function get_maileon_config() {
		$config = array(
			'API_KEY'  => get_option( 'API_KEY' ),
			'BASE_URI' => 'https://api.maileon.com/1.0',
		);
		if ( defined( 'WP_PROXY_HOST' ) ) {
			$config['PROXY_HOST'] = WP_PROXY_HOST;
		}
		if ( defined( 'WP_PROXY_PORT' ) ) {
			$config['WP_PROXY_PORT'] = WP_PROXY_PORT;
		}
		return $config;
	}

	/**
	 * Sanitizes the forms input
	 *
	 * @param array $post_data The POST data.
	 * @return array The form data sanitized
	 */
	public static function sanitize_form_data( $post_data ) {
		$form_data = array();

		foreach ( $post_data as $field => $value ) {

			if ( is_array( $value ) ) {
				$value_array_filter = array(
					$field => array( 'flags' => FILTER_REQUIRE_ARRAY ),
				);

				$form_data[ $field ] = implode( ';', filter_var_array( $post_data[ $field ], $value_array_filter ) );
			} else {
				// For SANITIZE_STRING it changed ' characters and thus, it was impossible to process JSON...
				$form_data[ $field ] = stripslashes( filter_var( $post_data[ $field ], FILTER_DEFAULT ) );

				// TODO: for boolean fields like doi or doiplus use FILTER_VALIDATE_BOOLEAN.
			}
		}

		return $form_data;
	}

	/**
	 * Checks if the form being displayed is currently being posted.
	 *
	 * @param Array $attributes The shortcode attributes.
	 * @return boolean
	 */
	protected static function is_currently_posted( $attributes ) {
		$form_name = filter_input( INPUT_POST, 'form_name' );

		if ( ! empty( $form_name ) && ! empty( $attributes ) ) {
			return ( ! empty( $attributes['name'] ) && $attributes['name'] === $form_name ) ||
				( ! empty( $attributes['template'] ) && $attributes['template'] === $form_name );
		}

		return false;
	}

	/**
	 * Does simple input validation:
	 *  * does the email address look like a valid email address?
	 *  * is the captcha empty?
	 *  * is the captcha correct?
	 *
	 * @param string $email The POSTed email addres.
	 * @param string $captcha The POSTed captcha.
	 * @return array Success information
	 */
	public static function validate_values( $email, $captcha = '' ) {
		$info = array(
			'error'   => false,
			'message' => '',
		);

		if ( ! is_email( $email ) ) {
			$info['error']   = true;
			$info['message'] = get_option( 'error_no_email', __( 'The specified email address is empty or not valid!', 'xq_maileon' ) );
		} else {
			if ( 'true' === get_option( 'captcha_enabled' ) ) {
				if ( empty( $captcha ) ) {
					$info['error']   = true;
					$info['message'] = get_option( 'error_no_captcha', __( 'You need to solve the equation!', 'xq_maileon' ) );
				} else {
					$info = self::validate_deprecated_captcha( $captcha );
				}
			}

			if ( 'true' === get_option( 'maileon_recaptcha_enabled' ) ) {
				// Validate Google reCaptcha result.
				$recaptcha_response = htmlspecialchars( filter_input( INPUT_POST, 'recaptcha' ) );
				$recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
				$recaptcha_secret   = get_option( 'maileon_recaptcha_secret_key' );

				$data = array(
					'secret'   => $recaptcha_secret,
					'response' => $captcha,
				);

				$options = array(
					'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
						'method'  => 'POST',
						'content' => http_build_query( $data ),
					),
				);

				$context = stream_context_create( $options );

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$recaptcha = file_get_contents( $recaptcha_url, false, $context );
				$recaptcha = json_decode( $recaptcha, true );

				if ( $recaptcha['success'] &&
					'subscribe' === $recaptcha['action'] &&
					$recaptcha['score'] >= intval( get_option( 'maileon_recaptcha_sensitivity' ) )
				) {
					$info['error'] = false;

				} else {
					$info['error'] = true;
					if ( get_option( 'maileon_debug' ) ) {
						$info['message'] = 'reCaptcha failed. Response: ' . wp_json_encode( $recaptcha );
					} else {
						$info['message'] = get_option( 'error_invalid_captcha', __( 'Invalid reCaptcha validation!', 'xq_maileon' ) );
					}
				}
			}
		}

		return $info;
	}

	/**
	 * Checks the captcha
	 * Uses $_SESSION to store data
	 *
	 * @deprecated
	 *
	 * @param string $answer The POSTed captcha answer.
	 * @return array information
	 */
	private static function validate_deprecated_captcha( $answer ) {
		$info = array(
			'error'   => false,
			'message' => '',
		);

		if ( empty( $answer ) ) {
			// Should be required in the form anyways.
			$info['error']   = true;
			$info['message'] = get_option( 'error_no_captcha', __( 'You need to solve the equation!', 'xq_maileon' ) );
			return $info;
		}

		$first_digit  = filter_var( isset( $_SESSION['maileon-captcha-first-digit'] ) ? $_SESSION['maileon-captcha-first-digit'] : null, FILTER_VALIDATE_INT );
		$second_digit = filter_var( isset( $_SESSION['maileon-captcha-second-digit'] ) ? $_SESSION['maileon-captcha-second-digit'] : null, FILTER_VALIDATE_INT );

		if ( empty( $first_digit ) || empty( $second_digit ) || intval( $answer ) !== $first_digit + $second_digit ) {
			$info['error']   = true;
			$info['message'] = get_option( 'error_invalid_captcha', __( 'Failed calculating captcha result!', 'xq_maileon' ) );
			return $info;
		}

		unset( $_SESSION['maileon-captcha-first-digit'] );
		unset( $_SESSION['maileon-captcha-second-digit'] );
		return $info;
	}

	/**
	 * Generates the captcha,
	 * uses $_SESSION to store data
	 *
	 * $_SESSION['maileon-captcha-first-digit']
	 * $_SESSION['maileon-captcha-second-digit']
	 */
	public static function generate_captcha() {
		$_SESSION['maileon-captcha-first-digit']  = wp_rand( 1, 15 );
		$_SESSION['maileon-captcha-second-digit'] = wp_rand( 1, 15 );
	}

	/**
	 * Extract the Maileon fields from the form data.
	 * Possible markers:
	 *  - standard
	 *  - custom
	 *  - preference
	 *
	 * @param array  $form_data The form data.
	 * @param string $marker The type of data to extract.
	 * @return array
	 */
	public static function extract_maileon_fields( $form_data, $marker ) {
		$result = array();
		if ( empty( $form_data ) ) {
			return $result;
		}

		foreach ( $form_data as $key => $value ) {
			if ( self::starts_with( $key, $marker . '_' ) ) {
				// As spaces (allowed e.g. in customfields) are translated to _, we encode them before
				// => Do not forget to decode them, here.
				$field_name            = self::decode_variable_name( substr( $key, strlen( $marker ) + 1 ) );
				$result[ $field_name ] = ( is_array( $value ) ) ? $value[0] : $value;
			}
		}
		return $result;
	}

	/**
	 * Convert shortcode attributes to maileon fields
	 * Possible markers:
	 *  - standard
	 *  - custom
	 *  - preference
	 *
	 * @param array  $attributes A JSON map of attributes.
	 * @param string $marker The type of data to extract.
	 * @return array
	 */
	public static function convert_shortcode_attributes_to_maileon_fields( $attributes, $marker ) {
		$result = array();

		if ( empty( $attributes ) ) {
			return $result;
		}
		if ( empty( $attributes[ $marker ] ) ) {
			return $result;
		}

		// As the parser cannot interpret ': replace them by ".
		$json = str_replace( "'", '"', $attributes[ $marker ] );

		$json_map = json_decode( $json );

		foreach ( $json_map as $key => $value ) {
			$result[ $marker . '_' . $key ] = $value;
		}

		return $result;
	}

	/**
	 * Generates the HTML form
	 *
	 * @param string $message The message to display.
	 * @param array  $form_data The form data that has been POSTed.
	 * @param string $template The template to use.
	 * @param array  $attributes Other form attributes to display.
	 * @return string
	 */
	public static function show_form( $message = '', $form_data = null, $template = null, $attributes = null ) {
		if ( null === $form_data ) {
			$form_data = array(
				'email'              => '',
				'standard_FIRSTNAME' => '',
				'standard_LASTNAME'  => '',
			);
		}

		// Iterate over each key-value pair and update the value with htmlspecialchars for output.
		foreach ( $form_data as $key => $value ) {
			$form_data[ $key ] = htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
		}

		$captcha = '';
		if ( 'true' === get_option( 'captcha_enabled' ) ) {
			self::generate_captcha();
			ob_start();
			require __DIR__ . '/../views/maileon-contact-form-captcha.php';
			$captcha = ob_get_clean();
		}

		$form = self::get_html_template( $template, $message, $form_data, $captcha, $attributes );
		if ( ! $form ) {
			/* translators: template name */
			return self::show_message( esc_html( sprintf( __( 'Failed to locate template "%s"!', 'xq_maileon' ), $template ) ) );
		}

		$nonce = wp_create_nonce( 'xq_maileon' );
		// When moving the code from the short code to the init method, we need to pass the config parameters as hidden fields.

		// Since 2.8.0, control attributes might come from form data. Thus, override/add here
		// All the attributes that control the behavior.
		$control_attributes = array(
			'debug',
			'doi',
			'doiplus',
			'doimailing',
			'permission',
			'sync_mode',
			'ok_page_id',
			'error_page_id',
			'template',
			'form_type',
			'standard',
			'custom',
			'src',
			'subscription_page',
		);

		if ( empty( $attributes ) ) {
			$attributes = array();
		}

		foreach ( $control_attributes as $control_attribute ) {
			if ( array_key_exists( $control_attribute, $form_data ) ) {
				$attributes[ $control_attribute ] = $form_data[ $control_attribute ]; // form data has been sanitized, so just use it.
			}
		}

		if ( ! empty( $attributes['name'] ) ) {
			$form_name = $attributes['name'];
		} else {
			$form_name = $template;
		}

		$new_hidden_fields  = '';
		$new_hidden_fields .= '<input type="hidden" id="_wpnonce" name="_wpnonce" value="' . esc_attr( $nonce ) . '"/>';
		$new_hidden_fields .= '<input type="hidden" id="maileon_form_name" name="form_name" value="' . esc_attr( $form_name ) . '"/>';

		if ( 'true' === get_option( 'maileon_recaptcha_enabled' ) ) {
			$new_hidden_fields .= '<input type="hidden" id="recaptcha" name="recaptcha" value=""/>';
		}

		// If there is a cid and cs, add them as hidden fields.
		if ( ! empty( $form_data['cid'] ) ) {
			$new_hidden_fields .= '<input type="hidden" id="cid" name="cid" value="' . esc_attr( $form_data['cid'] ) . '"/>';
		}
		if ( ! empty( $form_data['cs'] ) ) {
			$new_hidden_fields .= '<input type="hidden" id="cs" name="cs" value="' . esc_attr( $form_data['cs'] ) . '"/>';
		}

		// Process the attributes.
		if ( ! empty( $attributes ) ) {
			// All the attributes that control the behavior, exclude standard and custom, as they are handled differently below.
			$control_attributes = array(
				'debug',
				'doi',
				'doiplus',
				'doimailing',
				'permission',
				'sync_mode',
				'ok_page_id',
				'error_page_id',
				'template',
				'form_type',
				'src',
				'subscription_page',
			);

			foreach ( $control_attributes as $control_attribute ) {
				if ( array_key_exists( $control_attribute, $attributes ) ) {
					$new_hidden_fields .= sprintf(
						'<input type="hidden" id="%s" name="%s" value="%s"/>',
						esc_attr( 'maileon_' . $control_attribute ),
						esc_attr( $control_attribute ),
						esc_attr( $attributes[ $control_attribute ] )
					);
				}
			}

			// Get the fixed standard and custom fields from the shorttag.
			foreach ( self::convert_shortcode_attributes_to_maileon_fields( $attributes, 'standard' ) as $key => $value ) {
				$new_hidden_fields .= sprintf(
					'<input type="hidden" id="%s" name="%s" value="%s"/>',
					esc_attr( 'maileon_' . $key ),
					esc_attr( $key ),
					esc_attr( $value )
				);
			}
			foreach ( self::convert_shortcode_attributes_to_maileon_fields( $attributes, 'custom' ) as $key => $value ) {
				$new_hidden_fields .= sprintf(
					'<input type="hidden" id="%s" name="%s" value="%s"/>',
					esc_attr( 'maileon_' . $key ),
					esc_attr( $key ),
					esc_attr( $value )
				);
			}
			foreach ( self::convert_shortcode_attributes_to_maileon_fields( $attributes, 'preference' ) as $key => $value ) {
				$new_hidden_fields .= sprintf(
					'<input type="hidden" id="%s" name="%s" value="%s"/>',
					esc_attr( 'maileon_' . $key ),
					esc_attr( $key ),
					esc_attr( $value )
				);
			}

			// Also add default standard/custom field descriptor for passthrough in error case.
			if ( ! empty( $attributes['standard'] ) ) {
				$new_hidden_fields .= '<input type="hidden" id="maileon_standard" name="standard" value="' . esc_attr( $attributes['standard'] ) . '"/>';
			}
			if ( ! empty( $attributes['custom'] ) ) {
				$new_hidden_fields .= '<input type="hidden" id="maileon_custom" name="custom" value="' . esc_attr( $attributes['custom'] ) . '"/>';
			}
			if ( ! empty( $attributes['preference'] ) ) {
				$new_hidden_fields .= '<input type="hidden" id="maileon_preference" name="preference" value="' . esc_attr( $attributes['preference'] ) . '"/>';
			}

			// Returns elements with first element being the match and second element being the position.
			preg_match( '/<form (.*?)>/', $form, $output, PREG_OFFSET_CAPTURE );
			foreach ( $output as $entry ) {
				if ( ! empty( $entry[0] ) ) {
					$input_position = $entry[1] + strlen( $entry[0] ) + 1;

					$form = substr_replace( $form, $new_hidden_fields, $input_position, 0 );
					break;
				}
			}
		}

		return $form;
	}

	/**
	 * Get the used Maileon custom and standard fields used in the form
	 *
	 * @param string $template_id the template id/path.
	 * @return array the resulting arrays with keys 'standard', 'custom' and 'preference
	 */
	public static function get_maileon_fields( $template_id ) {
		$template_content = self::show_form( '', null, $template_id );

		// Get form fields in order to query information from Maileon.
		$standard_fields = array();
		preg_match_all( '~standard_[^"\']+~', $template_content, $standard_field_tags );
		if ( ! empty( $standard_field_tags ) ) {
			$standard_field_tags = array_unique( $standard_field_tags[0] );
			foreach ( $standard_field_tags as $standard_field_tag ) {
				$standard_fields[] = substr( trim( $standard_field_tag ), 9 );
			}
		}

		$custom_fields = array();
		preg_match_all( '~custom_[^"\']+~', $template_content, $custom_field_tags );
		if ( ! empty( $custom_field_tags ) ) {
			$custom_field_tags = array_unique( $custom_field_tags[0] );
			foreach ( $custom_field_tags as $custom_field_tag ) {
				$custom_fields[] = substr( trim( $custom_field_tag ), 7 );
			}
		}

		$preference_fields = array();
		preg_match_all( '~\"preference_[^"\']+~', $template_content, $preference_field_tags );
		if ( ! empty( $preference_field_tags ) ) {
			$preference_field_tags = array_unique( $preference_field_tags[0] );
			foreach ( $preference_field_tags as $preference_tag ) {
				$preference_fields[] = substr( trim( $preference_tag ), 12 );
			}
		}

		return array(
			'standard'   => $standard_fields,
			'custom'     => $custom_fields,
			'preference' => $preference_fields,
		);
	}

	/**
	 * Retrieves the name of the template file that exists.
	 *
	 * @param string $template_name Template file to search for.
	 * @return string
	 */
	public static function locate_template( $template_name ) {
		$located    = '';
		$upload_dir = wp_get_upload_dir()['basedir'] . '/xqueue-maileon/views/';

		if ( file_exists( $upload_dir . $template_name ) ) {
			$located = $upload_dir . $template_name;
		} elseif ( file_exists( __DIR__ . '/../views/' . $template_name ) ) {
			$located = __DIR__ . '/../views/' . $template_name;
		}

		return $located;
	}

	/**
	 * Get the html template
	 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	 *
	 * @param string $template_id The template id/path.
	 * @param string $message The message to show.
	 * @param array  $form_data The form data for the template.
	 * @param string $captcha The generated captcha.
	 * @param array  $attributes The attributes for the form template.
	 * @return string The HTML content of the template
	 */
	public static function get_html_template( $template_id, $message = '', $form_data = array(), $captcha = '', $attributes = array() ) {
		if ( empty( $template_id ) ) {
			$template_id = 'maileon-contact-form.php';
		}

		// Try loading it from Theme or Child Theme.
		$template_path = locate_template(
			array(
				'xqueue-maileon' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $template_id,
			)
		);

		if ( empty( $template_path ) ) {
			$template_path = self::locate_template( $template_id );
		}

		if ( empty( $template_path ) ) {
			return false;
		}

		// for backwards compatibility.
		$msg = $message;

		ob_start();
		require $template_path;
		return ob_get_clean();
	}

	/**
	 * Generates a simple HTML message
	 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
	 *
	 * @param string $msg The message to show.
	 * @param string $id  The id of the message box.
	 * @return string The generated HTML string
	 */
	public static function show_message( $msg, $id = '' ) {
		ob_start();
		require __DIR__ . '/../views/maileon-contact-form-message.php';
		return ob_get_clean();
	}

	/**
	 * Check if haystack starts with needle
	 *
	 * @param string $haystack The string to check.
	 * @param string $needle The thing to check for.
	 * @return boolean
	 */
	public static function starts_with( $haystack, $needle ) {
		// search backwards starting from haystack length characters from the end.
		return '' === $needle || strrpos( $haystack, $needle, - strlen( $haystack ) ) !== false;
	}

	/**
	 * Since spaces in variable names are converted to underscodes, we need to encode the name for the form
	 * See https://www.php.net/manual/en/language.variables.external.php
	 * Note: Dots and spaces in variable names are converted to underscores. For example <input name="a.b" /> becomes $_REQUEST["a_b"].
	 *
	 * @param string $name The variable name to encode.
	 * @return string
	 */
	public static function encode_variable_name( $name ) {
		return str_replace( ' ', '_space_', $name );
	}

	/**
	 * Since spaces in variable names are converted to underscodes, we encode the name for the form and need to decode before submitting
	 * See https://www.php.net/manual/en/language.variables.external.php
	 * Note: Dots and spaces in variable names are converted to underscores. For example <input name="a.b" /> becomes $_REQUEST["a_b"].
	 *
	 * @param string $name The variable name to decpde.
	 * @return string
	 */
	public static function decode_variable_name( $name ) {
		return str_replace( '_space_', ' ', $name );
	}
}
