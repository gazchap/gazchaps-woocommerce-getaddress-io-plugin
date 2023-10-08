<?php
	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	class GazChap_WC_GetAddress_Plugin_Exception extends RuntimeException {
		public $response = null;

		public function __construct( $response, $message = '', $code = 0, $previous = null ) {
			$this->response = $response;
			parent::__construct( $message, $code, $previous );
		}
	}