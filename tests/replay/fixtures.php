<?php
/** Generate signed fixtures and provide minimal WordPress function adapters. */

// phpcs:disable HM.Functions.NamespacedFunctions.MissingNamespace, PSR1.Files.SideEffects.FoundWithSymbols -- WordPress adapters must be global in this standalone harness.
use HumanMade\SimpleSaml as Plugin;
use OneLogin\Saml2\Utils;

error_reporting( E_ALL & ~E_DEPRECATED );
$repo = dirname( __DIR__, 2 );
$fixture_dir = getenv( 'SAML_FIXTURE_DIR' );
if ( ! $fixture_dir ) {
	throw new RuntimeException( 'SAML_FIXTURE_DIR is required.' );
}
require $repo . '/vendor/autoload.php';
require $repo . '/inc/namespace.php';
require $repo . '/inc/admin/namespace.php';

require __DIR__ . '/inc/class-wp-error.php';
require __DIR__ . '/inc/class-wp-user.php';
/**
 * Provide is_wp_error behavior for standalone regression tests.
 *
 * @param mixed $v Test input.
 * @return mixed
 */
function is_wp_error( $v ) {
	return $v instanceof WP_Error;
}
/**
 * Provide __ behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @param mixed ...$args Test input.
 * @return mixed
 */
function __( $s, ...$args ) {
	return $s;
}
/**
 * Provide esc_html__ behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @param mixed ...$args Test input.
 * @return mixed
 */
function esc_html__( $s, ...$args ) {
	return $s;
}
/**
 * Provide esc_html behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @return mixed
 */
function esc_html( $s ) {
	return $s;
}
/**
 * Provide trailingslashit behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @return mixed
 */
function trailingslashit( $s ) {
	return rtrim( $s, '/' ) . '/';
}
/**
 * Provide is_multisite behavior for standalone regression tests.
 *
 * @return mixed
 */
function is_multisite() {
	return getenv( 'SAML_MULTISITE' ) === '1';
}
/**
 * Provide get_main_site_id behavior for standalone regression tests.
 *
 * @return mixed
 */
function get_main_site_id() {
	return (int) ( getenv( 'SAML_MAIN_SITE' ) ?: 1 );
}
/**
 * Provide is_plugin_active_for_network behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @return mixed
 */
function is_plugin_active_for_network( $s ) {
	return false;
}
/**
 * Provide plugin_basename behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @return mixed
 */
function plugin_basename( $s ) {
	return $s;
}
/**
 * Provide home_url behavior for standalone regression tests.
 *
 * @param mixed $s Test input.
 * @return mixed
 */
function home_url( $s = '/' ) {
	return 'https://sp.example.test' . $s;
}
/**
 * Provide get_option behavior for standalone regression tests.
 *
 * @param mixed $key Test input.
 * @param mixed $default Test input.
 * @return mixed
 */
function get_option( $key, $default = false ) {
	return [
		'sso_sp_base' => 'https://sp.example.test/',
		'sso_idp_metadata' => file_get_contents( $GLOBALS['fixture_dir'] . '/metadata.xml' ),
	][ $key ] ?? $default;
}
/**
 * Provide get_user_by behavior for standalone regression tests.
 *
 * @param mixed $field Test input.
 * @param mixed $value Test input.
 * @return mixed
 */
function get_user_by( $field, $value ) {
	return $field === 'email' && $value === 'admin@example.test' ? new WP_User() : false;
}
/**
 * Provide is_ssl behavior for standalone regression tests.
 *
 * @return mixed
 */
function is_ssl() {
	return true;
}
/**
 * Provide wp_set_auth_cookie behavior for standalone regression tests.
 *
 * @param mixed $id Test input.
 * @param mixed $remember Test input.
 * @param mixed $secure Test input.
 * @return mixed
 */
function wp_set_auth_cookie( $id, $remember, $secure ) {
	$GLOBALS['cookie_call'] = compact( 'id', 'remember', 'secure' );
}
/**
 * Provide apply_filters behavior for standalone regression tests.
 *
 * @param mixed $name Test input.
 * @param mixed $value Test input.
 * @param mixed ...$args Test input.
 * @return mixed
 */
function apply_filters( $name, $value, ...$args ) {
	if ( $name === 'wpsimplesaml_idp_metadata_xml' ) {
		return get_option( 'sso_idp_metadata' );
	}
	if ( $name === 'wpsimplesaml_config' ) {
		$config = Plugin\Admin\get_config();
		if ( ( $GLOBALS['mode'] ?? 'default' ) !== 'default' ) {
			$config['strict'] = true;
		}
		return $config;
	}
	return $value;
}
$_SERVER = [
	'HTTPS' => 'on',
	'HTTP_HOST' => 'sp.example.test',
	'SERVER_PORT' => 443,
	'REQUEST_URI' => '/sso/verify',
	'SCRIPT_NAME' => '/sso/verify',
	'SERVER_NAME' => 'sp.example.test',
];
/**
 * Provide xml_fixture behavior for standalone regression tests.
 *
 * @param array $changes Test input.
 * @return mixed
 */
function xml_fixture( array $changes = [] ) {
	$v = array_merge([
		'issuer' => 'https://idp.example.test/',
		'audience' => 'https://sp.example.test/',
		'recipient' => 'https://sp.example.test/sso/verify',
		'destination' => 'https://sp.example.test/sso/verify',
		'expiry' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 600 ),
		'request' => '_original_request',
	], $changes);
	$now = gmdate( 'Y-m-d\TH:i:s\Z' );
	$before = gmdate( 'Y-m-d\TH:i:s\Z', time() - 3600 );
	return '<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_response_123" Version="2.0" IssueInstant="' . $now . '" Destination="' . $v['destination'] . '" InResponseTo="' . $v['request'] . '">'
	. '<saml:Issuer>' . $v['issuer'] . '</saml:Issuer><samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>'
	. '<saml:Assertion ID="_assertion_123" Version="2.0" IssueInstant="' . $now . '"><saml:Issuer>' . $v['issuer'] . '</saml:Issuer>'
	. '<saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">admin@example.test</saml:NameID>'
	. '<saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData InResponseTo="' . $v['request'] . '" Recipient="' . $v['recipient'] . '" NotOnOrAfter="' . $v['expiry'] . '"/></saml:SubjectConfirmation></saml:Subject>'
	. '<saml:Conditions NotBefore="' . $before . '" NotOnOrAfter="' . $v['expiry'] . '"><saml:AudienceRestriction><saml:Audience>' . $v['audience'] . '</saml:Audience></saml:AudienceRestriction></saml:Conditions>'
	. '<saml:AuthnStatement AuthnInstant="' . $now . '" SessionIndex="_session_123"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef></saml:AuthnContext></saml:AuthnStatement>'
	. '<saml:AttributeStatement><saml:Attribute Name="email"><saml:AttributeValue>admin@example.test</saml:AttributeValue></saml:Attribute></saml:AttributeStatement></saml:Assertion></samlp:Response>';
}
if ( ( $argv[1] ?? '' ) === 'generate' ) {
	$key = openssl_pkey_new( [
		'private_key_bits' => 2048,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	] );
	$csr = openssl_csr_new( [ 'commonName' => 'Local synthetic SAML IdP' ], $key, [ 'digest_alg' => 'sha256' ] );
	$cert = openssl_csr_sign( $csr, null, $key, 1, [ 'digest_alg' => 'sha256' ] );
	openssl_pkey_export( $key, $pem_key );
	openssl_x509_export( $cert, $pem_cert );
	$body = preg_replace( '/-----[^\n]+-----|\s/', '', $pem_cert );
	file_put_contents( $fixture_dir . '/metadata.xml', '<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://idp.example.test/"><md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"><md:KeyDescriptor use="signing"><ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#"><ds:X509Data><ds:X509Certificate>' . $body . '</ds:X509Certificate></ds:X509Data></ds:KeyInfo></md:KeyDescriptor><md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://idp.example.test/sso"/></md:IDPSSODescriptor></md:EntityDescriptor>' );
	$cases = [
		'valid' => [],
		'expired' => [ 'expiry' => gmdate( 'Y-m-d\TH:i:s\Z', time() - 600 ) ],
		'wrong-audience' => [ 'audience' => 'https://other-sp.example.test/' ],
		'wrong-issuer' => [ 'issuer' => 'https://other-idp.example.test/' ],
		'wrong-recipient' => [ 'recipient' => 'https://other-sp.example.test/sso/verify' ],
		'wrong-destination' => [ 'destination' => 'https://other-sp.example.test/sso/verify' ],
	];
	foreach ( $cases as $name => $changes ) {
		file_put_contents( $fixture_dir . '/' . $name . '.xml', Utils::addSign( xml_fixture( $changes ), $pem_key, $pem_cert ) );
	}
	file_put_contents( $fixture_dir . '/unsigned.xml', xml_fixture() );
	file_put_contents( $fixture_dir . '/missing-assertion-id.xml', Utils::addSign( str_replace( ' ID="_assertion_123"', '', xml_fixture() ), $pem_key, $pem_cert ) );
	$signed = file_get_contents( $fixture_dir . '/valid.xml' );
	file_put_contents( $fixture_dir . '/tampered.xml', str_replace( 'admin@example.test', 'attacker@example.test', $signed ) );
	$other_key = openssl_pkey_new( [ 'private_key_bits' => 2048 ] );
	openssl_pkey_export( $other_key, $other_pem );
	file_put_contents( $fixture_dir . '/untrusted-key.xml', Utils::addSign( xml_fixture(), $other_pem, $pem_cert ) );
	echo "Generated synthetic signed fixtures; no private key retained.\n";
	exit;
}
