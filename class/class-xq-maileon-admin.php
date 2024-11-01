<?php
/**
 * This file contains the Admin functionality
 *
 * @package xqueue-maileon
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

/**
 * The admin class
 */
class XQ_Maileon_Admin {
	/**
	 * Is the admin menu initialized
	 *
	 * @var boolean
	 */
	private $initialized = false;

	/**
	 * Add the admin menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		// 1. Text for title of page
		// 2. Text displayed in menu
		// 3. Permission required to use this page
		// 4. ID / URL of this settings page
		// 5. Name of the callback method for rendering (array as class name is specified)
		add_options_page( 'Maileon Settings', 'Maileon', 'manage_options', 'maileon-options', array( $this, 'render_options_menu' ) );
	}

	/**
	 * Enqueues the admin page styles and scripts
	 *
	 * @return void
	 */
	public function enqueue_admin_page_styles_and_scripts() {
		// Needed for the Settings Page.
		$wp_scripts = wp_scripts();
		wp_enqueue_style(
			'jquery-ui-theme-smoothness', // select ui theme: base...
			plugins_url( '/../css/jquery-ui.css', __FILE__ ),
			array(),
			XQ_MAILEON_PLUGIN_VERSION
		);
		wp_enqueue_script( 'jquery-ui-tabs' );
	}

	/**
	 * Initializes the plugin's settings
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_page_styles_and_scripts' ) );

		wp4xq_check_if_curl_is_installed();

		/*
		 * API settings
		 */

		add_settings_section(
			'maileon-api-settings-section',
			__( 'API settings', 'xq_maileon' ),
			array( $this, 'render_single_settings_section' ),
			'maileon-api-settings-section'
		);

		$this->register_boolean_option( __( 'Print Debug Data', 'xq_maileon' ), 'maileon_debug', false, 'maileon-api-settings-section' );

		$this->register_text_option(
			__( 'Maileon API key', 'xq_maileon' ),
			'API_KEY',
			'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
			'maileon-api-settings-section'
		);

		$this->register_text_option(
			__( 'Link to data privacy description', 'xq_maileon' ),
			'PRIVACY_PAGE',
			'',
			'maileon-api-settings-section'
		);

		$this->register_text_option(
			__( 'Organization', 'xq_maileon' ),
			'ORGANIZATION',
			'Name of the organization to display for the data privacy declaration.',
			'maileon-api-settings-section'
		);

		$this->register_boolean_option(
			__( 'Enable footer subscription', 'xq_maileon' ),
			'xq_maileon_footer_enabled',
			false,
			'maileon-api-settings-section'
		);

		$this->register_template_overview_option( __( 'Templates', 'xq_maileon' ), 'maileon_templates', 'maileon-api-settings-section' );

		/*
		 * Pages to set up
		 */

		add_settings_section(
			'maileon-acknowledgement_pages-section',
			__( 'Pages', 'xq_maileon' ),
			array( $this, 'render_single_settings_section' ),
			'maileon-acknowledgement_pages-section'
		);
		$this->register_text_option(
			__( 'OK: Title of page to display if contact was subscribed successfully (if empty, only the message below will be shown instead of the page)', 'xq_maileon' ),
			'PAGE_OK',
			'',
			'maileon-acknowledgement_pages-section'
		);
		$this->register_text_option(
			__( 'ERROR: Title of page to display if contact was *NOT* subscribed successfully (if empty, only the message below will be shown instead of the page)', 'xq_maileon' ),
			'PAGE_ERROR',
			'',
			'maileon-acknowledgement_pages-section'
		);

		$this->register_text_option(
			__( 'UPDATE_OK: Title of page to display if contact update was successful (if empty, only the message below will be shown instead of the page)', 'xq_maileon' ),
			'PAGE_UPDATE_OK',
			'',
			'maileon-acknowledgement_pages-section'
		);
		$this->register_text_option(
			__( 'UPDATE_ERROR: Title of page to display if contact update *NOT* successful (if empty, only the message below will be shown instead of the page)', 'xq_maileon' ),
			'PAGE_UPDATE_ERROR',
			'',
			'maileon-acknowledgement_pages-section'
		);

		/*
		 * Selection for different display texts
		 */

		add_settings_section(
			'maileon-localization-messages-settings-section',
			__( 'Messages', 'xq_maileon' ),
			array( $this, 'render_single_settings_section' ),
			'maileon-localization-messages-settings-section'
		);

		$this->register_text_option(
			__( 'Text for Succesfull Subscription', 'xq_maileon' ),
			'success_message',
			__( 'You have succesfully subscribed to our newsletter, please check your inbox for the confirmation mail.', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Empty Email', 'xq_maileon' ),
			'error_no_email',
			__( 'The specified email address is empty or not valid!', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Syntax Error', 'xq_maileon' ),
			'error_invalid_syntax',
			__( 'The specified email address contains errors:', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Similar Domains', 'xq_maileon' ),
			'error_invalid_domain',
			__( 'The specified domain does not exist. Similar domains:', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Invalid E-Mail-Address', 'xq_maileon' ),
			'error_invalid_address',
			__( 'The specified email address is invalid!', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Non-Existant Address', 'xq_maileon' ),
			'error_nonexistant_address',
			__( 'The specified email address does not exist!', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Empty Captcha', 'xq_maileon' ),
			'error_no_captcha',
			__( 'Please provide a solution for the given equation!', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Invalid Captcha', 'xq_maileon' ),
			'error_invalid_captcha',
			__( 'Failed to validate captcha challenge.', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Maileon Error', 'xq_maileon' ),
			'error_maileon',
			__( 'An error occured with Maileon. Please try again later.', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);

		add_settings_section(
			'maileon-localization-labels-settings-section',
			__( 'Labels', 'xq_maileon' ),
			array( $this, 'render_single_settings_section' ),
			'maileon-localization-labels-settings-section'
		);
		$this->register_text_option(
			__( 'Firstname Field', 'xq_maileon' ),
			'label_firstname',
			__( 'First name', 'xq_maileon' ),
			'maileon-localization-labels-settings-section'
		);
		$this->register_text_option(
			__( 'Lastname Field', 'xq_maileon' ),
			'label_lastname',
			__( 'Last name', 'xq_maileon' ),
			'maileon-localization-labels-settings-section'
		);
		$this->register_text_option(
			__( 'E-Mail Adress Field', 'xq_maileon' ),
			'label_email',
			__( 'E-mail address', 'xq_maileon' ),
			'maileon-localization-labels-settings-section'
		);
		$this->register_text_option(
			__( 'Subscribe Button', 'xq_maileon' ),
			'label_submit',
			__( 'Subscribe', 'xq_maileon' ),
			'maileon-localization-labels-settings-section'
		);

		// Updates.
		$this->register_text_option(
			__( 'Text for Succesfull Update', 'xq_maileon' ),
			'success_update_message',
			__( 'You have succesfully updated your contact.', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);
		$this->register_text_option(
			__( 'Text for Unsuccesfull Update', 'xq_maileon' ),
			'error_update_message',
			__( 'There was an error updating your contact.', 'xq_maileon' ),
			'maileon-localization-messages-settings-section'
		);

		/*
		 * Google reCaptcha settings.
		 */

		add_settings_section(
			'maileon-repcaptcha-settings-section',
			__( 'Captcha', 'xq_maileon' ),
			array( $this, 'render_single_settings_section' ),
			'maileon-repcaptcha-settings-section'
		);

		$this->register_boolean_option( __( 'Use (deprecated) Captcha Code', 'xq_maileon' ), 'captcha_enabled', true, 'maileon-repcaptcha-settings-section' );
		$this->register_boolean_option( __( 'Use Google reCaptcha', 'xq_maileon' ), 'maileon_recaptcha_enabled', false, 'maileon-repcaptcha-settings-section' );

		$this->register_text_option(
			__( 'Site Key', 'xq_maileon' ),
			'maileon_recaptcha_site_key',
			'',
			'maileon-repcaptcha-settings-section'
		);

		$this->register_text_option(
			__( 'Secret Key', 'xq_maileon' ),
			'maileon_recaptcha_secret_key',
			'',
			'maileon-repcaptcha-settings-section'
		);

		// Create dropdown for sensitivity.
		$settings = array();
		for ( $i = 0.1; $i <= 0.9; $i += 0.1 ) {
			$display_text = (string) $i;

			if ( '0.1' === $display_text ) {
				$display_text .= ' (accept nearly everything)';
			}
			if ( '0.5' === $display_text ) {
				$display_text .= ' (suggested value)';
			}
			if ( '0.9' === $display_text ) {
				$display_text .= ' (accept nearly nothing)';
			}

			$settings[ (string) $i ] = $display_text;
		}
		$this->register_text_dropdown_option(
			__( 'reCaptcha Sensitivity', 'xq_maileon' ),
			'maileon_recaptcha_sensitivity',
			'0.5',
			$settings,
			'maileon-repcaptcha-settings-section'
		);

		/*
		 * Add options for ADC
		 */

		add_settings_section(
			'xqueue-adc-section',
			__( 'ADC settings (optional)', 'xq_maileon' ),
			array( $this, 'render_single_settings_section' ),
			'xqueue-adc-section'
		);

		$this->register_boolean_option( __( 'Use ADC', 'xq_maileon' ), 'adc_enabled', false, 'xqueue-adc-section' );
		$this->register_text_option(
			__( 'ADC API User', 'xq_maileon' ),
			'adc_api_user',
			'',
			'xqueue-adc-section'
		);
		$this->register_text_option(
			__( 'ADC API Key', 'xq_maileon' ),
			'adc_api_key',
			'',
			'xqueue-adc-section'
		);
		$this->register_text_option(
			__( 'ID of E-Mail Field', 'xq_maileon' ),
			'adc_email_field',
			'maileon_contact_form_email',
			'xqueue-adc-section'
		);
		$this->register_text_option(
			__( 'ADC Input Check Delay (in seconds)', 'xq_maileon' ),
			'adc_input_delay',
			__( '5', 'xq_maileon' ),
			'xqueue-adc-section'
		);
		$this->register_boolean_option( __( 'Allow ADC calls only from same server', 'xq_maileon' ), 'adc_only_from_localhost', true, 'xqueue-adc-section' );

		$this->initialized = true;
	}

	/**
	 * Renders a single settings section like api settings, adc settings, ... for use in tabbed view
	 *
	 * @param array $args The arguments for the section.
	 * @return void
	 */
	public function render_single_settings_section( $args ) {
		echo '<table class="form-table" role="presentation"><tbody>';
		do_settings_fields( 'maileon-options', $args['id'] );
		echo '</tbody></table>';
	}

	/**
	 * This method is used to validate (and sanitize) option inputs by escaping potentially dangerous inputs
	 *
	 * @param mixed $input The input to validate.
	 * @return mixed
	 */
	public function default_maileon_settings_validation( $input ) {

		if ( is_array( $input ) ) {
			// Create our array for storing the validated options.
			$output = array();

			// Loop through each of the incoming options.
			foreach ( $input as $key => $value ) {
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[ $key ] ) ) {
					// Strip all HTML and PHP tags and properly handle quoted strings.
					$output[ $key ] = htmlspecialchars( $input[ $key ] );
				}
			}
		} else {
			$output = htmlspecialchars( $input );
		}

		// Return the array processing any additional functions filtered by this action.
		return apply_filters( 'default_maileon_settings_validation', $output, $input );
	}


	/**
	 * Helper function for registering and displaying a text option
	 *
	 * @param string $display_name The name to display for this option.
	 * @param string $name The name in the database.
	 * @param string $default_value The default value.
	 * @param string $section The section this option should appear in.
	 * @param string $group The group this option should appear in.
	 */
	private function register_text_option( $display_name, $name, $default_value, $section = 'maileon-main-settings-section', $group = 'maileon-settings-group' ) {
		add_settings_field(
			$name,
			$display_name,
			array( $this, 'render_text_input' ),
			'maileon-options',
			$section,
			array( 'name' => $name )
		);

		register_setting(
			$group,
			$name,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'default_maileon_settings_validation' ),
			)
		);

		// Why is sanitize_callbacl never called when get_option is not called? And why is it only getting the value passed on add_option instead the value entered int he form?
		get_option( $name ) ? add_option( $name, get_option( $name ) ) : add_option( $name, $default_value );
	}

	/**
	 * Helper function for registering and displaying a boolean option
	 *
	 * @param string  $display_name The name to display for this option.
	 * @param string  $name The name in the database.
	 * @param boolean $default_value The default value.
	 * @param string  $section The section this option should appear in.
	 */
	private function register_boolean_option( $display_name, $name, $default_value, $section = 'maileon-main-settings-section' ) {
		add_settings_field(
			$name,
			$display_name,
			array( $this, 'render_checkbox_input' ), // Callback.
			'maileon-options',
			$section,
			array( 'name' => $name )
		);

		register_setting(
			'maileon-settings-group',
			$name,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'default_maileon_settings_validation' ),
			)
		);

		// Why is sanitize_callbacl never called when get_option is not called? And why is it only getting the value passed on add_option instead the value entered int he form?
		get_option( $name ) ? add_option( $name, get_option( $name ) ) : add_option( $name, $default_value );
	}

	/**
	 * Helper function for registering and displaying text
	 *
	 * @param string $display_name The name to display for this option.
	 * @param string $text The text of this option.
	 * @param string $section The section this option should appear in.
	 */
	private function register_text( $display_name, $text, $section = 'maileon-main-settings-section' ) {
		$name = 'adc_information';
		add_settings_field(
			$name,
			$display_name,
			function ( $args ) {
				echo esc_html( $args['text'] );
			},
			'maileon-options',
			$section,
			array(
				'name' => $name,
				'text' => $text,
			)
		);

		register_setting(
			'maileon-settings-group',
			$name,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'default_maileon_settings_validation' ),
			)
		);

		// Why is sanitize_callbacl never called when get_option is not called? And why is it only getting the value passed on add_option instead the value entered int he form?
		get_option( $name ) ? add_option( $name, get_option( $name ) ) : add_option( $name );
	}

	/**
	 * Helper function for registering and displaying a text based dropdown option
	 *
	 * @param string $display_name The name to display for this option.
	 * @param string $name The name in the database.
	 * @param string $default_value The default value.
	 * @param array  $values The key-value pairs that define the dropdowns options.
	 * @param string $section The section this option should appear in.
	 */
	private function register_text_dropdown_option( $display_name, $name, $default_value, $values, $section = 'maileon-main-settings-section' ) {
		add_settings_field(
			$name,
			$display_name,
			array( $this, 'render_select_input' ),
			'maileon-options',
			$section,
			array(
				'name'   => $name,
				'values' => $values,
			)
		);

		register_setting(
			'maileon-settings-group',
			$name,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'default_maileon_settings_validation' ),
			)
		);

		// Why is sanitize_callbacl never called when get_option is not called? And why is it only getting the value passed on add_option instead the value entered int he form?
		get_option( $name ) ? add_option( $name, get_option( $name ) ) : add_option( $name, $default_value );
	}

	/**
	 * Helper function for registering and displaying the special template overview
	 *
	 * @param string $display_name The name to display for this option.
	 * @param string $name The name in the database.
	 * @param string $section The section this option should appear in.
	 * @param string $group The group this option should appear in.
	 */
	private function register_template_overview_option( $display_name, $name, $section = 'maileon-main-settings-section', $group = 'maileon-settings-group' ) {
		add_settings_field(
			$name,
			$display_name,
			array( $this, 'render_template_overview_option' ),
			'maileon-options',
			$section
		);

		register_setting(
			$group,
			$name,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'default_maileon_settings_validation' ),
			)
		);

		// Why is sanitize_callbacl never called when get_option is not called? And why is it only getting the value passed on add_option instead the value entered int he form?
		get_option( $name ) ? add_option( $name, get_option( $name ) ) : add_option( $name );
	}

	/**
	 * Echoes a HTML text input
	 */
	public function render_template_overview_option() {
		echo 'Default Templates (will be overwritten with each update!): <b>' . esc_html( realpath( __DIR__ . '/../views/' ) ) . '</b>';
		echo '<ul>';

		$handle = opendir( __DIR__ . '/../views/' );
		if ( $handle ) {
			$entry = readdir( $handle );
			while ( false !== $entry ) {
				if ( '.' !== $entry && '..' !== $entry ) {
					$this->print_template_entry( $entry );
				}

				$entry = readdir( $handle );
			}
			closedir( $handle );
		}
		echo '</ul>';

		$upload_directory = realpath( wp_get_upload_dir()['basedir'] . '/xqueue-maileon/views' );
		if ( ! empty( $upload_directory ) ) {
			echo '<br />Customer Templates: <b>' . esc_html( realpath( wp_get_upload_dir()['basedir'] . '/xqueue-maileon/views' ) ) . '</b>';
			echo '<ul>';

			$handle = opendir( wp_get_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . 'xqueue-maileon' . DIRECTORY_SEPARATOR . 'views' );
			if ( $handle ) {
				$entry = readdir( $handle );
				while ( false !== $entry ) {
					if ( '.' !== $entry && '..' !== $entry ) {
						$this->print_template_entry( $entry );
					}
					$entry = readdir( $handle );
				}
				closedir( $handle );
			}
			echo '</ul>';
		} else {
			echo '<br />Customer Templates: -';
			echo '<br /><br /><font style=\"color:grey\">Folder not existing, to overwrite the templates from WordPress uploads folder, please make sure the folder exists: <b>';
			echo '<br />' . esc_html( realpath( wp_get_upload_dir()['basedir'] ) ) . '/xqueue-maileon/views</b></font>';
			echo '</br />';
		}

		$theme_directory = realpath( get_template_directory() . '/xqueue-maileon/views' );
		if ( ! empty( $theme_directory ) ) {
			echo '<br />Theme Templates: <b>' . esc_html( realpath( get_template_directory() . '/xqueue-maileon/views' ) ) . '</b>';
			echo '<ul>';

			$handle = opendir( get_template_directory() . DIRECTORY_SEPARATOR . 'xqueue-maileon' . DIRECTORY_SEPARATOR . 'views' );
			if ( $handle ) {
				$entry = readdir( $handle );
				while ( false !== $entry ) {
					if ( '.' !== $entry && '..' !== $entry ) {
						$this->print_template_entry( $entry );
					}
					$entry = readdir( $handle );
				}
				closedir( $handle );
			}
			echo '</ul>';
		} else {
			echo '<br />Theme Templates: -';
			echo '<br /><br /><font style=\"color:grey\">Folder not existing, to overwrite the templates from the theme please make sure the folder exists: <b>';
			echo '<br />' . esc_html( realpath( get_template_directory() ) ) . '/xqueue-maileon/views</b></font>';
			echo '</br />';
		}

		if ( get_stylesheet_directory() !== get_template_directory() ) {
			$childtheme_directory = realpath( get_stylesheet_directory() . '/xqueue-maileon/views' );
			if ( ! empty( $childtheme_directory ) ) {

				echo '<br />Child Theme Templates: <b>' . esc_html( realpath( get_stylesheet_directory() ) . '/xqueue-maileon/views' ) . '</b>';
				echo '<ul>';

				$handle = opendir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'xqueue-maileon' . DIRECTORY_SEPARATOR . 'views' );
				if ( $handle ) {
					$entry = readdir( $handle );
					while ( false !== $entry ) {
						if ( '.' !== $entry && '..' !== $entry ) {
							$this->print_template_entry( $entry );
						}
						$entry = readdir( $handle );
					}
					closedir( $handle );
				}
				echo '</ul>';
			} else {
				echo '<br />Child Theme Templates: -';
				echo '<br/><br /><font style=\"color:grey\">Folder not existing, to overwrite the templates from the child theme please make sure the folder exists: <b>';
				echo '<br />' . esc_html( realpath( get_stylesheet_directory() ) ) . '/xqueue-maileon/views</b></font>';
				echo '</br />';
			}
		}
	}

	/**
	 * Small helper that echoes an entry from the template lists
	 *
	 * @param string $entry The entry to be echoed.
	 */
	private function print_template_entry( $entry ) {
		echo '<li>&nbsp;&nbsp;&nbsp;&bull;&nbsp;' . esc_html( $entry ) . '<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font style="color:grey">Shortcode:&nbsp;[maileon-contact template="' . esc_html( $entry ) . '" sync_mode=1 doi="true" doiplus="true" doimailing=""]</font></li>';
	}

	/**
	 * Echoes a HTML text input
	 *
	 * @param array $args An array that should contain a 'name'.
	 */
	public function render_text_input( $args ) {
		$name = $args['name'];

		echo '<input type="text" class="regular-text ltr" name="' . esc_attr( $name ) . '" value="' . esc_attr( get_option( $name ) ) . '">';
	}

	/**
	 * Echoes a HTML checkbox input
	 *
	 * @param array $args An array that should contain a 'name'.
	 */
	public function render_checkbox_input( $args ) {
		$name = $args['name'];

		echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="true" ' . ( get_option( $name ) ? 'checked' : '' ) . '>';
	}

	/**
	 * Echoes a HTML select tag with options
	 *
	 * @param array $args An array that should contain a 'name' and a 'values' array defining the possible options.
	 */
	public function render_select_input( $args ) {
		$name          = $args['name'];
		$current_value = get_option( $name );
		$dropdown      = '';

		foreach ( $args['values'] as $key => $value ) {
			if ( $key === $current_value ) {
				$dropdown .= '<option value="' . esc_attr( $key ) . '" selected>' . esc_html( $value ) . '</option>';
			} else {
				$dropdown .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
			}
		}

		// The value of $dropdown contains HTML and is already escaped above.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<select name="' . esc_attr( $name ) . '">' . $dropdown . '</select>';
	}

	/**
	 * Echoes the options page
	 */
	public function render_options_menu() {
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap">

			<div id="icon-themes" class="icon32"></div>
			<style>
			.maileon_settings_description {
				margin: 10px;
				padding: 10px;
				background-color: #EDEDED;
				border-radius: 5px;
			}
			</style>
			<h2><?php esc_html_e( 'Maileon Settings', 'xq_maileon' ); ?></h2>

			<form method="post" action="options.php">
				<div id="maileon_plugin_config_tabs">
					<ul>
						<li><a href="#section_api"><?php esc_html_e( 'API Settings', 'xq_maileon' ); ?></a></li>
						<li><a href="#section_labels"><?php esc_html_e( 'Form Labels', 'xq_maileon' ); ?></a></li>
						<li><a href="#section_pages"><?php esc_html_e( 'Pages', 'xq_maileon' ); ?></a></li>
						<li><a href="#section_messages"><?php esc_html_e( 'Messages', 'xq_maileon' ); ?></a></li>
						<li><a href="#section_captcha"><?php esc_html_e( 'Captcha', 'xq_maileon' ); ?></a></li>
						<li><a href="#section_adc"><?php esc_html_e( 'ADC settings (optional)', 'xq_maileon' ); ?></a></li>
					</ul>


					<?php
						// Generate nonce and all required fields for being able to update all fields belonging to 'maileon-settings-group'.
						settings_fields( 'maileon-settings-group' );
					?>

					<div id="section_api">
						<div class="maileon_settings_description">
							<?php esc_html_e( 'Copy your API key from Maileon -> Settings -> API-Keys.', 'xq_maileon' ); ?>
						</div>
						<?php do_settings_sections( 'maileon-api-settings-section' ); ?>
					</div>
					<div id="section_labels">
						<div class="maileon_settings_description">
							<?php esc_html_e( 'This section is used to set up labels for basic registration forms.', 'xq_maileon' ); ?>
						</div>
						<?php do_settings_sections( 'maileon-localization-labels-settings-section' ); ?>
					</div>
					<div id="section_pages">
						<div class="maileon_settings_description">
							<?php esc_html_e( 'This section is used to forward contacts to predesigned WordPress pages. If a page is configured, it will be used over its potentially configured status message on the tab "messages".', 'xq_maileon' ); ?>
						</div>
						<?php do_settings_sections( 'maileon-acknowledgement_pages-section' ); ?>
					</div>
					<div id="section_messages">
						<div class="maileon_settings_description">
							<?php
							printf(
								/* translators: the parameter is a line break */
								esc_html__( 'This section is used to seit up messages for different occasions. The messages will be displayed to contacts e.g. when submitting a form (either it worked or not...).%sThis behavior is overwritten, if pages for forwarding are configured.', 'xq_maileon' ),
								'<br>'
							);
							?>
						</div>
						<?php do_settings_sections( 'maileon-localization-messages-settings-section' ); ?>
					</div>
					<div id="section_captcha">
						<div class="maileon_settings_description">
							<?php
							printf(
								/* translators: %1$s is a line break, %2$s is a link open tag, %3$s is a link close tag */
								esc_html__( 'This section sets up the captcha functionality. Earlier it was solved using some simple math captchas. Now, there is the possibility of using goole reCaptcha.%1$sFor using reCaptcha, register your domain with Google reCaptcha, generate a site and secret key and add them here. For information how to use reCaptcha, click %2$shere%3$s.', 'xq_maileon' ),
								'<br>',
								'<a href="https://www.google.com/recaptcha/admin/create#list" target="_blank">',
								'</a>'
							);
							?>
						</div>
						<?php do_settings_sections( 'maileon-repcaptcha-settings-section' ); ?>
					</div>
					<div id="section_adc">
						<div class="maileon_settings_description">
							<?php
							printf(
								/* translators: the parameter is a link to the official site */
								esc_html__( 'Note, if you decide to enable AddressCheck for your forms and do not fill ADC user and key, ADC will be automatically disabled. For more information about AddressCheck, please visit %s.', 'xq_maileon' ),
								'<a href="https://www.addresscheck.eu/" target="_blank">https://www.addresscheck.eu/</a>'
							);
							?>
						</div>

						<?php do_settings_sections( 'xqueue-adc-section' ); ?>
					</div>
					<?php submit_button(); ?>
				</div>
			</form>

		</div><!-- /.wrap -->

			<script type="text/javascript">
				jQuery(function() {
					jQuery("#maileon_plugin_config_tabs").tabs();
					console.log("test");
				});
			</script>

		<?php
	}
}
