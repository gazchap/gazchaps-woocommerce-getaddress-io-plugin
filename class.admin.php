<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Admin {

		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ) );
			$this->init_fields();

			add_action( 'wp_ajax_gazchaps_woocommerce_getaddress_io_wp_admin', array( $this, 'do_postcode_lookup' ) );
		}

		public function do_postcode_lookup() {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				if ( empty( $_POST['address_type'] ) ) {
					$_POST['address_type'] = 'billing';
				}
				$result = GazChap_WC_GetAddress_Plugin_Common::lookup_postcode( $_POST['postcode'], $_POST['address_type'] );
				if ( !is_wp_error( $result ) ) {
					$output = $result;
					$output['select_placeholder'] = esc_html( sprintf( _n( '%s address found', '%s addresses found', count( $result['addresses'] ), 'gazchaps-woocommerce-getaddress-io' ), number_format_i18n( count( $result['addresses'] ) ) ) );
				} else {
					/**
					 * @var WP_Error $result
					 */
					$output = array(
						'error_code' => $result->get_error_code(),
						'error' => $result->get_error_message(),
					);
				}
			} else {
				$output = array(
					'error' =>__('The postcode lookup failed.', 'gazchaps-woocommerce-getaddress-io' ),
					'error_code' => 401,
				);
			}
			wp_die( json_encode( $output ) );
		}

		public function init_fields() {
			if ( 'yes' == get_option( 'gazchaps_getaddress_io_enabled' ) && !empty( get_option( 'gazchaps_getaddress_io_api_key' ) ) ) {
				if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_wc_admin' ) ) {
					if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_billing_address' ) ) {
						add_filter( 'woocommerce_admin_billing_fields', array( $this, 'modify_fields' ), 10 );
					}

					if ( 'no' != get_option( 'gazchaps_getaddress_io_enable_for_shipping_address' ) ) {
						add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'modify_fields' ), 10 );
					}
				}
			}
		}

		public function enqueue_js() {
			wp_register_script( 'gazchaps_getaddress_io', GC_WC_GAIO_URL . 'gazchaps-getaddress-io-admin.min.js', array( 'jquery' ), GazChap_WC_GetAddress_Plugin_Common::PLUGIN_VERSION, true );
			wp_enqueue_script( 'gazchaps_getaddress_io' );
			wp_localize_script( 'gazchaps_getaddress_io', 'gazchaps_getaddress_io', GazChap_WC_GetAddress_Plugin_Common::get_localize_js_options() );
		}


		public function modify_fields( $fields ) {
			// if we're in the middle of a POST request, DON'T modify the fields array otherwise
			// WC saves getaddress.io metadata to the order that is unnecessary!
			if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
				return $fields;
			}

			$new_fields = array();
			$fields_to_move = array();
			if ( isset( $fields['country'] ) ) {
				$fields_to_move['country'] = $fields['country'];
				unset( $fields['country'] );
			}
			if ( isset( $fields['postcode'] ) ) {
				$fields_to_move['postcode'] = $fields['postcode'];
				unset( $fields['postcode'] );
			}

			// move country to be before address 1
			// then postcode to be after country
			// everything else can stay as is
			foreach( $fields as $field_name => $field ) {
				if ( 'address_1' == $field_name ) {
					$new_fields[ 'country' ] = $fields_to_move[ 'country' ];
					$new_fields[ 'postcode' ] = $fields_to_move[ 'postcode' ];

					$new_fields[ 'gazchaps_getaddress_io_postcode_lookup_button'] = array(
						'type' => 'button',
						'class' => 'gazchaps_getaddress_io_postcode_lookup_button',
						'label' => '',
						'value' => ''
					);

					$new_fields[ 'gazchaps_getaddress_io_postcode_lookup_addresses'] = array(
						'type' => 'select',
						'wrapper_class' => 'form-field-wide',
						'class' => 'gazchaps_getaddress_io_postcode_lookup_addresses',
						'show' => false,
						'label' => __( 'Select Address', 'gazchaps-woocommerce-getaddress-io' ),
						'options' => array(),
					);

					if ( !empty( $new_fields['country']['wrapper_class'] ) ) {
						$new_fields['country']['wrapper_class'] .= ' form-field-wide';
					} else {
						$new_fields['country']['wrapper_class'] = 'form-field-wide';
					}
				}
				$new_fields[ $field_name ] = $field;
			}
			return $new_fields;
		}

	}

	new GazChap_WC_GetAddress_Plugin_Admin();
