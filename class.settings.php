<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Settings {

		public function __construct() {
			add_filter( 'woocommerce_get_settings_general', array( $this, 'add_settings_to_section' ), 10, 1 );
		}

		public function add_settings_to_section( $settings ) {
			$new_settings = array();

			$new_settings[] = array(
				'id'       => 'gazchaps_getaddress_io_section_title',
				'title' => __( 'getAddress.io Settings', 'gazchaps-woocommerce-getaddress-io' ),
				'desc' => __( 'Settings required for GazChap\'s WooCommerce getAddress.io Postcode Lookup. Get your API key from https://getaddress.io', 'gazchaps-woocommerce-getaddress-io' ),
				'type'     => 'title',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_last_desc',
				'title'     => '',
				'desc'      => __( 'To test the lookup without affecting your API usage limit, use the postcode TR19 7AA.', 'gazchaps-woocommerce-getaddress-io' ),
				'type'      => 'title',
			);

			$new_settings[] = array(
				'id'       => 'gazchaps_getaddress_io_enabled',
				'title'     => __( 'Enabled', 'gazchaps-woocommerce-getaddress-io' ),
				'desc' => __( 'Activate the integration (requires an API key to be entered below)', 'gazchaps-woocommerce-getaddress-io' ),
				'type'     => 'checkbox',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_api_key',
				'title'      => __( 'API Key', 'gazchaps-woocommerce-getaddress-io' ),
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
				'id'        => 'gazchaps_getaddress_io_find_address_button_text',
				'title'      => __( 'Find Address Button Text', 'gazchaps-woocommerce-getaddress-io' ),
				'desc_tip'      => __( 'Change the text on the Find Address buttons. If left blank, translations will work for "Find Address".', 'gazchaps-woocommerce-getaddress-io' ),
				'placeholder' => __( 'Find Address', 'gazchaps-woocommerce-getaddress-io' ),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_find_address_searching_text',
				'title'      => __( 'Find Address Searching Text', 'gazchaps-woocommerce-getaddress-io' ),
				'desc_tip'      => __( 'Change the text shown on the button when a search is in progress. If left blank, translations will work for "Searching...".', 'gazchaps-woocommerce-getaddress-io' ),
				'placeholder' => __( 'Searching...', 'gazchaps-woocommerce-getaddress-io' ),
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchaps_getaddress_io_email_when_usage_limit_hit',
				'title'     => __( 'Email when Usage Limit Hit', 'gazchaps-woocommerce-getaddress-io' ),
				'desc_tip'  => __( 'Email address to send the notification to. Leave blank to disable the notification. Use {admin_email} for the Email Address set in WordPress general settings. Only one email will be sent in a day.', 'gazchaps-woocommerce-getaddress-io' ),
				'default'   => '{admin_email}',
				'type'      => 'text',
			);

			$new_settings[] = array(
				'id'        => 'gazchap_getaddress_io_options',
				'type'      => 'sectionend',
			);

			$settings = array_merge( $settings, $new_settings );

			return $settings;
		}

	}

	new GazChap_WC_GetAddress_Plugin_Settings();