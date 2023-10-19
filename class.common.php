<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Common {
		const PLUGIN_VERSION = '3.1';
		const DONATE_URL = 'https://ko-fi.com/gazchap';

		public static function lookup_postcode( $postcode, $address_type = 'billing' ) {
			$output = array();

			if ( !empty( $postcode ) ) {
				// sanitize postcode
				$postcode = strtoupper( preg_replace("/[^A-Z0-9]/i", "", $postcode ) );

				if ( !empty( $postcode ) ) {
					try {
						$results = null;
						if ( GazChap_WC_GetAddress_Plugin_Database::enabled() ) {
							$results = GazChap_WC_GetAddress_Plugin_Database::lookup( $postcode );
						}
						if ( empty( $results ) ) {
							$results = GazChap_WC_GetAddress_Plugin_API::autocomplete( rawurlencode( $postcode ) );
						}

						$address_type = ( 'shipping' == $address_type ) ? 'shipping' : 'billing';

						$addresses = array();
						foreach( $results->suggestions as $address ) {
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
							$this_address['label'] = str_replace("|", ", ", preg_replace( "/\|+/", "|", trim( $this_address['option'], '|' ) ) );
							$this_address['address'] = (array) $address;

							$addresses[] = $this_address;
						}

						$formatted_postcode = strtoupper( substr( $postcode, 0, -3 ) . ' ' . substr( $postcode, -3 ) );
						$output = array(
							'postcode' => $formatted_postcode,
							'address_count' => count( $addresses ),
							'address_type' => $address_type,
							'addresses' => $addresses,
						);
					} catch ( GazChap_WC_GetAddress_Plugin_Exception $exception ) {
						$output = array(
							'error_code' => $exception->getCode()
						);
						switch( $exception->getCode() ) {
							case 401:
							case 429:
								$output['error'] = __('The postcode lookup failed. Please try again later.', 'gazchaps-woocommerce-getaddress-io' );
								break;

							case 404:
								$output['error'] = __('No addresses were found for this postcode.', 'gazchaps-woocommerce-getaddress-io' );
								break;

							case 500:
								$output['error'] = __('Server error. Please try again later.', 'gazchaps-woocommerce-getaddress-io' );
								break;
						}
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
				'button_text' => GazChap_WC_GetAddress_Plugin_Settings::get_find_button_text(),
				'searching_text' => GazChap_WC_GetAddress_Plugin_Settings::get_searching_text(),
				'enter_address_manually_text' => GazChap_WC_GetAddress_Plugin_Settings::get_enter_address_manually_text(),
				'hide_address_fields' => $hide_address_fields,
				'fields_to_hide' => apply_filters( 'gazchaps-woocommerce-getaddress-io_hide-fields', [ 'address_1', 'address_2', 'city', 'state' ] ),
				'field_prefixes' => $field_prefixes,
			);
		}
	}

