<?php
/**
 * Tempate for the captcha block of contact forms
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

?>
<div class="et_pb_contact_right" id="maileon_contact_form_captcha_container" >
	<p class="clearfix">
		<?php
		echo esc_html(
			sprintf(
				'%1$s + %2$s =',
				filter_var( isset( $_SESSION['maileon-captcha-first-digit'] ) ? $_SESSION['maileon-captcha-first-digit'] : 0, FILTER_VALIDATE_INT ),
				filter_var( isset( $_SESSION['maileon-captcha-second-digit'] ) ? $_SESSION['maileon-captcha-second-digit'] : 0, FILTER_VALIDATE_INT )
			)
		);
		?>
		<input type="text" size="2" id="maileon_contact_form_captcha" class="input et_pb_contact_captcha" value="" name="maileon-captcha" required="true">
	</p>
</div>