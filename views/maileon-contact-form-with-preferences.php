<?php
/**
 * Tempate sample for a form with flags:
 *  - "hide_form_salutation=false" to show a salutation selection. Default: true
 *  - "hide_form_confirmation_fields=true" to hide confirmation fields. Default: false
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

?>

<div class="maileon_contact_form_container">
	<div class="et_pb_contact">
		<form id="maileon_contact_form" class="et_pb_contact_form clearfix" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
			<div class="et_pb_contact_left">
				<?php if ( ! empty( $attributes['show_form_salutation'] ) && filter_var( $attributes['show_form_salutation'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
				<p class="clearfix">
					<label id="maileon_contact_form_gender_label" class="et_pb_contact_form_label"><?php esc_html_e( 'Salutation', 'xq_maileon' ); ?></label>
					<select id="maileon_contact_form_gender" class="input et_pb_contact_name" name="standard_GENDER">
						<option value="" disabled selected value>-</option>
						<option value="m" <?php selected( isset( $form_data['standard_GENDER'] ) ? $form_data['standard_GENDER'] : '', 'm' ); ?>><?php esc_html_e( 'Mister', 'xq_maileon' ); ?></option>
						<option value="f" <?php selected( isset( $form_data['standard_GENDER'] ) ? $form_data['standard_GENDER'] : '', 'f' ); ?>><?php esc_html_e( 'Madam', 'xq_maileon' ); ?></option>
					</select>
				</p>
				<?php endif; ?>

				<p class="clearfix">
					<input id="maileon_contact_form_firstname" type="text" class="input et_pb_contact_name" placeholder="<?php echo esc_attr( get_option( 'label_firstname', __( 'firstname', 'xq_maileon' ) ) ); ?>"value="<?php echo esc_attr( ! empty( $form_data['standard_FIRSTNAME'] ) ? $form_data['standard_FIRSTNAME'] : '' ); ?>" name="standard_FIRSTNAME">
				</p>
				<p class="clearfix">
					<input id="maileon_contact_form_lastname" type="text" class="input et_pb_contact_name" placeholder="<?php echo esc_attr( get_option( 'label_lastname', __( 'lastname', 'xq_maileon' ) ) ); ?>" value="<?php echo esc_attr( ! empty( $form_data['standard_LASTNAME'] ) ? $form_data['standard_LASTNAME'] : '' ); ?>" name="standard_LASTNAME">
				</p>
				<p class="clearfix">
					<input id="maileon_contact_form_email" type="text" class="input et_pb_contact_email" placeholder="<?php echo esc_attr( get_option( 'label_email', __( 'E-mail', 'xq_maileon' ) ) ); ?>" value="<?php echo esc_attr( ! empty( $form_data['email'] ) ? $form_data['email'] : '' ); ?>" name="email" required="true">
				</p>

				<p class="clearfix">
					<!-- this seems the best way to return false if not chcked... -->
					<input type="hidden" name="preference_Interessensgebiete:Technik" value="false" />
					<input id="maileon_contact_form_preference_1" type="checkbox" class="input" value="true" name="preference_Interessensgebiete:Technik"
						<?php checked( isset( $form_data['preference_Interessensgebiete:Technik'] ) ? $form_data['preference_Interessensgebiete:Technik'] : 'false', 'true' ); ?> /> Technik
				</p>
				<p class="clearfix">
					<input type="hidden" name="preference_Interessensgebiete:Reisen" value="false" />
					<input id="maileon_contact_form_preference_2" type="checkbox" class="input" value="true" name="preference_Interessensgebiete:Reisen"
					<?php checked( isset( $form_data['preference_Interessensgebiete:Reisen'] ) ? $form_data['preference_Interessensgebiete:Reisen'] : 'false', 'true' ); ?> /> Reisen
				</p>

			</div>

			<?php if ( ! empty( $message ) ) : ?>
				<p class="maileon_warning clearfix">
					<?php
						/* translators: %s is the reason of the form error */
						echo esc_html( sprintf( __( 'Please fill the form correctly: %s', 'xq_maileon' ), $message ) );
					?>
				</p>
			<?php endif; ?>
			<input id="maileon_contact_form_button" type="submit" value="<?php echo esc_attr( get_option( 'label_update', __( 'Update', 'xq_maileon' ) ) ); ?>" class="et_pb_contact_submit">
		</form>
	</div>
</div>

