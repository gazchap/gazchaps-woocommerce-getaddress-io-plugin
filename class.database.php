<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Database {
		private static $enabled = true;
		const DBVERSION_OPTION_KEY = 'gazchaps_get_address_io_dbversion';

		public static function enabled() {
			return self::$enabled;
		}

		public static function disable() {
			self::$enabled = false;
		}

		public static function lookup( $postcode ) {
			global $wpdb;
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE postcode=%s", $postcode )
			);
			if ( empty( $row ) || strtotime( $row->expires ) <= time() ) {
				$wpdb->delete( self::table(), array( 'postcode' => $postcode ) );
				return false;
			}

			return json_decode($row->results);
		}
		
		public static function store( $postcode, $results ) {
			global $wpdb;
			return $wpdb->insert( self::table(), array(
				'postcode' => $postcode,
				'results' => json_encode( $results ),
				'expires' => date( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			) );
		}

		public static function purge() {
			global $wpdb;
			$wpdb->query( "DELETE FROM " . self::table() . " WHERE expires <= NOW()" );
		}

		public static function install( $version ) {
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$charset_collate = $wpdb->get_charset_collate();
			$update_version = null;

			// 3.0
			if ( !$version || version_compare( $version, '3.0', '<' ) ) {
				$sql = "CREATE TABLE " . self::table() . " (
					postcode varchar(20) NOT NULL,
					results mediumtext NOT NULL,
					expires timestamp NOT NULL,
					PRIMARY KEY  (postcode)
				) " . $charset_collate . ";";

				$result = dbDelta($sql);
				if ( isset( $result[$wpdb->prefix . 'gazchaps_getaddress_io'] ) && stristr( $result[$wpdb->prefix . 'gazchaps_getaddress_io'], 'created table' ) ) {
					$update_version = '3.0';
				} else {
					self::disable();
				}
			}
			if ( $update_version ) {
				update_option( self::DBVERSION_OPTION_KEY, $update_version );
			}
		}

		public static function uninstall() {
			global $wpdb;
			delete_option( self::DBVERSION_OPTION_KEY );
			$wpdb->query( "DROP TABLE IF EXISTS " . self::table() );
		}

		private static function table() {
			global $wpdb;
			return $wpdb->prefix . 'gazchaps_getaddress_io';
		}
	}