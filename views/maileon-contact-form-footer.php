<?php
/**
 * Tempate part for the footer of a contact form
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

?>
<div class="maileon_footer_fixed_bar">
	<div class="maileon_footer_close"><?php echo esc_html( get_option( 'footer_close_label', __( 'Close', 'xq_maileon' ) ) ); ?><div class="maileon_footer_close_icon">Q</div></div>
	<div class="maileon_footer_clear"></div>

	<div class="maileon_footer_form_container">
		<form class="maileon_footer_form" method="post" action="<?php echo esc_url( get_permalink( get_option( 'footer_default_post_id' ) ) ); ?>">

			<div class="maileon_footer_row_container">
				<span class="maileon_footer_label maileon_footer_subscribe"><?php echo esc_html( get_option( 'footer_subscribe_label' ) ); ?></span>
				<span class="maileon_footer_label maileon_footer_newsletter"><?php echo esc_html( get_option( 'footer_newsletter_label' ) ); ?></span>
			</div>

			<div class="maileon_footer_spacer maileon_footer_input_inline">
				<label class="maileon_footer_input_label" for="maileon-name"><?php echo esc_html( get_option( 'label_name', __( 'Name', 'xq_maileon' ) ) ); ?></label>
				<input type="text" value="<?php echo esc_attr( get_option( 'label_name', __( 'Name', 'xq_maileon' ) ) ); ?>" name="maileon-name">
			</div>

			<div class="maileon_footer_spacer maileon_footer_input_inline">
				<label class="maileon_footer_input_label" for="maileon-email"><?php echo esc_html( get_option( 'label_email', __( 'E-mail', 'xq_maileon' ) ) ); ?></label>
				<input type="text" value="<?php echo esc_attr( get_option( 'label_email', __( 'E-mail', 'xq_maileon' ) ) ); ?>" name="maileon-email">
			</div>

			<div class="maileon_footer_row_container">
				<span class="maileon_footer_label maileon_footer_captcha_label"><?php echo esc_html( get_option( 'footer_captcha_label', __( 'Captcha', 'xq_maileon' ) ) ); ?></span>
				<span class="maileon_footer_label maileon_footer_captcha_value">
					<?php
					echo esc_html(
						sprintf(
							'%1$s + %2$s =',
							filter_var( isset( $_SESSION['maileon-captcha-first-digit'] ) ? $_SESSION['maileon-captcha-first-digit'] : 0, FILTER_VALIDATE_INT ),
							filter_var( isset( $_SESSION['maileon-captcha-second-digit'] ) ? $_SESSION['maileon-captcha-second-digit'] : 0, FILTER_VALIDATE_INT )
						)
					);
					?>
				</span>
			</div>

			<div class="maileon_footer_input_inline">
				<label class="maileon_footer_input_label" for="maileon-captcha">X</label>
				<input type="text" size="2" value="X" name="maileon-captcha">
			</div>

			<input type="submit" value="<?php echo esc_attr( sprintf( '%s >', get_option( 'label_submit', __( 'Submit', 'xq_maileon' ) ) ) ); ?>" class="maileon_footer_spacer maileon_footer_submit_button">
		</form>
	</div>
</div>