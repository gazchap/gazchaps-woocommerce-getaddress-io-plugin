<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_API {
		const BASE_URL = 'https://api.getaddress.io/';

		const ADDRESS_COMPONENTS = array(
			'line_1',
			'line_2',
			'line_3',
			'line_4',
			'town_or_city',
			'locality',
			'county',
		);

		public static function autocomplete( $postcode ) {
			$template = implode( "\t", array_map( function( $component ) {
				return '{' . $component . '}';
			}, self::ADDRESS_COMPONENTS ) );

			$response = self::request( 'autocomplete', $postcode, array(
				'all' => 'true',
				'template' => $template,
			) );
			$results = json_decode( $response['body'] );

			// if results is empty, throw a '404' (what the previous API did)
			if ( empty( $results->suggestions ) ) {
				throw new GazChap_WC_GetAddress_Plugin_Exception( $response, 'No addresses found for this postcode.', 404 );
			}

			// add the individual lines of the address in as per old API
			array_map( array( self::class, 'format_result' ), $results->suggestions );
			return $results;
		}

		public static function usage_range( $from, $to ) {
			if ( !is_a( $from, DateTime::class ) ) {
				$from = new DateTime('@' . strtotime($from));
			}
			if ( !is_a( $to, DateTime::class ) ) {
				$to = new DateTime('@' . strtotime($to));
			}
			$path = 'from/' . $from->format('j/n/Y') . '/To/' . $to->format('j/n/Y');

			return self::request( 'usage', $path, array(), true );
		}

		private static function request( $endpoint, $path, $params = array(), $admin = false ) {
			$url = self::BASE_URL . $endpoint . '/' . $path;

			if ( !is_array( $params ) ) $params = array();
			$params['api-key'] = !$admin ?
				GazChap_WC_GetAddress_Plugin_Settings::get_api_key() :
				GazChap_WC_GetAddress_Plugin_Settings::get_admin_key();
			if ( empty( $params['api-key'] ) ) return false;

			$url .= '?' . http_build_query( $params );
			$response = wp_remote_get( $url );
			if ( 200 == $response['response']['code'] ) {
				return $response;
			} else {
				throw new GazChap_WC_GetAddress_Plugin_Exception( $response, $response['response']['message'], $response['response']['code'] );
			}
		}

		private static function format_result( $result ) {
			$parts = explode( "\t", $result->address );
			foreach( $parts as $k => $v ) {
				$result->{ self::ADDRESS_COMPONENTS[$k] } = $v;
			}
			return $result;
		}
	}

