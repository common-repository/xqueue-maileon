<?php
/**
 * This file contains the sidebar subscription widget
 *
 * @package xqueue-maileon
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

// Require the base plugin class.
require_once 'class/class-xq-maileon.php';

/**
 * Adds the newsletter subscription widget.
 */
class XQ_Maileon_Subscription_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'xq_maileon_nl', // Base ID.
			__( 'XQ Maileon Newsletter Subscription', 'xq_maileon' ), // Name.
			array( 'description' => __( 'Register subscribers in your Maileon newsletter account.', 'xq_maileon' ) ) // Args.
		);

		wp_register_script(
			'maileon-contact-form-widget-js',
			plugins_url( '/js/maileon-contact-form-widget.js', __FILE__ ),
			array( 'jquery' ),
			XQ_MAILEON_PLUGIN_VERSION,
			array( 'in_footer' => false )
		);

		wp_enqueue_script( 'maileon-contact-form-widget-js' );
		wp_localize_script(
			'maileon-contact-form-widget-js',
			'config',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
		);

		// Register script for ADC.
		wp_register_script(
			'maileon-contact-form-adc',
			plugins_url( 'js/maileon-contact-form-adc.js', __FILE__ ),
			array( 'jquery' ),
			XQ_MAILEON_PLUGIN_VERSION,
			array( 'in_footer' => false )
		);

		// Add the script required for using Addresscheck, if access data has been filled.
		if ( 'true' === get_option( 'adc_enabled' ) && ! empty( get_option( 'adc_api_user' ) ) && ! empty( get_option( 'adc_api_key' ) ) ) {
			wp_enqueue_script( 'maileon-contact-form-adc' );

			wp_localize_script(
				'maileon-contact-form-adc',
				'xqueue_adc_ajax',
				array(
					'url'             => '?xq_adc_ajax',
					'adc_input_delay' => get_option( 'adc_input_delay' ),
					'adc_email_field' => get_option( 'adc_email_field', 'maileon_contact_form_email' ),
				)
			);
		}
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$form_template = $instance['template'];
		if ( ! empty( $form_template ) ) {

			// Load template.
			$attributes = array(
				'doi'            => $instance['doi'],
				'doiplus'        => $instance['doiplus'],
				'doimailing'     => $instance['doimailing'],
				'permission'     => $instance['permission'],
				'sync_mode'      => $instance['sync_mode'],
				'subscribe_text' => $instance['subscribe'],
			);

			// The output of the function is already escaped.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo XQ_Maileon::show_form( null, null, $form_template, $attributes );
		} else {
			// Show old default form.
			echo '<form id="xq_nl_sidebar_form">';
			echo '<input id="maileon_contact_form_email" class="xq_sidebar_textfield" name="email" type="email" required>';
			echo '<button style="margin-top:1em;" id="xq_nl_subscription_button" class="xq_sidebar_button" type="submit" id="subscribe">' . esc_html( $instance['subscribe'] ) . '</button>';
			echo '<input name="doi" type="hidden" value="' . esc_attr( $instance['doi'] ) . '" />';
			echo '<input name="doiplus" type="hidden" value="' . esc_attr( $instance['doiplus'] ) . '" />';
			echo '<input name="doimailing" type="hidden" value="' . esc_attr( $instance['doimailing'] ) . '" />';
			echo '<input name="permission" type="hidden" value="' . esc_attr( $instance['permission'] ) . '" />';
			echo '<input name="sync_mode" type="hidden" value="' . esc_attr( $instance['sync_mode'] ) . '" />';
			echo '</form>';

			// The output of the function is already escaped.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo XQ_Maileon::show_message( '', 'xq_sidebar_message' );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title      = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'xq_maileon' );
		$subscribe  = ! empty( $instance['subscribe'] ) ? $instance['subscribe'] : __( 'Subscribe now', 'xq_maileon' );
		$doi        = ! empty( $instance['doi'] ) ? $instance['doi'] : 'true';
		$doiplus    = ! empty( $instance['doiplus'] ) ? $instance['doiplus'] : 'true';
		$doimailing = ! empty( $instance['doimailing'] ) ? $instance['doimailing'] : '';
		$permission = ! empty( $instance['permission'] ) ? $instance['permission'] : '1';
		$sync_mode  = ! empty( $instance['sync_mode'] ) ? $instance['sync_mode'] : '2';
		$template   = ! empty( $instance['template'] ) ? $instance['template'] : '';
		?>

		<h2><?php esc_html_e( 'Form Settings', 'xq_maileon' ); ?></h2>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'xq_maileon' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'subscribe' ) ); ?>"><?php esc_html_e( 'Subscribe button text:', 'xq_maileon' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subscribe' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subscribe' ) ); ?>" type="text" value="<?php echo esc_attr( $subscribe ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>"><?php esc_html_e( 'Template:', 'xq_maileon' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'template' ) ); ?>" type="text" value="<?php echo esc_attr( $template ); ?>">
		</p>

		<hr />
		<h2><?php esc_html_e( 'API Settings', 'xq_maileon' ); ?></h2>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'doi' ) ); ?>"><?php esc_html_e( 'Use DOI process:', 'xq_maileon' ); ?></label>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'doi' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'doi' ) ); ?>" value="true" <?php checked( $doi, 'true' ); ?>>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'doiplus' ) ); ?>"><?php esc_html_e( 'Requested DOI+ permission:', 'xq_maileon' ); ?></label>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'doiplus' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'doiplus' ) ); ?>" value="true" <?php checked( $doiplus, 'true' ); ?>>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'permission' ) ); ?>"><?php esc_html_e( 'Initial Permission (before accepting DOI):', 'xq_maileon' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'permission' ) ); ?>">
				<option type="radio" id="xq_permission_1" value="1" <?php selected( $permission, '1' ); ?>><?php esc_html_e( 'None (standard)', 'xq_maileon' ); ?></option><br>
				<option type="radio" id="xq_permission_2" value="2" <?php selected( $permission, '2' ); ?>><?php esc_html_e( 'SOI', 'xq_maileon' ); ?></option><br>
				<option type="radio" id="xq_permission_3" value="3" <?php selected( $permission, '3' ); ?>><?php esc_html_e( 'COI', 'xq_maileon' ); ?></option><br>
				<option type="radio" id="xq_permission_4" value="4" <?php selected( $permission, '4' ); ?>><?php esc_html_e( 'DOI', 'xq_maileon' ); ?></option><br>
				<option type="radio" id="xq_permission_5" value="5" <?php selected( $permission, '5' ); ?>><?php esc_html_e( 'DOI+', 'xq_maileon' ); ?></option><br>
				<option type="radio" id="xq_permission_6" value="6" <?php selected( $permission, '6' ); ?>><?php esc_html_e( 'Other', 'xq_maileon' ); ?></option><br>
			</select >
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'sync_mode' ) ); ?>"><?php esc_html_e( 'Sync Mode:', 'xq_maileon' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'sync_mode' ) ); ?>">
				<option type="radio" id="xq_sync_mode_1" value="1" <?php selected( $sync_mode, '1' ); ?>><?php esc_html_e( 'Ignore', 'xq_maileon' ); ?></option><br>
				<option type="radio" id="xq_sync_mode_2" value="2" <?php selected( $sync_mode, '2' ); ?>><?php esc_html_e( 'Update', 'xq_maileon' ); ?></option><br>
			</select >
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'doimailing' ) ); ?>"><?php esc_html_e( 'DOI Mailing Key:', 'xq_maileon' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'doimailing' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'doimailing' ) ); ?>" type="text" value="<?php echo esc_attr( $doimailing ); ?>">
		</p>

		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array(
			'title'      => ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '',
			'subscribe'  => ( ! empty( $new_instance['subscribe'] ) ) ? wp_strip_all_tags( $new_instance['subscribe'] ) : '',
			'doi'        => ( ! empty( $new_instance['doi'] ) ) ? $new_instance['doi'] : 'false',
			'doiplus'    => ( ! empty( $new_instance['doiplus'] ) ) ? $new_instance['doiplus'] : 'false',
			'doimailing' => ( ! empty( $new_instance['doimailing'] ) ) ? wp_strip_all_tags( $new_instance['doimailing'] ) : '',
			'permission' => ( ! empty( $new_instance['permission'] ) ) ? $new_instance['permission'] : '1',
			'sync_mode'  => ( ! empty( $new_instance['sync_mode'] ) ) ? $new_instance['sync_mode'] : '2',
			'template'   => ( ! empty( $new_instance['template'] ) ) ? $new_instance['template'] : '',
		);

		return $instance;
	}
}
