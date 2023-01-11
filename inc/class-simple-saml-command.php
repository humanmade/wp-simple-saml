<?php
/**
 * WP Simple SAML WP-CLI command class.
 */

namespace HumanMade\SimpleSaml;

use WP_CLI;
use WP_CLI_Command;

/**
 * Inspect SAML settings.
 *
 * ## EXAMPLES
 *
 *     # Print attribute mapping.
 *     $ wp simple-saml attribute-mapping
 *     +------------+------------+
 *     | Field      | Value      |
 *     +------------+------------+
 *     | user_login | EmployeeID |
 *     | user_email | EmailID    |
 *     | first_name | FirstName  |
 *     | last_name  | LastName   |
 *     +------------+------------+
 *
 *     # Print SP metadata (XML).
 *     $ wp simple-saml metadata
 *     <?xml version="1.0"?>
 *     <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
 *                          validUntil="2022-11-11T11:11:22Z"
 *                          cacheDuration="PT604800S"
 *                          entityID="php-saml">
 *     ...
 */
class Simple_Saml_Command extends WP_CLI_Command {

	/**
	 * Print mapping of SAML attributes to user data attributes.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Instead of returning all fields, return the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Print attribute mapping.
	 *     $ wp simple-saml attribute-mapping
	 *     +------------+------------+
	 *     | Field      | Value      |
	 *     +------------+------------+
	 *     | user_login | EmployeeID |
	 *     | user_email | EmailID    |
	 *     | first_name | FirstName  |
	 *     | last_name  | LastName   |
	 *     +------------+------------+
	 *
	 * @subcommand attribute-mapping
	 * @alias attribute-map
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function attribute_map( $args, $assoc_args ) {
		$map = get_attribute_map();

		( new WP_CLI\Formatter( $assoc_args, array_keys( $map ) ) )->display_item( $map );
	}

	/**
	 * Print SP metadata (XML).
	 *
	 * ## EXAMPLES
	 *
	 *     # Print SP metadata (XML).
	 *     $ wp simple-saml metadata
	 *     <?xml version="1.0"?>
	 *     <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
	 *                          validUntil="2022-11-11T11:11:22Z"
	 *                          cacheDuration="PT604800S"
	 *                          entityID="php-saml">
	 *     ...
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function metadata( $args, $assoc_args ) {
		$metadata = get_metadata();
		if ( is_wp_error( $metadata ) ) {
			WP_CLI::error( $metadata );
		}

		WP_CLI::print_value( $metadata );
	}
}
