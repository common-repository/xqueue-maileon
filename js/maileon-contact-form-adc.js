var $jQ = jQuery.noConflict();

$jQ(document).ready(function() {

    var typingTimer;                //timer identifier

    var triggerDelayedEmailCheck = function() {
        clearTimeout(typingTimer);
        if ($jQ('#'+xqueue_adc_ajax.adc_email_field).val()) {
            typingTimer = setTimeout(checkEmailaddressWithAdc, xqueue_adc_ajax.adc_input_delay*1000); // Time in ms
        }
    }

    var checkEmailaddressWithAdc = function() {
        email = $jQ('#'+xqueue_adc_ajax.adc_email_field).val().trim();
        // deactivate cache
        $jQ.ajaxSetup({cache: false});
        // set up an asynchronous request and check return value
        $jQ('#adc_status_icon').removeClass('adc_status_icon_valid').removeClass('adc_status_icon_warn').removeClass('adc_status_icon_invalid').addClass('adc_status_icon_loading');

        $jQ.getJSON(xqueue_adc_ajax.url + '&email=' + email, function (result) {
            // See http://dev.addresscheck.eu/public/addressquality-check.html
            console.log(result);
            switch(result.address) {
                case 0:
                    $jQ('#adc_status_icon').removeClass('adc_status_icon_loading').addClass('adc_status_icon_invalid');
                    $jQ("#adc_error_message").show();
                    break;
                case 1:
                    $jQ('#adc_status_icon').removeClass('adc_status_icon_loading').addClass('adc_status_icon_valid');
                    $jQ("#adc_error_message").hide();
                    break;
                case 2:
                    $jQ('#adc_status_icon').removeClass('adc_status_icon_loading').addClass('adc_status_icon_warn');
                    $jQ("#adc_error_message").show();
                    break;
                default:
                // do nothing
            }
        }).fail(function() {
            $jQ('#adc_status_icon').removeClass('adc_status_icon_loading').addClass('adc_status_icon_warn');
            $jQ("#adc_error_message").show();
        });
    }


    //on keyup, start the countdown
    $jQ('#'+xqueue_adc_ajax.adc_email_field).keyup(triggerDelayedEmailCheck);
    /*
    $jQ('#'+xqueue_adc_ajax.adc_email_field).on( "autocompleteselect", triggerDelayedEmailCheck);
    $jQ('#'+xqueue_adc_ajax.adc_email_field).on( "paste", triggerDelayedEmailCheck);
    $jQ('#'+xqueue_adc_ajax.adc_email_field).on( "change", triggerDelayedEmailCheck);
    */
    $jQ('#'+xqueue_adc_ajax.adc_email_field).on( "focusout", triggerDelayedEmailCheck);

});