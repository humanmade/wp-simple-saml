<?php
/**
 * WP Simple SAML Response WP-CLI command class.
 */

namespace HumanMade\SimpleSaml;

use DOMDocument;
use WP_CLI;
use WP_CLI_Command;

/**
 * Inspect SAML response data.
 *
 * ## EXAMPLES
 *
 *     # Get one SAML attribute.
 *     $ wp simple-saml response attribute mail
 *     john@example.com
 *
 *     # Get all SAML attributes.
 *     $ wp simple-saml response attributes
 *     +------------+------------------+
 *     | Field      | Value            |
 *     +------------+------------------+
 *     | user_login | 4815162342       |
 *     | user_email | john@example.com |
 *     | first_name | John             |
 *     | last_name  | Doe              |
 *     +------------+------------------+
 *
 *     # Decode SAML response.
 *     $ wp simple-saml response decode
 *     <?xml version="1.0"?>
 *     <samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_8e8dc5f69a98cc4c1ff3427e5ce34606fd672f91e6" Version="2.0" IssueInstant="2022-11-11T11:11:22Z" Destination="https://example.com/sso/verify" InResponseTo="ONELOGIN_4fee3b046395c4e751011e97f8900b5273d56685">
 *       <saml:Issuer>https://idp.example.com/metadata.php</saml:Issuer>
 *       <samlp:Status>
 *         <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
 *     ...
 *
 *     # Get NameID data.
 *     $ wp simple-saml response name-id-data
 *     +-----------------------+-----------------------------------------------------+
 *     | Field                 | Value                                               |
 *     +-----------------------+-----------------------------------------------------+
 *     | NameId                | _ce3d2948b4cf20146dee0a0b3dd6f69b6cf86f62d7         |
 *     | NameIdFormat          | urn:oasis:names:tc:SAML:2.0:nameid-format:transient |
 *     | NameIdNameQualifier   |                                                     |
 *     | NameIdSPNameQualifier | php-saml                                            |
 *     +-----------------------+-----------------------------------------------------+
 *
 *     # Process SAML response.
 *     $ wp simple-saml response process
 *     Success: SAML response processed.
 *     Print SAML2 Auth object? [y/n]
 */
class Response_Command extends WP_CLI_Command {

	/**
	 * Decoded SAML response data.
	 *
	 * @var string
	 */
	private $response;

	/**
	 * Process SAML response and return requested attribute.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the attribute to get.
	 *
	 * [--file=<value>]
	 * : The name of the SAML response file to use. If omitted, it will look for 'saml.txt'.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get mail attribute.
	 *     $ wp simple-saml response attribute mail
	 *     john@example.com
	 *
	 *     # Get mail attribute from custom file.
	 *     $ wp simple-saml response attribute mail --file=jane.saml
	 *     jane@example.com
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function attribute( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( __( 'Attribute name missing.', 'wp-simple-saml' ) );
		}

		$saml = $this->_process_response( $args, $assoc_args );

		$value = $saml->getAttribute( $args[0] );
		if ( is_null( $value ) ) {
			WP_CLI::warning( sprintf(
				/* translators: %s: Attribute name. */
				__( 'Attribute "%s" missing.', 'wp-simple-saml' ),
				$args[0]
			) );

			return;
		}

		$value = $this->_get_attribute_value( $value );

		WP_CLI::line( $value );
	}

	/**
	 * Process SAML response and return all attributes.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Instead of returning all fields, return the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--file=<value>]
	 * : The name of the SAML response file to use. If omitted, it will look for 'saml.txt'.
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
	 *     # Get all attributes.
	 *     $ wp simple-saml response attributes
	 *     +------------+------------------+
	 *     | Field      | Value            |
	 *     +------------+------------------+
	 *     | user_login | 4815162342       |
	 *     | user_email | john@example.com |
	 *     | first_name | John             |
	 *     | last_name  | Doe              |
	 *     +------------+------------------+
	 *
	 *     # Get all attributes from custom file.
	 *     $ wp simple-saml response attributes --file=jane.saml
	 *     +------------+------------------+
	 *     | Field      | Value            |
	 *     +------------+------------------+
	 *     | user_login | 1234567890       |
	 *     | user_email | jane@example.com |
	 *     | first_name | Jane             |
	 *     | last_name  | Doe              |
	 *     +------------+------------------+
	 *
	 *     # Get specified attributes only.
	 *     $ wp simple-saml response attributes --fields=user_login,user_email
	 *     +------------+------------------+
	 *     | Field      | Value            |
	 *     +------------+------------------+
	 *     | user_login | 4815162342       |
	 *     | user_email | john@example.com |
	 *     +------------+------------------+
	 *
	 *     # Get all attributes as JSON.
	 *     $ wp simple-saml response attributes --format=json
	 *     {"user_login":"4815162342","user_email":"john@example.com","first_name":"John","last_name":"Doe"}
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function attributes( $args, $assoc_args ) {
		$saml = $this->_process_response( $args, $assoc_args );

		$attributes = $saml->getAttributes();
		if ( ! $attributes ) {
			WP_CLI::warning( __( 'Attributes empty.', 'wp-simple-saml' ) );

			return;
		}

		$data = array_map( [ $this, '_get_attribute_value' ], $attributes );

		$this->_display_data( $assoc_args, $data );
	}

	/**
	 * Decode and print SAML response.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<value>]
	 * : The name of the SAML response file to use. If omitted, it will look for 'saml.txt'.
	 *
	 * ## EXAMPLES
	 *
	 *     # Decode SAML response.
	 *     $ wp simple-saml response decode
	 *     <?xml version="1.0"?>
	 *     <samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_8e8dc5f69a98cc4c1ff3427e5ce34606fd672f91e6" Version="2.0" IssueInstant="2022-11-11T11:11:22Z" Destination="https://example.com/sso/verify" InResponseTo="ONELOGIN_4fee3b046395c4e751011e97f8900b5273d56685">
	 *       <saml:Issuer>https://idp.example.com/metadata.php</saml:Issuer>
	 *       <samlp:Status>
	 *         <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
	 *     ...
	 *
	 *     # Decode SAML response from custom file.
	 *     $ wp simple-saml response decode --file=jane.saml
	 *     <?xml version="1.0"?>
	 *     <samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="pfxf3240435-0976-5706-92fe-1ab6529f5960" Version="2.0" IssueInstant="2022-11-11T11:11:22Z" Destination="https://example.com/sso/verify" InResponseTo="ONELOGIN_4fee3b046395c4e751011e97f8900b5273d56685">
	 *       <saml:Issuer>https://idp.example.com/metadata.php</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
	 *       <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
	 *         <ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
	 *     ...
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function decode( $args, $assoc_args ) {
		$this->_validate_response( $args, $assoc_args );

		$document = new DOMDocument();
		$document->formatOutput = true;
		$document->preserveWhiteSpace = false;
		$document->loadXML( $this->response );

		$xml = $document->saveXML();

		WP_CLI::print_value( $xml );
	}

	/**
	 * Process SAML response and return NameID data.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Instead of returning all fields, return the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--file=<value>]
	 * : The name of the SAML response file to use. If omitted, it will look for 'saml.txt'.
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
	 *     # Get NameID data.
	 *     $ wp simple-saml response name-id-data
	 *     +-----------------------+-----------------------------------------------------+
	 *     | Field                 | Value                                               |
	 *     +-----------------------+-----------------------------------------------------+
	 *     | NameId                | _ce3d2948b4cf20146dee0a0b3dd6f69b6cf86f62d7         |
	 *     | NameIdFormat          | urn:oasis:names:tc:SAML:2.0:nameid-format:transient |
	 *     | NameIdNameQualifier   |                                                     |
	 *     | NameIdSPNameQualifier | php-saml                                            |
	 *     +-----------------------+-----------------------------------------------------+
	 *
	 *     # Get NameID data from custom file.
	 *     $ wp simple-saml response name-id-data --file=jane.saml
	 *     +-----------------------+----------------------------------------------------+
	 *     | Field                 | Value                                              |
	 *     +-----------------------+----------------------------------------------------+
	 *     | NameId                | 1234567890                                         |
	 *     | NameIdFormat          | urn:oasis:names:tc:SAML:2.0:nameid-format:kerberos |
	 *     | NameIdNameQualifier   |                                                    |
	 *     | NameIdSPNameQualifier | php-saml                                           |
	 *     +-----------------------+----------------------------------------------------+
	 *
	 *     # Get specified attributes only.
	 *     $ wp simple-saml response name-id-data --fields=NameId,NameIdFormat
	 *     +--------------+-----------------------------------------------------+
	 *     | Field        | Value                                               |
	 *     +--------------+-----------------------------------------------------+
	 *     | NameId       | _ce3d2948b4cf20146dee0a0b3dd6f69b6cf86f62d7         |
	 *     | NameIdFormat | urn:oasis:names:tc:SAML:2.0:nameid-format:transient |
	 *     +--------------+-----------------------------------------------------+
	 *
	 *     # Get all attributes as JSON.
	 *     $ wp simple-saml response name-id-data --format=json
	 *     {"NameId":"_ce3d2948b4cf20146dee0a0b3dd6f69b6cf86f62d7","NameIdFormat":"urn:oasis:names:tc:SAML:2.0:nameid-format:transient","NameIdNameQualifier":"","NameIdSPNameQualifier":"php-saml"}
	 *
	 * @subcommand name-id-data
	 * @alias nameid-data
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function name_id_data( $args, $assoc_args ) {
		$saml = $this->_process_response( $args, $assoc_args );

		$data = [
			'NameId'                => $saml->getNameId(),
			'NameIdFormat'          => $saml->getNameIdFormat(),
			'NameIdNameQualifier'   => $saml->getNameIdNameQualifier(),
			'NameIdSPNameQualifier' => $saml->getNameIdSPNameQualifier(),
		];

		$this->_display_data( $assoc_args, $data );
	}

	/**
	 * Process SAML response and optionally print SAML2 Auth object.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<value>]
	 * : The name of the SAML response file to use. If omitted, it will look for 'saml.txt'.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Process SAML response.
	 *     $ wp simple-saml response process
	 *     Success: SAML response processed.
	 *     Print SAML2 Auth object? [y/n]
	 *
	 *     # Process SAML response from custom file.
	 *     $ wp simple-saml response process --file=jane.saml
	 *     Success: SAML response processed.
	 *     Print SAML2 Auth object? [y/n]
	 *
	 *     # Process SAML response and print SAML2 Auth object.
	 *     $ wp simple-saml response process --yes
	 *     Success: SAML response processed.
	 *     OneLogin\Saml2\Auth::__set_state(array(
	 *        '_settings' =>
	 *       OneLogin\Saml2\Settings::__set_state(array(
	 *          '_paths' =>
	 *         array (
	 *    ...
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 */
	public function process( $args, $assoc_args ) {
		$saml = $this->_process_response( $args, $assoc_args );

		WP_CLI::success( __( 'SAML response processed.', 'wp-simple-saml' ) );

		WP_CLI::confirm( __( 'Print SAML2 Auth object?', 'wp-simple-saml' ), $assoc_args );

		WP_CLI::print_value( $saml );
	}

	/**
	 * Display given data according to passed arguments.
	 *
	 * @param array $format_args Arguments passed to the command (original order).
	 * @param array $data        Data to display.
	 *
	 * @return void
	 */
	private function _display_data( $format_args, $data ) {
		$fields = array_keys( $data );

		$formatter = new WP_CLI\Formatter( $format_args, $fields );
		$formatter->display_item( $data );
	}

	/**
	 * Return (first) attribute value.
	 *
	 * @param string|string[] $value One or more attribute values.
	 *
	 * @return string Attribute value.
	 */
	private function _get_attribute_value( $value ) {
		return is_array( $value ) ? reset( $value ) : $value;
	}

	/**
	 * Validate and process SAML response data and return SAML2 Auth object.
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 *
	 * @return void|\OneLogin\Saml2\Auth|\WP_Error SAML2 Auth object, or WordPress error.
	 *
	 * @throws WP_CLI\ExitException If error.
	 */
	private function _process_response( $args, $assoc_args ) {
		$this->_validate_response( $args, $assoc_args );

		// Ensure strict mode is disabled.
		// Otherwise, validating the response will fail due to expired timestamps or mismatching destinations etc.
		add_filter( 'wpsimplesaml_config', function ( array $config ) {
			return array_merge( $config, [ 'strict' => false ] );
		}, 100 );

		$saml = process_response();
		if ( is_wp_error( $saml ) ) {
			WP_CLI::error( $saml, false );

			$saml = instance();
			if ( $saml ) {
				$error = $saml->getLastErrorReason();
				if ( $error ) {
					WP_CLI::print_value( $error );
				}
			}

			exit( 1 );
		}

		return $saml;
	}

	/**
	 * Validate SAML response for subsequent use. If invalid, exit.
	 *
	 * @param array $args       Arguments passed to the command (original order).
	 * @param array $assoc_args Arguments passed to the command (named).
	 *
	 * @return void
	 *
	 * @throws WP_CLI\ExitException If response invalid.
	 */
	private function _validate_response( $args, $assoc_args ) {
		$file = $assoc_args['file'] ?? 'saml.txt';
		if ( ! file_exists( $file ) || ! is_file( $file ) ) {
			WP_CLI::error( sprintf(
				/* translators: %s: File name. */
				__( 'Unable to read content from "%s".', 'wp-simple-saml' ),
				$file
			) );
		}

		$response = file_get_contents( $file );
		if ( ! $response ) {
			WP_CLI::error( __( 'Response missing or empty.', 'wp-simple-saml' ), false );
			WP_CLI::print_value( $response );

			exit( 1 );
		}

		$response = preg_replace( '/[\n\r]+/', "\n", trim( $response ) );

		$_POST['SAMLResponse'] = $response;

		$response = base64_decode( $response );
		if ( ! $response || ! is_string( $response ) ) {
			WP_CLI::error( __( 'Response data invalid or empty.', 'wp-simple-saml' ), false );
			WP_CLI::print_value( $response );

			exit( 1 );
		}

		$this->response = $response;
	}
}
