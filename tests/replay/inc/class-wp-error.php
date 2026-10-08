<?php
/** Minimal WordPress user/error adapter for the standalone replay tests. */

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions.MissingNamespace -- These WordPress classes must be global.
/** Standalone WordPress test adapter. */
class WP_Error {
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $code;
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $message;
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $data;
	/**
	 * Initialize the test adapter.
	 *
	 * @param mixed $code Test input.
	 * @param mixed $message Test input.
	 * @param mixed $data Test input.
	 */
	public function __construct( $code, $message, $data = null ) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}
	/**
	 * Provide get_error_message behavior for standalone regression tests.
	 *
	 * @return mixed
	 */
	public function get_error_message() {
		return $this->message;
	}
}
