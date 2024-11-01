var $jQ = jQuery.noConflict();

$jQ(document).ready(function() {
    grecaptcha.ready(function() {
        grecaptcha.execute(xqueue_recaptcha.site_key, {action: 'subscribe'}).then(function(token) {
            var recaptchaResponse = document.getElementById('recaptcha');
            if (recaptchaResponse != null) {
                recaptchaResponse.value = token;
            }
        });
    });
});