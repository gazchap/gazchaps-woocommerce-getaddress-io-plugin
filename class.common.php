<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Common {
		const PLUGIN_VERSION = '2.0';
		const DONATE_URL = 'https://ko-fi.com/gazchap';

		public static function lookup_postcode( $postcode, $address_type = 'billing' ) {
			$output = array();

			if ( !empty( $postcode ) ) {
				// sanitize postcode
				$postcode = strtoupper( preg_replace("/[^A-Z0-9]/i", "", $postcode ) );

				if ( !empty( $postcode ) ) {
					$url = "https://api.getaddress.io/find/" . rawurlencode( $postcode ) . "?sort=true&expand=true";
					$auth = base64_encode( "api-key:" . get_option( 'gazchaps_getaddress_io_api_key' ) );

					$args = array(
						'headers' => array(
							'Authorization' => 'Basic ' . $auth
						)
					);
					$result = wp_remote_request( $url, $args );

					switch( intval( $result['response']['code'] ) ) {
						case 200:
							$address_type = ( 'shipping' == $address_type ) ? 'shipping' : 'billing';

							$addresses = array();
							$array = json_decode( $result['body'] );
							foreach( $array->addresses as $address ) {
								if ( empty( $address->line_2 ) && !empty( $address->locality ) ) {
									$address->line_2 = $address->locality;
									unset( $address->locality );
								}
								$this_address = array();
								$address_lines = array(
									$address_type . '_address_1' => $address->line_1,
									$address_type . '_address_2' => $address->line_2,
									$address_type . '_city' => $address->town_or_city,
									$address_type . '_state' => $address->county,
								);
								$this_address['option'] = implode( "|", array_values( $address_lines ) );
								$this_address['label'] = str_replace("|", ", ", preg_replace( "/\|+/", "|", $this_address['option'] ) );
								$this_address['address'] = (array) $address;

								$addresses[] = $this_address;
							}

							$output = array(
								'postcode' => $array->postcode ?: $postcode,
								'address_count' => count( $addresses ),
								'address_type' => $address_type,
								'addresses' => $addresses,
							);
							break;

						case 400:
							$output = array(
								'error' =>__('The postcode supplied is invalid', 'gazchaps-woocommerce-getaddress-io' ),
								'error_code' => $result['response']['code'],
							);
							break;

						case 401:
							$output = array(
								'error' =>__('The postcode lookup failed. Please try again later.', 'gazchaps-woocommerce-getaddress-io' ),
								'error_code' => $result['response']['code'],
							);
							break;

						case 404:
							$output = array(
								'error' =>__('No addresses were found for this postcode.', 'gazchaps-woocommerce-getaddress-io' ),
								'error_code' => $result['response']['code'],
							);
							break;

						case 429:
							$output = array(
								'error' =>__('The postcode lookup failed. Please try again later.', 'gazchaps-woocommerce-getaddress-io' ),
								'error_code' => $result['response']['code'],
							);

							GazChap_WC_GetAddress_Plugin_Common::send_overusage_email();
							break;

						case 500:
							$output = array(
								'error' =>__('Server error. Please try again later.', 'gazchaps-woocommerce-getaddress-io' ),
								'error_code' => $result['response']['code'],
							);
							break;
					}
				}
			}
			if ( empty( $output ) ) {
				$output = array(
					'error' =>__('No postcode was supplied.', 'gazchaps-woocommerce-getaddress-io' ),
					'error_code' => 400,
				);
			}
			if ( !empty( $output['error_code'] ) ) {
				$output['error'] = apply_filters( 'gazchaps-woocommerce-getaddress-io_api_error_' . $output['error_code'], $output['error'] );

				return new WP_Error( $output['error_code'], $output['error'], $output );
			}

			return $output;
		}

		/**
		 * @return string
		 */
		public static function get_find_button_text() {
			$text = __( 'Find Address', 'gazchaps-woocommerce-getaddress-io' );
			if ( !empty( get_option( 'gazchaps_getaddress_io_find_address_button_text' ) ) ) {
				$text = get_option( 'gazchaps_getaddress_io_find_address_button_text' );
			}

			return apply_filters( 'gazchaps-woocommerce-getaddress-io_find-address-button-text', $text );
		}

		/**
		 * @return string
		 */
		public static function get_searching_text() {
			$text = __( 'Searching...', 'gazchaps-woocommerce-getaddress-io' );
			if ( !empty( get_option( 'gazchaps_getaddress_io_find_address_searching_text' ) ) ) {
				$text = get_option( 'gazchaps_getaddress_io_find_address_searching_text' );
			}

			return apply_filters( 'gazchaps-woocommerce-getaddress-io_find-address-searching-text', $text );
		}

		/**
		 * @return string
		 */
		public static function get_enter_address_manually_text() {
			$text = __( 'Enter an address manually', 'gazchaps-woocommerce-getaddress-io' );
			if ( !empty( get_option( 'gazchaps_getaddress_io_enter_address_manually_text' ) ) ) {
				$text = get_option( 'gazchaps_getaddress_io_enter_address_manually_text' );
			}

			return apply_filters( 'gazchaps-woocommerce-getaddress-io_enter-address-manually-text', $text );
		}

		public static function get_localize_js_options() {
			$field_prefixes = [];
			if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_billing_address' ) ) {
				$field_prefixes[] = 'billing';
			}
			if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_shipping_address' ) ) {
				$field_prefixes[] = 'shipping';
			}

			$hide_address_fields = ( 'yes' == get_option( 'gazchaps_getaddress_io_hide_address_fields' ) );
			return array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'clear_additional_fields' => apply_filters( 'gazchaps-woocommerce-getaddress-io_clear_additional_fields', true ),
				'button_text' => self::get_find_button_text(),
				'searching_text' => self::get_searching_text(),
				'enter_address_manually_text' => self::get_enter_address_manually_text(),
				'hide_address_fields' => $hide_address_fields,
				'fields_to_hide' => apply_filters( 'gazchaps-woocommerce-getaddress-io_hide-fields', [ 'address_1', 'address_2', 'city', 'state' ] ),
				'field_prefixes' => $field_prefixes,
			);
		}

		/**
		 * Sends the overusage email to the designated recipient
		 *
		 * @return void
		 */
		public static function send_overusage_email() {
			$recipient = trim( get_option( 'gazchaps_getaddress_io_email_when_usage_limit_hit' ) );
			if ( !empty( $recipient ) ) {
				if ( '{admin_email}' == strtolower( $recipient ) ) {
					$recipient = trim( get_bloginfo('admin_email') );
				}

				if ( !empty( $recipient ) && is_email( $recipient ) ) {
					$last_email_sent = get_option( 'gazchaps_getaddress_io_email_when_usage_limit_hit_lastsent' );

					if ( !$last_email_sent || $last_email_sent < strtotime("-24 hours") ) {
						$subject = __( "getAddress.io API Usage Limit Reached", 'gazchaps-woocommerce-getaddress-io' );
						$message = "";
						$message .= sprintf( __( "Sent from: %s", 'gazchaps-woocommerce-getaddress-io' ), home_url() ) . "\r\n";
						$message .= sprintf( __( "getAddress.io API Key: %s", 'gazchaps-woocommerce-getaddress-io' ), get_option( 'gazchaps_getaddress_io_api_key' ) ) . "\r\n";
						$message .= sprintf( __( "Date/Time: %s", 'gazchaps-woocommerce-getaddress-io' ), current_time( 'mysql' ) ) . "\r\n";

						$recipient = apply_filters( 'gazchaps-woocommerce-getaddress-io_overusage_email_recipient', $recipient );
						$subject = apply_filters( 'gazchaps-woocommerce-getaddress-io_overusage_email_subject', $subject );
						$message = apply_filters( 'gazchaps-woocommerce-getaddress-io_overusage_email_message', $message );

						wp_mail( $recipient, $subject, $message );
						update_option( 'gazchaps_getaddress_io_email_when_usage_limit_hit_lastsent', time() );
					}
				}
			}
		}

	}

