(function($){
	
	// Even if it sounds strange use admin-ajax.php to submit client ajax requests to
	//var wp_ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
	
	$(document).ready( function(){
		
		$('#xq_sidebar_message').hide();
					
		// Sidebar code
		$('#xq_nl_sidebar_form').on('submit', function (e) {

		  e.preventDefault();

		  $.post(
				config.ajax_url, 
				{
					'action': 'xq_subscription',
					'data':   $(this).serialize()
				}, 
				function(response){
					if (response['error'] == false) {
						$('#xq_nl_sidebar_form').hide(200);
					}
					$('#xq_sidebar_message').show();
					$('#xq_sidebar_message').text(response['message']);
					
					//console.log('The server responded: ' + response, response);
				}
			);

		});
	});

})(jQuery)
