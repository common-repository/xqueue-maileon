<?php
/**
 * This file contains the AddressCheck functionality
 *
 * @package xqueue-maileon
 * @author  XQueue GmbH <integrations@xqueue.com>
 */

// Require the autoload file from composer.
require_once __DIR__ . '/../vendor/autoload.php';

use XQueue\AddressCheck\API\Address\AddressService;

/**
 * The AddressCheck functionality class
 */
class XQ_Adc {
	/**
	 * Initialize the autoloader and the maileon static classes
	 */
	public static function init() {
		// Check if the call is a call for xq_adc_ajax.
		if ( filter_has_var( INPUT_GET, 'xq_adc_ajax' ) ) {
			header( 'Content-Type: application/json' );

			// If configured, allow call only from same host.
			if ( 'false' === get_option( 'adc_only_from_localhost' ) || self::requested_by_the_same_domain() ) {
				$adc_user    = get_option( 'adc_api_user' );
				$adc_key     = get_option( 'adc_api_key' );
				$adc_enabled = get_option( 'adc_enabled' );

				if ( ! empty( $adc_enabled ) && 'true' === $adc_enabled ) {
					if ( ! empty( $adc_key ) && ! empty( $adc_user ) ) {
						// If ADC is configured, run check.

						// Get address to check or abort if not set.
						if ( filter_has_var( INPUT_GET, 'email' ) ) {
							$email = filter_input( INPUT_GET, 'email', FILTER_SANITIZE_EMAIL );
						} else {
							return wp_send_json(
								array(
									'error' => 'E-Mail address required',
								)
							);
						}

						$allow_disposable_addresses = htmlspecialchars( filter_input( INPUT_GET, 'allow_disposable_addresses' ) );

						return self::process_request( $adc_user, $adc_key, $email, $allow_disposable_addresses );
					} else {
						// Else return error.
						return wp_send_json(
							array(
								'error' => 'ADC user or key not configured.',
							),
							401
						);
					}
				} else {
					// Else return error.
					return wp_send_json(
						array(
							'error' => 'ADC not activated, resource not available.',
						),
						404
					);
				}
			} else {
				// Return error: call is only allowed from same server but came from different server.
				return wp_send_json(
					array(
						'error' => 'Calls are only allowed from same server but came from different server.',
					),
					403
				);
			}
		}
	}

	/**
	 * Check if the request is coming from the same domain
	 *
	 * @return boolean
	 */
	private static function requested_by_the_same_domain() {
		if ( filter_has_var( INPUT_SERVER, 'HTTP_REFERER' ) ) {
			return ( strtolower( wp_parse_url( filter_input( INPUT_SERVER, 'HTTP_REFERER' ), PHP_URL_HOST ) ) === strtolower( filter_input( INPUT_SERVER, 'HTTP_HOST' ) ) );
		}
		return false;
	}

	/**
	 * Process an AddressCheck request
	 *
	 * @param string  $adc_user The AddressCheck API user.
	 * @param string  $adc_key The AddressCheck API key.
	 * @param string  $email The email address to check.
	 * @param boolean $allow_disposable_addresses Whether disposable addresses allowed.
	 * @return string
	 */
	private static function process_request( $adc_user, $adc_key, $email, $allow_disposable_addresses ) {
		// The configuration.
		$address_check_config = array(
			'BASE_URI' => 'https://adc.maileon.com/svc/2.0/',
			'USERNAME' => $adc_user,
			'PASSWORD' => $adc_key,
			'TIMEOUT'  => 60,
			'DEBUG'    => false,
		);

		$address_service          = new AddressService( $address_check_config );
		$quality_check_request    = $address_service->fastQualityCheck( $email );
		$disposable_check_request = $address_service->disposableAddressCheck( $email );

		if ( $quality_check_request->isSuccess() && $disposable_check_request->isSuccess() ) {
			$quality_status_result    = $quality_check_request->getResult();
			$disposable_status_result = $disposable_check_request->getResult();

			if ( 1 === $quality_status_result['syntax'] && // Syntax is valid.
				1 === $quality_status_result['domain'] && // Domain exists.
				1 === $quality_status_result['mailserver'] && // SMTP at domain exists.
				0 !== $quality_status_result['address'] && // Address not invalidated by SMTP request.
				( ! $allow_disposable_addresses || 1 !== $disposable_status_result['result'] ) // Not a disposable address.
			) {
				// everything is OK.
				return wp_send_json(
					array(
						'address' => $quality_status_result['address'],
					)
				);
			} else {
				$message = '';
				if ( 1 !== $quality_status_result['syntax'] ) {
					$message .= "Syntax of address '$email' is invalid. ";
				}
				if ( 1 !== $quality_status_result['domain'] ) {
					$message .= "Domain of address '$email' does not exist. ";
				}
				if ( 1 !== $quality_status_result['mailserver'] ) {
					$message .= "Mailserver of address '$email' does not exist. ";
				}
				if ( 1 !== $quality_status_result['address'] ) {
					$message .= "Mailbox of address '$email' does not exist. ";
				}

				return wp_send_json(
					array(
						'address' => $quality_status_result['address'],
						'error'   => $message,
					)
				);
			}
		} else {
			// Communication or setup error.
			return wp_send_json(
				array(
					'error' => 'Problem communicating with ADC server: Quality-Check-Status=' . $quality_check_request->getStatusCode() . ' and Disposable-Address-Check-Status=' . $disposable_check_request->getStatusCode(),
				)
			);
		}
	}
}
