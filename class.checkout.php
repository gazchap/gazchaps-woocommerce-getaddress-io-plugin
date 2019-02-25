<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin {

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );
			$this->init_checkout_fields();

			add_action( 'wp_ajax_gazchaps_woocommerce_getaddress_io', array( $this, 'do_postcode_lookup' ) );
			add_action( 'wp_ajax_nopriv_gazchaps_woocommerce_getaddress_io', array( $this, 'do_postcode_lookup' ) );
		}

		public function do_postcode_lookup() {
			$output = array();

			if ( !empty( $_POST['postcode'] ) ) {
				// sanitize postcode
				$postcode = strtoupper( preg_replace("/[^A-Z0-9]/i", "", $_POST['postcode'] ) );

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
							$address_type = 'billing';
							if ( 'shipping' == $_POST['address_type'] ) $address_type = 'shipping';
							$addresses = array();
							$array = json_decode( $result['body'] );
							foreach( $array->addresses as $address ) {
								if ( empty( $address->line_2 ) && !empty( $address->locality ) ) {
									$address->line_2 = $address->locality;
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

								$addresses[] = $this_address;
							}

							$fragment = $this->get_address_selector_html( $addresses, $address_type );
							$output = array(
								'address_count' => count( $addresses ),
								'address_type' => $address_type,
								'fragment' => $fragment,
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

							$this->send_overusage_email();
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
			}
			wp_die( json_encode( $output ) );
		}

		public function get_address_selector_html( $addresses, $address_type ) {
			$p_id = $address_type . '_gazchaps-woocommerce-getaddress-io-address-selector';
			$p_class = apply_filters( 'gazchaps-woocommerce-getaddress-io_' . $address_type . '_selector_row_class', 'form-row form-row-wide' );
			$select_id = $address_type . '_gazchaps-woocommerce-getaddress-io-address-selector-select';

			$html = '<p class="' . esc_attr( $p_class ) . '" id="' . esc_attr( $p_id ) . '">';
			$html.= '<label for="' . esc_attr( $address_type ) . '_gazchaps-woocommerce-getaddress-io-address-selector-select">' . __( 'Select Address', 'gazchaps-woocommerce-getaddress-io' ) . '</label>';
			$html.= '<span class="woocommerce-input-wrapper"><select id="' . esc_attr( $select_id ) . '">';
			$html.= '<option value="">' . esc_html( sprintf( _n( '%s address found', '%s addresses found', count( $addresses ), 'gazchaps-woocommerce-getaddress-io' ), number_format_i18n( count( $addresses ) ) ) ) . '</option>';

			foreach( $addresses as $address ) {
				$html.= '<option value="' . esc_attr( $address['option'] ) . '">' . esc_html( $address['label'] ) . '</option>';
			}
			$html.= '</select></span>';
			$html.= '</p>';
			return $html;
		}

		public function enqueue_js() {
			wp_register_script( 'gazchaps_getaddress_io', GC_WC_GAIO_URL . 'gazchaps-getaddress-io.min.js', array( 'jquery' ), '1.1', true );
			wp_enqueue_script( 'gazchaps_getaddress_io' );

			$options = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'clear_additional_fields' => apply_filters( 'gazchaps-woocommerce-getaddress-io_clear_additional_fields', true ),
			);
			wp_localize_script( 'gazchaps_getaddress_io', 'gazchaps_getaddress_io', $options );
		}

		public function send_overusage_email() {
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

		public function init_checkout_fields() {
			if ( 'yes' == get_option( 'gazchaps_getaddress_io_enabled' ) && !empty( get_option( 'gazchaps_getaddress_io_api_key' ) ) ) {
				if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_billing_address' ) || 'no' != get_option( 'gazchaps_getaddress_io_enable_for_shipping_address' ) ) {
					add_filter( 'woocommerce_get_country_locale_default', array( $this, 'modify_country_locale_default' ), 10, 1 );
					add_filter( 'woocommerce_get_country_locale', array( $this, 'modify_country_locale' ), 10, 2 );
					add_filter( 'woocommerce_country_locale_field_selectors', array( $this, 'modify_country_locale_field_selectors' ), 10, 1 );
					add_filter( 'woocommerce_form_field_gazchaps_getaddress_io_postcode_lookup_button', array( $this, 'render_postcode_lookup_button' ), 10, 4 );
				}

				if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_billing_address' ) ) {
					add_filter( 'woocommerce_billing_fields', array( $this, 'modify_billing_fields' ) );
				}

				if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_shipping_address' ) ) {
					add_filter( 'woocommerce_shipping_fields', array( $this, 'modify_shipping_fields' ), 10, 1 );
				}
			}
		}

		public function modify_country_locale_default( $locale ) {
			$locale['gazchaps_getaddress_io_postcode_lookup_button']['hidden'] = true;
			return $locale;
		}

		public function modify_country_locale( $locale ) {
			if ( isset( $locale['GB'] ) ) {
				// move postcode field to above country field
				$locale['GB']['postcode']['priority'] = 45;
				$locale['GB']['gazchaps_getaddress_io_postcode_lookup_button']['priority'] = 46;
				$locale['GB']['gazchaps_getaddress_io_postcode_lookup_button']['hidden'] = false;
			}
			foreach( $locale as $key => $array ) {
				if ( 'GB' != $key ) {
					$locale[ $key ]['gazchaps_getaddress_io_postcode_lookup_button']['hidden'] = true;
				}
			}
			return $locale;
		}

		public function modify_country_locale_field_selectors( $locale_fields ) {
			$locale_fields['gazchaps_getaddress_io_postcode_lookup_button'] = "#billing_gazchaps_getaddress_io_postcode_lookup_button_field, #shipping_gazchaps_getaddress_io_postcode_lookup_button_field";
			return $locale_fields;
		}

		public function modify_billing_fields( $fields ) {
			return $this->modify_fields( $fields, 'billing' );
		}

		public function modify_shipping_fields( $fields ) {
			return $this->modify_fields( $fields, 'shipping' );
		}

		private function modify_fields( $fields, $type = 'billing' ) {
			// move postcode to after country
			$country_priority = $fields[ $type . '_country']['priority'];
			$fields[ $type . '_postcode']['priority'] = $country_priority + 5;

			// change postcode so it's a form-row-first jobber
			if ( !empty( $fields[ $type . '_postcode']['class'] ) ) {
				if ( !is_array( $fields[ $type . '_postcode']['class'] ) ) {
					$fields[ $type . '_postcode']['class'] = array( $fields[ $type . '_postcode']['class'] );
				}
			} else {
				$fields[ $type . '_postcode']['class'] = array();
			}
			$fields[ $type . '_postcode']['class'][] = 'form-row-first';

			// remove form-row-wide if it's in there
			if ( false !== ( $wide_key = array_search( 'form-row-wide', $fields[ $type . '_postcode']['class'] ) ) ) {
				unset( $fields[ $type . '_postcode']['class'][ $wide_key ] );
				$fields[ $type . '_postcode']['class'] = array_values( $fields[ $type . '_postcode']['class'] );
			}

			// add postcode lookup button
			$fields[ $type . '_gazchaps_getaddress_io_postcode_lookup_button'] = array(
				'type' => 'gazchaps_getaddress_io_postcode_lookup_button',
				'label' => __( 'Find Address', 'gazchaps-woocommerce-getaddress-io' ),
				'class' => array(
					'form-row-last',
				),
				'priority' => $country_priority + 7,
			);

			return $fields;
		}

		public function render_postcode_lookup_button( $field, $key, $args, $value ) {
			$priority = ( !empty( $args['priority'] ) ) ? $args['priority'] : '';
			$class = ( !empty( $args['class'] ) ) ? esc_attr( implode( ' ', $args['class'] ) ) : '';
			$id    = ( !empty( $args['id'] ) ) ? esc_attr( $args['id'] ) . '_field' : '';

			// note: this render code is in a <script> tag so that it does not appear if JS is disabled for any reason
			ob_start();
			?>
			<script>
				document.write( '<p class="form-row <?php echo $class; ?>" id="<?php echo $id; ?>" data-priority="<?php echo esc_attr( $priority ); ?>"><br>' );
				document.write( '<button type="button" class="button alt gazchaps-getaddress-io-lookup-button" id="<?php echo $id;?>_button"><?php echo esc_html( $args['label'] ); ?></button></p>' );
			</script>
			<?php
			return ob_get_clean();
		}

	}

	new GazChap_WC_GetAddress_Plugin();