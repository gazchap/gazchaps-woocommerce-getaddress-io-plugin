<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Checkout {

		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );
			$this->init_checkout_fields();

			add_action( 'wp_ajax_gazchaps_woocommerce_getaddress_io', array( $this, 'do_postcode_lookup' ) );
			add_action( 'wp_ajax_nopriv_gazchaps_woocommerce_getaddress_io', array( $this, 'do_postcode_lookup' ) );
		}

		public function do_postcode_lookup() {
			if ( empty( $_POST['address_type'] ) ) {
				$_POST['address_type'] = 'billing';
			}
			$result = GazChap_WC_GetAddress_Plugin_Common::lookup_postcode( $_POST['postcode'], $_POST['address_type'] );
			if ( !is_wp_error( $result ) ) {
				$fragment = $this->get_address_selector_html( $result['addresses'], $result['address_type'] );
				$output = array(
					'postcode' => $result['postcode'],
					'address_count' => $result['address_count'],
					'address_type' => $result['address_type'],
					'fragment' => $fragment,
				);
			} else {
				/**
				 * @var WP_Error $result
				 */
				$output = array(
					'error_code' => $result->get_error_code(),
					'error' => $result->get_error_message(),
				);
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
			// only enqueue on checkout or account pages
			if ( is_checkout() || is_account_page() || is_edit_account_page() ) {
				wp_register_script( 'gazchaps_getaddress_io', GC_WC_GAIO_URL . 'gazchaps-getaddress-io.min.js', array( 'jquery' ), GazChap_WC_GetAddress_Plugin_Common::PLUGIN_VERSION, true );
				wp_enqueue_script( 'gazchaps_getaddress_io' );

				wp_localize_script( 'gazchaps_getaddress_io', 'gazchaps_getaddress_io', GazChap_WC_GetAddress_Plugin_Common::get_localize_js_options() );
			}
		}

		public function init_checkout_fields() {
			if ( 'yes' == get_option( 'gazchaps_getaddress_io_enabled' ) && !empty( get_option( 'gazchaps_getaddress_io_api_key' ) ) ) {
				if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_billing_address' ) || 'no' != get_option( 'gazchaps_getaddress_io_enable_for_shipping_address' ) ) {
					add_filter( 'woocommerce_get_country_locale_default', array( $this, 'modify_country_locale_default' ), 10, 1 );
					add_filter( 'woocommerce_get_country_locale', array( $this, 'modify_country_locale' ), 10, 2 );
					add_filter( 'woocommerce_country_locale_field_selectors', array( $this, 'modify_country_locale_field_selectors' ), 10, 1 );

					add_filter( 'woocommerce_form_field_gazchaps_getaddress_io_postcode_lookup_button', array( $this, 'render_postcode_lookup_button' ), 10, 4 );
					add_filter( 'woocommerce_form_field_gazchaps_getaddress_io_enter_address_manually_button', array( $this, 'render_enter_address_manually_button' ), 10, 4 );
				}

				add_filter( 'woocommerce_default_address_fields', array( $this, 'modify_default_fields' ), 10 );

				if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_billing_address' ) ) {
					add_filter( 'woocommerce_billing_fields', array( $this, 'modify_billing_fields' ), 10 );
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

		public function modify_default_fields( $fields ) {
			return $this->modify_fields( $fields, '' );
		}

		public function modify_billing_fields( $fields ) {
			return $this->modify_fields( $fields, 'billing' );
		}

		public function modify_shipping_fields( $fields ) {
			return $this->modify_fields( $fields, 'shipping' );
		}

		private function modify_fields( $fields, $type = 'billing' ) {
			if ( !empty( $type ) ) {
				$type .= '_';
			}

			// move postcode to after country
			$country_priority = $fields[ $type . 'country']['priority'];
			$fields[ $type . 'postcode']['priority'] = $country_priority + 5;

			// change postcode so it's a form-row-first jobber
			if ( !empty( $fields[ $type . 'postcode']['class'] ) ) {
				if ( !is_array( $fields[ $type . 'postcode']['class'] ) ) {
					$fields[ $type . 'postcode']['class'] = array( $fields[ $type . 'postcode']['class'] );
				}
			} else {
				$fields[ $type . 'postcode']['class'] = array();
			}
			$fields[ $type . 'postcode']['class'][] = 'form-row-first';

			// remove form-row-wide if it's in there
			if ( false !== ( $wide_key = array_search( 'form-row-wide', $fields[ $type . 'postcode']['class'] ) ) ) {
				unset( $fields[ $type . 'postcode']['class'][ $wide_key ] );
				$fields[ $type . 'postcode']['class'] = array_values( $fields[ $type . 'postcode']['class'] );
			}

			// add postcode lookup button
			$fields[ $type . 'gazchaps_getaddress_io_postcode_lookup_button'] = array(
				'type' => 'gazchaps_getaddress_io_postcode_lookup_button',
				'label' => GazChap_WC_GetAddress_Plugin_Common::get_find_button_text(),
				'class' => array(
					'form-row-last',
				),
				'priority' => $country_priority + 7,
			);

			// add the "enter address manually" button
			if ( 'yes' == get_option( 'gazchaps_getaddress_io_hide_address_fields' ) && 'no' != get_option( 'gazchaps_getaddress_io_allow_manual_entry' ) ) {
				$fields[ $type . 'gazchaps_getaddress_io_enter_address_manually_button'] = array(
					'type' => 'gazchaps_getaddress_io_enter_address_manually_button',
					'label' => GazChap_WC_GetAddress_Plugin_Common::get_enter_address_manually_text(),
					'class' => array(
						'form-row-wide',
					),
					'priority' => $country_priority + 8,
				);
			}

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

		public function render_enter_address_manually_button( $field, $key, $args, $value ) {
			$priority = ( !empty( $args['priority'] ) ) ? $args['priority'] : '';
			$class = ( !empty( $args['class'] ) ) ? esc_attr( implode( ' ', $args['class'] ) ) : '';
			$id    = ( !empty( $args['id'] ) ) ? esc_attr( $args['id'] ) . '_field' : '';

			// note: this render code is in a <script> tag so that it does not appear if JS is disabled for any reason
			ob_start();
			?>
			<script>
				document.write( '<p class="form-row <?php echo $class; ?>" id="<?php echo $id; ?>" data-priority="<?php echo esc_attr( $priority ); ?>"><br>' );
				document.write( '<button type="button" class="button gazchaps-getaddress-io-enter-address-manually-button" id="<?php echo $id;?>_button"><?php echo esc_html( $args['label'] ); ?></button></p>' );
			</script>
			<?php
			return ob_get_clean();
		}

	}

	new GazChap_WC_GetAddress_Plugin_Checkout();
