<?php
/*
 * Plugin Name: GazChap's WooCommerce getAddress.io Postcode Lookup
 * Plugin URI: https://www.gazchap.com/posts/woocommerce-getaddress-io
 * Version: 2.2
 * Author: Gareth 'GazChap' Griffiths
 * Author URI: https://www.gazchap.com
 * Description: Adds a UK postcode address lookup tool to the WooCommerce checkout process.
 * Tested up to: 6.1
 * WC requires at least: 3.0.0
 * WC tested up to: 7.0.0
 * Text Domain: gazchaps-woocommerce-getaddress-io
 * Domain Path: /lang
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Donate link: https://paypal.me/gazchap
 */

	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	define('GC_WC_GAIO_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR );
	define('GC_WC_GAIO_URL', plugin_dir_url( __FILE__ ) );

	class GC_WC_GAIO {

		function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_languages' ) );
			add_action( 'plugins_loaded', array( $this, 'load_class' ), 15 );

			add_action( 'admin_init', array( $this, 'init_plugin' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
		}

		function load_class() {
			require GC_WC_GAIO_DIR . 'class.common.php';
			require GC_WC_GAIO_DIR . 'class.settings.php';
			require GC_WC_GAIO_DIR . 'class.checkout.php';

			if ( is_admin() ) {
				require GC_WC_GAIO_DIR . 'class.admin.php';
			}
		}

		function load_languages() {
			load_plugin_textdomain( 'gazchaps-woocommerce-getaddress-io', false, GC_WC_GAIO_DIR . 'lang' . DIRECTORY_SEPARATOR );
		}

		/**
		 * Check if WooCommerce is active - if not, then deactivate this plugin and show a suitable error message
		 */
		function init_plugin(){
		    if ( is_admin() ) {
		        if ( !class_exists( 'WooCommerce' ) ) {
		            add_action( 'admin_notices', array( $this, 'woocommerce_deactivated_notice' ) );
		            deactivate_plugins( plugin_basename( __FILE__ ) );
		        }
		    }
		}

		function woocommerce_deactivated_notice() {
		    ?>
		    <div class="notice notice-error"><p><?php esc_html_e( 'GazChap\'s WooCommerce getAddress.io Postcode Lookup requires WooCommerce to be installed and activated.', 'gazchaps-woocommerce-getaddress-io' ) ?></p></div>
		    <?php
		}

		function add_settings_link( $links ) {
			if ( !is_array( $links ) ) {
				$links = array();
			}
			$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '#gazchaps_getaddress_io_section_title-description">' . __( 'Settings', 'gazchaps-woocommerce-getaddress-io' ) . '</a>';
			$links[] = '<a href="' . esc_attr( esc_url( GazChap_WC_GetAddress_Plugin_Common::DONATE_URL ) ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Donate', 'gazchaps-woocommerce-getaddress-io' ) . '</a>';
			return $links;
		}

	}

	new GC_WC_GAIO();
