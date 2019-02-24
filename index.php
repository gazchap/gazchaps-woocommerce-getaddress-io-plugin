<?php
	/*
		Plugin Name: GazChap's WooCommerce getAddress.io Plugin
		Plugin URI: https://www.gazchap.com/posts/woocommerce-getaddress-io-plugin
		Description: Adds a UK postcode address lookup tool to the WooCommerce checkout process.
		Author: Gareth 'GazChap' Griffiths
		Author URI: https://www.gazchap.com
		Text Domain: gazchaps-woocommerce-getaddress-io-plugin
		Domain Path: /lang
		Version: 1.0
		WooCommerce requires at least version: 3.0.0
		WooCommerce tested up to version: 3.5.5
		License: GNU General Public License v2.0
		License URI: http://www.gnu.org/licenses/gpl-2.0.html
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
			require GC_WC_GAIO_DIR . 'class.settings.php';
			require GC_WC_GAIO_DIR . 'class.checkout.php';
		}

		function load_languages() {
			load_plugin_textdomain( 'gazchaps-woocommerce-getaddress-io-plugin', false, GC_WC_GAIO_DIR . 'lang' . DIRECTORY_SEPARATOR );
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
		    <div class="notice notice-error"><p><?php esc_html_e( 'GazChap\'s WooCommerce getAddress.io Plugin requires WooCommerce to be installed and activated.', 'gazchaps-woocommerce-getaddress-io-plugin' ) ?></p></div>
		    <?php
		}

		function add_settings_link( $links ) {
			if ( !is_array( $links ) ) {
				$links = array();
			}
			$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '#gazchaps_getaddress_io_section_title-description">' . __( 'Settings', 'gazchaps-woocommerce-getaddress-io-plugin' ) . '</a>';
			return $links;
		}

	}

	new GC_WC_GAIO();
