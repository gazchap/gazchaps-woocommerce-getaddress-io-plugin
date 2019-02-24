<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin {

		public function __construct() {
			$this->init_settings();
			$this->init_checkout_fields();
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
				'label' => __( 'Find Address', 'gazchaps-woocommerce-getaddress-io-plugin' ),
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

			ob_start();
			?>
			<p class="form-row <?php echo $class; ?>" id="<?php echo $id; ?>" data-priority="<?php echo esc_attr( $priority ); ?>">
				<br>
				<button type="button" class="button alt" id="<?php echo $id;?>_button"><?php echo esc_html( $args['label'] ); ?></button>
			</p>
			<?php
			return ob_get_clean();
		}

		public function init_settings() {
			add_filter( 'woocommerce_get_settings_general', array( $this, 'add_settings_to_section' ), 10, 1 );
		}

		public function add_settings_to_section( $settings ) {
			$new_settings = array();

			$new_settings[] = array(
				'id'       => 'gazchaps_getaddress_io_section_title',
				'title' => __( 'getAddress.io Settings', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'desc' => __( 'Settings required for GazChap\'s WooCommerce getAddress.io Plugin. Get your API key from https://getaddress.io', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'type'     => 'title',
			);

			$new_settings[] = array(
				'id'       => 'gazchaps_getaddress_io_enabled',
				'title'     => __( 'Enabled', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'desc' => __( 'Activate the integration (requires an API key to be entered below)', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'type'     => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_api_key',
				'title'      => __( 'API Key', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_enable_for_billing_address',
				'title'      => __( 'Enable for Billing Address', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'desc'      => __( 'Add the lookup field to the Billing Address section in the checkout', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_enable_for_shipping_address',
				'title'      => __( 'Enable for Shipping Address', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'desc'      => __( 'Add the lookup field to the shipping Address section in the checkout', 'gazchaps-woocommerce-getaddress-io-plugin' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchap_getaddress_io_options',
				'type'      => 'sectionend',
			);

			$settings = array_merge( $settings, $new_settings );

			return $settings;
		}

	}

	new GazChap_WC_GetAddress_Plugin();