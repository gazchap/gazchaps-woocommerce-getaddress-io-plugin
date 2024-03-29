<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Settings {
		const SECTION_ID = 'gazchaps_getaddress_io';

		public function __construct() {
			add_filter( 'woocommerce_get_sections_general', array( $this, 'add_settings_section' ), 10, 1 );
			add_filter( 'woocommerce_get_settings_general', array( $this, 'add_settings_to_section' ), 10, 2 );
		}

		public function add_settings_section( $sections ) {
			$sections[self::SECTION_ID] = __( 'getAddress.io Settings', 'gazchaps-woocommerce-getaddress-io' );
			return $sections;
		}

		public function add_settings_to_section( $settings, $section ) {
			if ( self::SECTION_ID !== $section ) return $settings;

			$new_settings = array();

			$new_settings[] = array(
				'id'       => 'gazchaps_getaddress_io_section_title',
				'title' => __( 'getAddress.io Settings', 'gazchaps-woocommerce-getaddress-io' ),
				'desc' =>   '<p>' . sprintf( __( 'Settings required for GazChap\'s WooCommerce getAddress.io Postcode Lookup. Get your API key from <a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s</a>', 'gazchaps-woocommerce-getaddress-io' ), 'https://getaddress.io' ) . '</p>' .
							'<p>' . sprintf( __( 'If you find this free plugin to be useful, please consider <a href="%1$s" target="_blank" rel="noopener noreferrer">making a donation</a> to help me support and maintain the plugin in the future. Thanks!', 'gazchaps-woocommerce-getaddress-io' ), esc_attr( esc_url( GazChap_WC_GetAddress_Plugin_Common::DONATE_URL ) ) ) . '</p>',
				'type'     => 'title',
			);

			$new_settings[] = array(
				'id'       => 'gazchaps_getaddress_io_enabled',
				'title'     => __( 'Enabled', 'gazchaps-woocommerce-getaddress-io' ),
				'desc' => __( 'Activate the integration (requires an API key to be entered below)', 'gazchaps-woocommerce-getaddress-io' ),
				'type'     => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_api_key',
				'title'      => __( 'API Key/Domain Token', 'gazchaps-woocommerce-getaddress-io' ),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_enable_for_billing_address',
				'title'      => __( 'Enable for Billing Address', 'gazchaps-woocommerce-getaddress-io' ),
				'desc'      => __( 'Add the lookup field to the Billing Address section in checkout and account areas', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_enable_for_shipping_address',
				'title'      => __( 'Enable for Shipping Address', 'gazchaps-woocommerce-getaddress-io' ),
				'desc'      => __( 'Add the lookup field to the Shipping Address section in checkout and account areas', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_enable_for_wc_admin',
				'title'      => __( 'Enable in Admin', 'gazchaps-woocommerce-getaddress-io' ),
				'desc'      => __( 'Add the lookup field to the address when managing orders in the WooCommerce admin', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_hide_address_fields',
				'title'      => __( 'Hide Address Fields', 'gazchaps-woocommerce-getaddress-io' ),
				'desc'      => __( 'Hide address fields until a lookup is performed and an address is selected - does not hide fields if an address is already present!', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => 'no',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_allow_manual_entry',
				'title'      => __( 'Allow Manual Entry', 'gazchaps-woocommerce-getaddress-io' ),
				'desc'      => __( 'Allow customers to choose to enter their address manually, when \'Hide Address Fields\' is turned on - think very carefully before turning this off!', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => 'yes',
				'type'      => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_find_address_button_text',
				'title'      => __( 'Find Address Button Text', 'gazchaps-woocommerce-getaddress-io' ),
				'desc_tip'      => __( 'Change the text on the Find Address buttons. If left blank, translations will work for "Find Address".', 'gazchaps-woocommerce-getaddress-io' ),
				'placeholder' => self::get_find_button_text(),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_find_address_searching_text',
				'title'      => __( 'Find Address Searching Text', 'gazchaps-woocommerce-getaddress-io' ),
				'desc_tip'      => __( 'Change the text shown on the button when a search is in progress. If left blank, translations will work for "Searching...".', 'gazchaps-woocommerce-getaddress-io' ),
				'placeholder' => self::get_searching_text(),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_enter_address_manually_text',
				'title'      => __( 'Enter Address Manually Text', 'gazchaps-woocommerce-getaddress-io' ),
				'desc_tip'      => __( 'Change the link text that is clicked to enter an address manually. If left blank, translations will work for "Enter an address manually".', 'gazchaps-woocommerce-getaddress-io' ),
				'placeholder' => self::get_enter_address_manually_text(),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_hook_priority',
				'title'     => __( 'Hook Priority', 'gazchaps-woocommerce-getaddress-io' ),
				'desc'  => __( 'Some plugins that also modify the checkout fields may conflict with this plugin and stop the lookup button from appearing.<br>Experiment with a higher priority here to see if this can fix the conflict.<br>e.g. for <strong>Checkout Field Editor</strong> a priority of 1001 or above seems to work.', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => '10',
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchap_getaddress_io_options',
				'type'      => 'sectionend',
			);

			$settings = array_merge( $settings, $new_settings );

			return $settings;
		}

		/**
		 * @return string
		 */
		public static function get_api_key() {
			$key = get_option( 'gazchaps_getaddress_io_api_key', '' );
			return $key;
		}

		/**
		 * @return string
		 */
		public static function get_find_button_text() {
			$text = get_option( 'gazchaps_getaddress_io_find_address_button_text' );
			if ( !$text ) $text = __( 'Find Address', 'gazchaps-woocommerce-getaddress-io' );
			return apply_filters( 'gazchaps-woocommerce-getaddress-io_find-address-button-text', $text );
		}

		/**
		 * @return string
		 */
		public static function get_searching_text() {
			$text = get_option( 'gazchaps_getaddress_io_find_address_searching_text' );
			if ( !$text ) $text = __( 'Searching...', 'gazchaps-woocommerce-getaddress-io' );
			return apply_filters( 'gazchaps-woocommerce-getaddress-io_find-address-searching-text', $text );
		}

		/**
		 * @return string
		 */
		public static function get_enter_address_manually_text() {
			$text = get_option( 'gazchaps_getaddress_io_enter_address_manually_text' );
			if ( !$text ) $text = __( 'Enter an address manually', 'gazchaps-woocommerce-getaddress-io' );
			return apply_filters( 'gazchaps-woocommerce-getaddress-io_enter-address-manually-text', $text );
		}
	}

	new GazChap_WC_GetAddress_Plugin_Settings();
