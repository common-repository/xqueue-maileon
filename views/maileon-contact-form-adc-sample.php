<?php
/**
 * Tempate sample for a custom contact form with AddressCheck
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

?>
<style>
	#maileon_contact_form_email.adc_status_icon_valid {
		background: url(<?php echo esc_url( plugins_url( '../img/icon_valid.png', __FILE__ ) ); ?>) no-repeat scroll 7px;
		padding-left:40px;
	}

	#maileon_contact_form_email.adc_status_icon_warn {
		background: url(<?php echo esc_url( plugins_url( '../img/icon_warn.png', __FILE__ ) ); ?>) no-repeat scroll 7px;
		padding-left:40px;
	}

	#maileon_contact_form_email.adc_status_icon_invalid {
		background: url(<?php echo esc_url( plugins_url( '../img/icon_invalid.png', __FILE__ ) ); ?>) no-repeat scroll 7px;
		padding-left:40px;
	}

	#maileon_contact_form_email.adc_status_icon_loading {
		background: url(<?php echo esc_url( plugins_url( '../img/icon_loading.gif', __FILE__ ) ); ?>) no-repeat scroll 7px;
		padding-left:40px;
	}
</style>
<div class="maileon_contact_form_container">
	<div class="et_pb_contact">
		<form id="maileon_contact_form" class="et_pb_contact_form clearfix" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
			<div class="et_pb_contact_left">
				<p class="clearfix">
					<label id="maileon_contact_form_gender_label" class="et_pb_contact_form_label"><?php esc_html_e( 'Salutation', 'xq_maileon' ); ?></label>
					<select id="maileon_contact_form_gender" class="input et_pb_contact_name" name="standard_GENDER">
						<option value="" disabled selected value>-</option>
						<option value="m" <?php selected( isset( $form_data['standard_GENDER'] ) ? $form_data['standard_GENDER'] : '', 'm' ); ?>><?php esc_html_e( 'Mister', 'xq_maileon' ); ?></option>
						<option value="f" <?php selected( isset( $form_data['standard_GENDER'] ) ? $form_data['standard_GENDER'] : '', 'f' ); ?>><?php esc_html_e( 'Madam', 'xq_maileon' ); ?></option>
					</select>
				</p>
				<p class="clearfix">
					<label id="maileon_contact_form_firstname_label" class="et_pb_contact_form_label"><?php echo esc_html( get_option( 'label_firstname', __( 'firstname', 'xq_maileon' ) ) ); ?></label>
					<input id="maileon_contact_form_firstname" type="text" class="input et_pb_contact_name" value="<?php echo esc_attr( ! empty( $form_data['standard_FIRSTNAME'] ) ? $form_data['standard_FIRSTNAME'] : '' ); ?>" name="standard_FIRSTNAME">
				</p>
				<p class="clearfix">
					<label id="maileon_contact_form_lastname_label" class="et_pb_contact_form_label"><?php echo esc_html( get_option( 'label_lastname', __( 'lastname', 'xq_maileon' ) ) ); ?></label>
					<input id="maileon_contact_form_lastname" type="text" class="input et_pb_contact_name" value="<?php echo esc_attr( ! empty( $form_data['standard_LASTNAME'] ) ? $form_data['standard_LASTNAME'] : '' ); ?>" name="standard_LASTNAME">
				</p>
				<p class="clearfix">
					<label id="maileon_contact_form_email_label" class="et_pb_contact_form_label"><?php echo esc_html( get_option( 'label_email', __( 'E-mail', 'xq_maileon' ) ) ); ?></label>
					<input id="maileon_contact_form_email" type="text" class="adc_status_icon_warn input et_pb_contact_email" value="<?php echo esc_attr( ! empty( $form_data['email'] ) ? $form_data['email'] : '' ); ?>" name="email" required="true">
					<span id="adc_error_message" style="display: none;"><?php esc_html_e( 'E-Mail is maybe invalid.', 'xq_maileon' ); ?></span>
				</p>
				<p id="adc_error_image" class="clearfix" style="display: none;">
				</p>
				<?php
					// The value of captcha is already escaped. The logic is kept for backwards compatibility.
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $captcha;
				?>
				<p class="clearfix">
					<label id="maileon_contact_form_privacy_label" style="font-weight:normal;">
						<input id="maileon_contact_form_privacy" name="privacy" value="true" type="checkbox" required="true">
						&nbsp;
						<?php
						printf(
							/* translators: %1$s is the link open tag, $2$s is the link close tag, %3$s is a company name */
							esc_html__( 'I have taken notice of the %1$sprivacy declaration%2$s of %3$s.', 'xq_maileon' ),
							sprintf( '<a href="%s" target="_blank">', esc_url( get_option( 'PRIVACY_PAGE', '' ) ) ),
							'</a>',
							esc_html( get_option( 'ORGANIZATION', __( 'XQueue GmbH', 'xq_maileon' ) ) )
						)
						?>
					</label>
					<br/>
					<label id="maileon_contact_form_tracking_label" style="font-weight:normal;">
						<input id="maileon_contact_form_tracking" name="doiplus" value="true" type="checkbox">
						&nbsp;
						<?php esc_html_e( 'I agree with the newsletter tracking in order to optimize personal content.', 'xq_maileon' ); ?>
					</label>
				</p>
			</div> <!-- .et_pb_contact_left -->

			<?php if ( ! empty( $message ) ) : ?>
				<p class="maileon_warning clearfix">
					<?php
						/* translators: %s is the reason of the form error */
						echo esc_html( sprintf( __( 'Please fill the form correctly: %s', 'xq_maileon' ), $message ) );
					?>
				</p>
			<?php endif; ?>
			<input id="maileon_contact_form_button" type="submit" value="<?php echo esc_attr( get_option( 'label_submit', __( 'Submit', 'xq_maileon' ) ) ); ?>" class="et_pb_contact_submit">
		</form>
		</div> <!-- .et_pb_contact -->
</div> <!-- .et_pb_contact_form_container -->

