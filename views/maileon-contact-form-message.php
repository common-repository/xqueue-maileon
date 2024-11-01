<?php
/**
 * Tempate part for a message in the contact form
 *
 * @package xqueue-maileon
 * @version 2.16.0
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

?>
<div class="et-pb-contact-message">
	<?php
	if ( empty( $id ) ) {
		echo esc_html( $msg );
	} else {
		printf(
			'<div id="%s">%s</div>',
			esc_attr( $id ),
			esc_html( $msg )
		);
	}
	?>
</div>
