(function($){
    
    // Fix as live has veen removed, see explained in https://stackoverflow.com/questions/14354040/jquery-1-9-live-is-not-a-function
    jQuery.fn.extend({
        live: function (event, callback) {
           if (this.selector) {
                jQuery(document).on(event, this.selector, callback);
            }
            return this;
        }
    });

    $(document).ready( function(){
        //some cosmetic stuff for the shortcode form
        var $et_contact_container = $('.maileon_contact_form_container');
        if ( $et_contact_container.length ) {
                var $et_contact_form = $et_contact_container.find( 'form' ),
                    $et_inputs = $et_contact_form.find('input[type=text],textarea');

                //if the inputs hold the default values, empty them on focus, 
                //and repopulate on blur
                $et_inputs.live('focus', function(){
                        if ( $(this).val() === $(this).siblings('label').text() ) $(this).val("");
                }).live('blur', function(){
                        if ($(this).val() === "") $(this).val( '' ); // Don't use the default text 
                });
        }
        
        //cosmetic stuff/session handling for the floating footer
        var $maileon_footer_fixed_bar = $('.maileon_footer_fixed_bar');
        if ( $maileon_footer_fixed_bar.length ) {
                var $form = $maileon_footer_fixed_bar.find( 'form' ),
                $inputs = $form.find('input[type=text],textarea');
                
                //on submit mark the footer closed
                $submit_button = $form.find('input[type=submit]');
                $submit_button.live('click', function() {
                    $.ajax({
                      url:     maileon_ajax.url,
                      data:    ({action  : 'maileon_close_footer'}),
                      success: function(data){ }
                    })
                });

                //if the inputs hold the default values, empty them on focus, 
                //and repopulate on blur
                $inputs.live('focus', function(){
                        if ( $(this).val() === $(this).siblings('label').text() ) $(this).val("");
                }).live('blur', function(){
                        if ($(this).val() === "") $(this).val( $(this).siblings('label').text() );
                });
                
                //show the footer
                $maileon_footer_fixed_bar.slideDown( {duration: 1000, queue: true, ease: 'easeInOutCubic' } );
        }
        
        //do something when the close div is clicked
        var $maileon_footer_close = $('.maileon_footer_close');
        if( $maileon_footer_close.length ) {
            //on closing mark the footer closed and hide it
            $maileon_footer_close.live('click', function() {
                $.ajax({
                  url:     maileon_ajax.url,
                  data:    ({action  : 'maileon_close_footer'}),
                  success: function(data){ }
                });

                $('.maileon_footer_fixed_bar').slideUp( {duration: 1000, queue: true, ease: 'easeInOutCubic' } );
            });
        }
    });

})(jQuery)
