<?php
/**
 * Tempate for a contact form
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

?>

<div class="maileon_contact_form_container">
	<div class="et_pb_contact">
		<form id="maileon_contact_form" class="et_pb_contact_form clearfix" method="post" action="<?php echo esc_url( get_permalink() ); ?>">

			<style>
				:root {
					--color-light: white;
					--color-dark: #212121;
					--color-signal: #fab700;

					--color-background: var(--color-light);
					--color-text: var(--color-dark);
					--color-accent: var(--color-signal);

					--size-bezel: .5rem;
					--size-radius: 4px;

					line-height: 1.4;

					font-family: 'Inter', sans-serif;
					font-size: calc(.6rem + .4vw);
					color: var(--color-text);
					background: var(--color-background);
					font-weight: 300;
					padding: 0 calc(var(--size-bezel) * 3);
				}

				.subscription_form h1, h2, h3 {
					font-weight: 900;
				}

				.subscription_form .card {
					background: var(--color-background);
					padding: calc(4 * var(--size-bezel));
					margin-top: calc(4 * var(--size-bezel));
					border-radius: var(--size-radius);
					border: 3px solid var(--color-shadow, currentColor);
					box-shadow: .5rem .5rem 0 var(--color-shadow, currentColor);

				}

				.subscription_form .card:first-child {
					margin-top: 0px;
				}


				.subscription_form {
					max-width: 40rem;
					padding: 1rem;
				}

				.subscription_form .form_container {
					margin-top: 15px;
				}

				.subscription_form .input {
					position: relative;
				}

				.subscription_form .input-label {
					position: absolute;
					left: 0;
					top: -20px;
					padding: calc(var(--size-bezel) * 0.75) calc(var(--size-bezel) * .5);
					padding-top: 0;
					padding-bottom: 0;
					margin: calc(var(--size-bezel) * 0.75 + 3px) calc(var(--size-bezel) * .5);
					background: white;
					white-space: nowrap;
					transform: translate(0, 0);
					transform-origin: 0 0;
					transition: transform 120ms ease-in;
					font-weight: bold;
					line-height: 1.2;
				}

				.subscription_form .input-field {
					box-sizing: border-box;
					display: block;
					width: 100%;
					border: 3px solid currentColor;
					padding: calc(var(--size-bezel) * 1.5) var(--size-bezel);
					color: currentColor;
					background: transparent;
					border-radius: var(--size-radius);
				}


				.subscription_form .button-group {
					margin-top: calc(var(--size-bezel) * 2.5);
				}

				.subscription_form button {
					color: currentColor;
					padding: var(--size-bezel) calc(var(--size-bezel) * 2);
					background: var(--color-accent);
					border: none;
					border-radius: var(--size-radius);
					font-weight: 900;
					cursor: pointer;
				}

				.subscription_form .reset_button {
					background: lightgrey;
				}

				.subscription_form button + button {
					margin-left: calc(var(--size-bezel) * 2);

				}

				.subscription_form .icon {
					display: inline-block;
					width: 1em; height: 1em;
					margin-right: .5em;
				}

				.subscription_form .hidden {
					display: none;
				}
			</style>
			<article class="subscription_form">

				<div class="card">
				<h2><?php esc_html_e( 'Newsletter', 'xq_maileon' ); ?></h2>

				<div class="form_container">
					<label class="input">
						<input id="maileon_contact_form_firstname" name="standard_FIRSTNAME" class="input-field" type="text" placeholder=" " value="<?php echo esc_attr( ! empty( $form_data['standard_FIRSTNAME'] ) ? $form_data['standard_FIRSTNAME'] : '' ); ?>" />
						<span id="maileon_contact_form_firstname_label" class="input-label"><?php echo esc_html( get_option( 'label_firstname', __( 'firstname', 'xq_maileon' ) ) ); ?></span>
					</label>
				</div>
				<div class="form_container">
					<label class="input">
						<input id="maileon_contact_form_lastname" name="standard_LASTNAME" class="input-field" type="text" placeholder=" " value="<?php echo esc_attr( ! empty( $form_data['standard_LASTNAME'] ) ? $form_data['standard_LASTNAME'] : '' ); ?>" />
						<span id="maileon_contact_form_lastname_label" class="input-label"><?php echo esc_html( get_option( 'label_lastname', __( 'lastname', 'xq_maileon' ) ) ); ?></span>
					</label>
				</div>
				<div class="form_container">
					<label class="input">
						<input id="maileon_contact_form_email" name="email" class="input-field" type="text" placeholder=" " value="<?php echo esc_attr( ! empty( $form_data['email'] ) ? $form_data['email'] : '' ); ?>"  required="true" />
						<span id="maileon_contact_form_email_label" class="input-label"><?php echo esc_html( get_option( 'label_email', __( 'E-mail', 'xq_maileon' ) ) ); ?></span>
					</label>
				</div>


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

				<?php
					// The value of captcha is already escaped. The logic is kept for backwards compatibility.
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $captcha;
				?>

				<?php if ( ! empty( $message ) ) : ?>
					<p class="maileon_warning clearfix">
						<?php
							/* translators: %s is the reason of the form error */
							echo esc_html( sprintf( __( 'Please fill the form correctly: %s', 'xq_maileon' ), $message ) );
						?>
					</p>
				<?php endif; ?>

				<div class="button-group">
					<button id="maileon_contact_form_button"><?php echo esc_html( get_option( 'label_submit', __( 'Submit', 'xq_maileon' ) ) ); ?></button>
					<button class="reset_button" type="reset"><?php esc_html_e( 'Reset', 'xq_maileon' ); ?></button>
				</div>
				</div>
			</article>
		</form>
	</div> <!-- .et_pb_contact -->
</div> <!-- .et_pb_contact_form_container -->

