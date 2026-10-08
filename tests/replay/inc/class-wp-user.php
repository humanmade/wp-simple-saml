<?php
/** Minimal WordPress user/error adapter for the standalone replay tests. */

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions.MissingNamespace -- These WordPress classes must be global.
/** Standalone WordPress test adapter. */
class WP_User {
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $ID = 1;
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $user_email = 'admin@example.test';
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $roles = [ 'administrator' ];
}
