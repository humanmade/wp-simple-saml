<?php
/** Generate signed fixtures and provide minimal WordPress function adapters. */

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
require __DIR__ . '/inc/namespace.php';
$_SERVER = [
	'HTTPS' => 'on',
	'HTTP_HOST' => 'sp.example.test',
	'SERVER_PORT' => 443,
	'REQUEST_URI' => '/sso/verify',
	'SCRIPT_NAME' => '/sso/verify',
	'SERVER_NAME' => 'sp.example.test',
];
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
