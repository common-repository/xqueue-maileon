
=== Maileon for WordPress ===
Contributors: marcus.beckerle@xqueue.com
Tags: xqueue, xq, maileon, email, marketing, newsletter, subscribe, subscription, integration
Requires at least: 4.2
Tested up to: 6.7.0
Stable tag: 2.16.3
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Subscribe contacts with your Maileon account as newsletter recipients.

== Description ==

### Maileon for WordPress

This plugin helps you to set up forms for subscribing contacts with your mailing lists in [Maileon](https://www.maileon.de/). It is fully configurable and provides customizable templates for simple forms, a sidebar widget, and the possibility to build more complex forms e.g. with Contact Forms 7. Since version 2.6.0 it is possible to add [AddressCheck](https://www.addresscheck.eu/) for your form to prevent misspelled e-mail addresses.

### Some features

* Configuring your wordpress to connect with your Maileon account using the admin GUI
* Use shortcodes to include a form whereever you want, different templates for different forms are possible
* Customer and (child) theme support for forms
* Multilingual support, e.g. Polylang
* AddressCheck support


== Installation ==

### Installing Requirements
Make sure cURL, SPL and SimpleXML for PHP are installed and enabled and that you are using PHP 5.6.x or higher.

### Installing the plugin
1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **Maileon for WordPress** and click &quot;*Install now*&quot;
1. Alternatively, download the plugin and upload the contents of &apos;maileon-for-wp.zip&apos; to your plugins directory, which usually is &apos;/wp-content/plugins/&apos;.
1. Activate the plugin
1. Set your API key under Settings -> Maileon.

### Configuring Forms

#### Configuring Shorttags
You can add a standard form using the shortcode [maileon-contact].
To configure the behavior of the form, you can set the attributes from the [REST-API documentation](https://maileon.com/support/create-contact/) for each form. An example for a common shorttag: **[maileon-contact doi=&quot;true&quot; doiplus=&quot;true&quot; sync_mode=1 ]**

For more information about the shorttags, please refer to our [shorttag documentation](https://maileon.com/support/wordpress-plugin-shorttag-configuration/)

Please note that by default already existing contacts are **not updated** to avoid customers overriding information. If you think that updates should be allowed, please pass parameter sync_mode=1.
Example: **[maileon-contact sync_mode=1]**

Using multiple forms on the same page can be achieved by using a different template for each form, or by specifying the `name` shortcode attribute for each.

#### Configuring Forms
The plugin comes with a set of predefined forms for newsletter subscription and allows adding own custom forms. In addition, there is the possibiligy to add forms for profile updates, e.g. letting a contact add or edit details.

For handling forms, please refer to our [form documentation](https://maileon.com/support/wordpress-plugin-how-to-use-forms/)


### Configuring Sidebar Widget(s)
The sidebar widget can be added and customized like any other widget. It provides a single registration form. The title and submit button text can be changed in the widget settings and it uses the confirmation messages from the general plugin settings.

For more details how to use the sidebar widget, please refer to our [sidebar documentation](https://maileon.com/support/wordpress-plugin-sidebar-widget/)

### Integration With Other Form Plugins

#### ContactForm7
The form can also be connected with other form plugins, here an example for Contact Forms 7.
The basic approach is to add the following hook to your Wordpress by using a plugin like **Code Snippets** (https://de.wordpress.org/plugins/code-snippets/)

	// ------------------------------ Start CF7 Hook ------------------------------
	// Prevent CF7 from sending mails
    add_action( 'wpcf7_before_send_mail', 'xq_wpcf7_before_send_mail' );

    function xq_wpcf7_before_send_mail( $wpcf7_data ) {
    	// Since version 4.0 the author changed $skip_mail to be private, use hook instead
        //$wpcf7_data->skip_mail = true;
    	add_filter('wpcf7_skip_mail','xq_wpcf7_skip_mail');

        $submission = WPCF7_Submission::get_instance();
        $formData= $submission->get_posted_data();

    	// If only a form with a certain ID should be submitted, provide the id here
    	// This can be extended to use an array of IDs or even check if a hidden field is available in the posted data...
    	//if (in_array($wpcf7_data->id(), array(16, 31))) {

        // or use the shortcode ID:
        // if (in_array($wpcf7_data->hash(), array('0aa65a4, '38b70cf'))) {

    		$result = XQ_Maileon::register_contact($formData);

    		// If there is an error set a message and add the error handler
    		if (!$result['response']->isSuccess()) {
    			global $message;
    			$message = "Es ist ein Fehler aufgetreten: " . $result['response']->getBodyData();
    			add_filter( 'wpcf7_ajax_json_echo', 'xq_wpcf7_ajax_json_echo', 10, 2 );
    		}
    	//}
    	return $wpcf7_data;
    }

    // Method to update the status message
    function xq_wpcf7_ajax_json_echo($response, $result) {
        global $message;
        $response['mailSent'] = false;
        $response['status'] = 'mail_failed';
        $response['message'] = $message;
        return $response;
    }

    // New method to skip mails from CF7 itself
    function xq_wpcf7_skip_mail($f){
        $submission = WPCF7_Submission::get_instance();
        return true;
    }
	// ------------------------------ End CF7 Hook------------------------------

This piece of code catches the submission of the form and checks if the ID of the form matches one of the provided elements, here 16 or 31. this check has been added to prevent all contact forms to submit data to Maileon, you can of course also remove this condition or check if a certain (hidden) post field is available. If the condition is fulfilled it registers the contact with Maileon. It also displays an error message in case of errors.
You can also pass configuration parameters in the form. The following are valid parameters and documented at: https://maileon.com/support/create-contact/

* [hidden doi &quot;true&quot;]
* [hidden doiplus &quot;true&quot;]
* [hidden doimailing &quot;&quot;]
* [hidden permission &quot;1&quot;]
* [hidden sync_mode &quot;1&quot;]

#### Elementor
Elementor comes with a bunch of useful methods to submit data to external services.
Unfortunatelly you can neither define the data name of an element (you can set label, placeholder and even the ID but not the name) nor use placeholders for the GET parameters in the URL. As Elementor uses the language dependent Label as the "name" for data submission, it is not possible to use the webhook module directly. Instead, you must register a hook and process the data using our plugin.

The basic approach is to add the following hook to your Wordpress by using a plugin like **Code Snippets** (https://de.wordpress.org/plugins/code-snippets/)

    // ------------------------------ Start Elementor Hook ------------------------------
    class Maileon_Elementor_Form_Hook {

        function register_maileon(){
            //Add our additional webhook right here
            add_action( 'elementor_pro/forms/new_record', array( $this, 'maileon_elementor_form_callback' ), 10, 2 );
        }

        function maileon_elementor_form_callback( $record, $ajax_handler ) {

            $form_data = $record->get_formatted_data();

            // As you cannot specify the 'name' of form elements in elementor (you can specify the label, placeholders and the element id)
            // Elementor submits the form with the language dependent form label!
            // Here, you need to translate the form elements to the Maileon standard nomenclature. Also add configuration parameters here, if required.
            // Hint: If you want to use Maileon only for a single form, best give the form a unique name (in Elementor). Here we have set "Maileon Form" as name...

            //if ($record->get_form_settings( 'form_name' ) == "Maileon Form") {
                $new_data = array(
                    'email' => isset( $form_data['E-Mail'] ) ? $form_data['E-Mail'] : '',
                    'standard_LASTNAME' => isset( $form_data['Name'] ) ? $form_data['Name'] : '',
                    'standard_FIRSTNAME' => isset( $form_data['Vorname'] ) ? $form_data['Vorname'] : '',
                    'sync_mode' => 1,
                    'doiplus' => true
                );

                $result = XQ_Maileon::register_contact($new_data);

                // If there is an error set a message and add the error handler
                if (!$result['response']->isSuccess()) {
                    $message = "Es ist ein Fehler aufgetreten. Status-Code: " . $result['response']->getStatusCode();

                    //$ajax_handler->add_error( $field['id'], $message );
                    $ajax_handler->add_error_message( $message );

                    $ajax_handler->is_success = false;
                }
            //}
        }
    }
    $maileonHook = new Maileon_Elementor_Form_Hook();
    $maileonHook->register_maileon();

    // ------------------------------ End Elementor Hook ------------------------------

As commented in the code, you can use the plugin also for single forms, only by using e.g. the name of the form and uncomment the if clause.

### Adding AddressCheck (ADC)
You can help users preventing entering misspelled e-mail addresses by enabling AddressCheck in the plugin settings. ADC will check the mailbox of the e-mail address entered in your form in order to decide if it really exists or not. To enable AddressCheck, just provide your API user and API key and check "Use ADC". You can also select an ADC check delay in seconds to prevent the plugin from checking the given address too often and an ID of the e-mail form field. The plugin will display ADC results by setting classes 'adc_status_icon_valid', 'adc_status_icon_warn', or 'adc_status_icon_invalid' to the e-mail form field and making an element with ID 'adc_error_message' visible or invisible, depending if there is a problem or not. An example form with ADC can be found in **template='maileon-contact-form-adc-sample.php'**

### Using Plugins like Polylang to translate texts
The plugin supports Polylang (https://wordpress.org/support/plugin/polylang/), so you can set up different texts e.g. for successful registrations. Just install and set up Polylang and go to "Languages" -> "String Translations" and filter for "xqueue-maileon" to get a list of translatable content.

== Frequently Asked Questions ==
= When sending a form I get an error in the log "Headers already sent" =
This plugin can redirect to pages but this is only possible if no headers were already sent to the web browser, i.e. if no (other) plugin has already sent output. To avoid this, this script buffers output for POST requests. If you still find this error message, please contact us.
= Can I use caching in combination with your plugin? =
This plugin uses serverside sessions to save values for the (old) captcha method. If caching is enabled for the newsletter registration forms it can happen that outdated captcha data is shown. Further, on POST requests, this plugin enables buffering as some other plugins sometimes send headers early which makes it impossible to forward to proper landingpages later on.
= How can I check error logs when something failed? =
We do not show internal error information to frontend users. When you enable the WP debug mode (define(&apos;WP_DEBUG&apos;, true) and define(&apos;WP_DEBUG_LOG&apos;, true)) the plugin writes errors and warnings to the WP debug log, which you can usually find  under /wp-content/debug.log.
= When sending the form I only get a (more or less) blank page. Why? =
Check your logs, most probably you do not have cURL for PHP installed. You should also see a warning in your administration panel.
= When sending the form I always get an error even if my API-Key is correct. Why? =
Check your logs, most probably trigger Maileon to send a DOI request but you have not specified a default DOI Mailing under &quot;Settings -> Double-Opt-In&quot;. Please do so and retry.
= When pasting a form from the documentation page I get strange errors when sending the form =
If you get a warning like &quot;Invalid argument supplied for foreach() *path*\xqueue-maileon\class\class-xq-maileon.php on line 306&quot; it might be an issue with special chars generated by the webserver when displaying the manual. We noticed that some examples are displayed using different types of colons for &apos; or &quot;. Please make sure to use only those two characters to mark strings etc. in your form descriptor, e.g. instead use [maileon-contact standard=&quot;{&apos;LOCALE&apos;:&apos;de_DE&apos;}&quot; doimailing=&quot;FF3OfzjS&quot;] instead of [maileon-contact standard=&quot;{&apos;LOCALE&apos;:&apos;de_DE&apos;}&quot; doimailing=&quot;FF3OfzjS&quot;]
= When I copy your examples from this documentation, they produce errors in my wordpress =
Sometimes the plugin page for Wordpress translates some signs like regular " or ' into special characters for better display. You need to replace them by the proper signs &quot; and &apos;.
= I cannot send the form, it always claims there is an error. =
The captcha implementation that relies on calculating simple math can cause this problem. If you cache the newsletter registration page, it might be possible, that the numbers are not updated in the form. Please make sure to disable caching for the registration page. Also it relies on sessions, so if sessions are disabled or broken, the captcha cannot work. In this case maybe think of using Google reCaptcha.
= I am not using DOI and DOI+ as permissions, does this work? =
The current version is aligned with German law and thus, supports working with DOI and DOI+. In the short tags of registration forms, you can set doi to false and select a different permission to be set without DOI mail but for profile update pages, there is currently no possibility to change between different permissions than DOI and DOI+ (add/remove single user tracking).

== Screenshots ==
The configuration panel

== Upgrade Notice ==
None yet.

== Changelog ==

= 2.16.3 - 19.03.2024 =
* Fixed an issue with not being able to hide the footer popup registration
* Added the 'name' shortcode attribute to differentiate multiple forms on the same page

= 2.16.2 - 04.11.2023 =
* Fixing confirmation message on contact update

= 2.16.1 - 26.09.2023 =
* Enhanced output escaping
* Applied new Wordpress code styles
* Enhanced loading templates

= 2.16.0 - 13.09.2023 =
* Enhanced input validation and output sanitation for config and registration forms.
* Added nicer default form

= 2.15.3 - 10.03.2023 =
* Fixing problem with spaces in custom field and preference names configured in shorttags. Now spaces in names are allowed and in forms, spaces can be escaped by using "_space_", see documentation for more information.

= 2.15.2 - 04.01.2023 =
* Fixing warning with PHP8 and empty path in file_get_contents when checking child template for form templates

= 2.15.1 - 29.12.2022 =
* Enabled src and subscription_page as parameters for shorttags

= 2.15.0 - 09.11.2022 =
* Enhanced update form handling. Previously contact ID and checksum might not have been passed properly, if sessions were restricted.

= 2.14.0 - 25.05.2022 =
* Fixed compatibility with WP6/PHP8

= 2.13.0 - 14.04.2021 =
* Added contact preference possibility

= 2.12.6 - 14.04.2021 =
* Fixed problem with settings of Gravity Forms "Entries" when returning JSON instead o HTML as content type header

= 2.12.5 - 14.04.2021 =
* Added two control parameters to 'maileon-contact-form-with-placeholders.php':
** "hide_form_salutation=false" to show a salutation selection. Default: true
** - "hide_form_confirmation_fields=true" to hide confirmation fields. Default: false

= 2.12.4 - 08.04.2021 =
* Changed example mergetags to use strings for parameters doi and doiplus as 1 and 0 caused problems for some customers
* Adding new example template using the labels as placeholders: maileon-contact-form-with-placeholders.php
* Adding option to set data privacy organization name

= 2.12.3 - 17.03.2021 =
* Fixing problem with saving contact on profile update pages
* Fixing another possible problem with path of admin stylesheet

= 2.12.2 - 15.03.2021 =
* Fixing error message in admin about undefined constant _FILE_

= 2.12.1 - 12.03.2021 =
* Fixing loading css file for admin settings
* Reduced calls or ob_start to POST actions to prevent other plugins from sending headers too early

= 2.12.0 - 11.03.2021 =
* Updated Maileon PHP API client
* Switched from cURL to ADC API client
* Added enhanced input filtering and sanitation
* Enhanced admin panel to be better understandable, also adding example shortcodes
* Cleaned PHP, e.g. removing PHP short-open-tags


= 2.11.0 - 10.03.2021 =
* Fixing display problem for acknowledgement method in sidebar widget
* Updated compatibility

= 2.10.0 - 01.12.2020 =
* Adding support for Polylang [Thank you "Greatives" for your support!]
* Adding support for (child) theme templates for forms [Thank you "Greatives" for your support!]

= 2.9.4 - 27.11.2020 =
* Fixing labels section, which was not properly displayed since 2.9.1

= 2.9.3 - 17.11.2020 =
* Allowing (absolute) paths for "ok" and "error" page titles
* Fixing some output warnings in sample forms

= 2.9.2 - 28.10.2020 =
* Fixing displaying an error if custom template upload folder did not exist

== Changelog ==
= 2.9.1 - 21.08.2020 =
* Adding Google reCaptcha v3 for profile update pages
* Adding possibility to add a checkbox with name "doiplus" which shows if the user has DOI or DOI+ as permission

= 2.9.0 - 21.08.2020 =
* Major refactoring of configuration panel
* Adding Google reCaptcha v3 as alternative
* Bugfix for cases not accepting standard fields configured in the short tag
* Fixed ADC to work with sidebar
* Enhanced translations

= 2.8.2 - 09.07.2020 =
* Added shorttag attributes to new form if error was displayed. Before, these arrtibutes did not get passed.
* Fixed problem with shorttags without attributes

= 2.8.1 - 18.06.2020 =
* Fixed redirect after success or error

= 2.8.0 - 22.04.2020 =
* Moved shortcode execution into an action to avoid duplicate executions, e.g. with plugin "Easy Table of Content"
* Injecting shortcode attributes into forms to allow form controls in action

= 2.7.2 - 20.04.2020 =
* Moved shortcode initialization inside the init hook to avoid duplicate executions

= 2.7.1 - 01.04.2020 =
* Added parameters ok_page_id and error_page_id for register-contact-shortcodes to overwrite global settings

= 2.7.0 - 20.03.2020 =
* Fixing problem with Gutenbergeditor showing an error while saving due to AJAX saving method
* Added contact update shorttag and methods
* Refactored service classes
* Updated Maileon PHP-API-Client to 1.5.5

= 2.6.3 - 11.03.2020 =
* Fixing session warnings
* Updated finding uploads folder according to wordpress standard

= 2.6.2 - 20.02.2019 =
* Updating CF7 instructions
* Cleaning up code

= 2.6.1 - 11.12.2018 =
* Fixed problem causing Wordpress to be stuck for some time, when editing a template file and saving it

= 2.6.0 - 14.08.2018 =
* Updated Maileon PHP api client to version 1.3.8
* Added backend configuration for AddressCheck
* Added frontend and backend code for including AddressCheck
* Added example form for using AddressCheck

= 2.5.2 - 13.07.2018 =
* Fixed bug when specifying a not-existing form template on a page causing the page to be non editable in backend
* Added loading templates from wp-content/uploads/xqueue-maileon/views as fallback

= 2.5.0 - 11.07.2018 =
* Added multilingual support under domain *"xq_maileon"*, it can be edited with Wordpress "GetText" as supported by simple language files or plugins like WPML
* Removed CF7 sample code from plugin, removed instruction to add it there as it might be overridden when updating the plugin

= 2.4.2 - 04.05.2018 =
* Fixed bug with not existing template tag

= 2.4.1 - 04.05.2018 =
* Possibility to add data privacy acknowledgement checkbox and general newsletter subscription checkbox to form
* Added setting for privacy description link

= 2.4.0 - 04.05.2018 =
Information: if you update, please make sure your form still works as expected as the templating system has changed.

* Added option for selecting an own template file.
* Changed templating to be able to use PHP code in own files to allow more flexibility

= 2.3.1 - 23.11.2017 =
* Added debug attribut
* Tested with Wordpress 4.9

= 2.3.0 - 01.09.2017 =
* Added options to display pages on OK or ERROR instead of messages on the same page
* added page buffer to prevent "header not send" errors when redirecting

= 2.2.6 - 31.08.2017 =
* Fixed bug when submitting arrays in a form
* Removed magic_quotes_gpc() (as deprecated) and strimpslashes

= 2.2.5 - 09.08.2017 =
* Updates examples, tested responses for CF7

= 2.2.4 - 01.06.2017 =
* Validated functionality for 4.8
* Added proxy information from WP Constants WP_PROXY_HOST and WP_PROXY_PORT (thanks to @wdjac)

= 2.2.3 - 20.04.2017 =
* Fixed displaying error details when the server failed to resolve the root CA of the certificate of https://api.maileon.com
* Added examples to description

= 2.2.2 - 22.11.2016 =
* Fixed possible problem with some PHP versions not being able to disable DOI mailings

= 2.2.1 - 04.10.2016 =
* Fixed encoding to UTF without BOM
* Removed test <hr /> from default form template

= 2.2.0 - 04.10.2016 =
* Added check if cURL for PHP is installed, otherwise an error will be displayed in the admin panel.

= 2.1.0 - 12.09.2016 =
* Update version number to indicate realy old version was 1.0
* Fixed problem with BOM in one file causing an error message when activating the plugin
* Added more IDs to the standard form
* Added config for enabling/disabling captcha for the standard form

= 1.0.0 - 31.08.2016 =
* Initial version