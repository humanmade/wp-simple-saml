<?php
/**
 * This file is part of php-saml.
 *
 * (c) OneLogin Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package OneLogin
 * @author  OneLogin Inc <saml-info@onelogin.com>
 * @license MIT https://github.com/onelogin/php-saml/blob/master/LICENSE
 * @link    https://github.com/onelogin/php-saml
 */

namespace OneLogin\Saml2;

use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;

use DOMDocument;
use Exception;

/**
 * Configuration of the OneLogin PHP Toolkit
 */
class Settings {

	/**
	 * List of paths.
	 *
	 * @var array
	 */
	private $_paths = array();

	/**
	 * Baseurl of the site.
	 * 
	 * @var string 
	 */
	private $_baseurl;

	/**
	 * Strict. If active, PHP Toolkit will reject unsigned or unencrypted messages
	 * if it expects them signed or encrypted. If not, the messages will be accepted
	 * and some security issues will be also relaxed.
	 *
	 * @var bool
	 */
	private $_strict = false;

	/**
	 * Activate debug mode
	 *
	 * @var bool
	 */
	private $_debug = false;

	/**
	 * SP data.
	 *
	 * @var array
	 */
	private $_sp = array();

	/**
	 * IdP data.
	 *
	 * @var array
	 */
	private $_idp = array();

	/**
	 * Compression settings that determine
	 * whether gzip compression should be used.
	 *
	 * @var array
	 */
	private $_compress = array();

	/**
	 * Security Info related to the SP.
	 *
	 * @var array
	 */
	private $_security = array();

	/**
	 * Setting contacts.
	 *
	 * @var array
	 */
	private $_contacts = array();

	/**
	 * Setting organization.
	 *
	 * @var array
	 */
	private $_organization = array();

	/**
	 * Setting errors.
	 *
	 * @var array
	 */
	private $_errors = array();

	/**
	 * Valitate SP data only flag
	 *
	 * @var bool
	 */
	private $_sp_validation_only = false;

	/**
	 * Initializes the settings:
	 * - Sets the paths of the different folders
	 * - Loads settings info from settings file or array/object provided
	 *
	 * @param array|null $settings         SAML Toolkit Settings.
	 * @param bool       $sp_validation_only Validate or not the IdP data.
	 *
	 * @throws Error If any settings parameter is invalid.
	 */
	public function __construct( array $settings = null, bool $sp_validation_only = false ) {
		$this->_sp_validation_only = $sp_validation_only;
		$this->_loadPaths();

		if ( ! isset( $settings ) ) {
			if ( ! $this->_loadSettingsFromFile() ) {
				throw new Error(
					'Invalid file settings: %s',
					Error::SETTINGS_INVALID,
					array( implode( ', ', $this->_errors ) )
				);
			}
			$this->_addDefaultValues();
		} else {
			if ( ! $this->_loadSettingsFromArray( $settings ) ) {
				throw new Error(
					'Invalid array settings: %s',
					Error::SETTINGS_INVALID,
					array( implode( ', ', $this->_errors ) )
				);
			}
		}

		$this->formatIdPCert();
		$this->formatSPCert();
		$this->formatSPKey();
		$this->formatSPCertNew();
		$this->formatIdPCertMulti();
	}

	/**
	 * Sets the paths of the different folders
	 *
	 * @suppress PhanUndeclaredConstant
	 */
	private function _loadPaths() {
        $base_path    = dirname( dirname( __DIR__ ) ) . '/';
		$this->_paths = array(
			'base'   => $base_path,
			'config' => $base_path,
			'cert'   => $base_path . 'certs/',
			'lib'    => __DIR__ . '/',
		);

		if ( defined( 'ONELOGIN_CUSTOMPATH' ) ) {
			$this->_paths['config'] = ONELOGIN_CUSTOMPATH;
			$this->_paths['cert']   = ONELOGIN_CUSTOMPATH . 'certs/';
		}
	}

	/**
	 * Returns base path.
	 *
	 * @return string  The base toolkit folder path
	 */
	public function getBasePath() {
		return $this->_paths['base'];
	}
	
	/**
	 * Returns cert path.
	 *
	 * @return string The cert folder path
	 */
	public function getCertPath() {
		return $this->_paths['cert'];
	}

	/**
	 * Returns config path.
	 *
	 * @return string The config folder path
	 */
	public function getConfigPath(): string {
		return $this->_paths['config'];
	}

	/**
	 * Returns lib path.
	 *
	 * @return string The library folder path
	 */
	public function getLibPath(): string {
		return $this->_paths['lib'];
	}

	/**
	 * Returns schema path.
	 *
	 * @return string  The external library folder path
	 */
	public function getSchemasPath(): string {
		if ( isset( $this->_paths['schemas'] ) ) {
			return $this->_paths['schemas'];
		}
		return __DIR__ . '/schemas/';
	}

	/**
	 * Set schemas path
	 *
	 * @param  string $path path of Schemas.
	 * @return void 
	 */
	public function setSchemasPath( $path ): void {
		$this->_paths['schemas'] = $path;
	}

	/**
	 * Loads settings info from a settings Array.
	 *
	 * @param array $settings SAML Toolkit Settings.
	 *
	 * @return bool True if the settings info is valid.
	 */
	private function _loadSettingsFromArray( array $settings ) {
		if ( isset( $settings['sp'] ) ) {
			$this->_sp = $settings['sp'];
		}
		if ( isset( $settings['idp'] ) ) {
			$this->_idp = $settings['idp'];
		}

		$errors = $this->checkSettings( $settings );
		if ( empty( $errors ) ) {
			$this->_errors = array();

			if ( isset( $settings['strict'] ) ) {
				$this->_strict = $settings['strict'];
			}
			if ( isset( $settings['debug'] ) ) {
				$this->_debug = $settings['debug'];
			}

			if ( isset( $settings['baseurl'] ) ) {
				$this->_baseurl = $settings['baseurl'];
			}

			if ( isset( $settings['compress'] ) ) {
				$this->_compress = $settings['compress'];
			}

			if ( isset( $settings['security'] ) ) {
				$this->_security = $settings['security'];
			}

			if ( isset( $settings['contactPerson'] ) ) {
				$this->_contacts = $settings['contactPerson'];
			}

			if ( isset( $settings['organization'] ) ) {
				$this->_organization = $settings['organization'];
			}

			$this->_addDefaultValues();
			return true;
		} else {
			$this->_errors = $errors;
			return false;
		}
	}

	/**
	 * Loads settings info from the settings file.
	 *
	 * @return bool True if the settings info is valid.
	 *
	 * @throws Error File doesn't exist.
	 *
	 * @suppress PhanUndeclaredVariable.
	 */
	private function _loadSettingsFromFile() {
		$filename = $this->getConfigPath() . 'settings.php';

		if ( ! file_exists( $filename ) ) {
			throw new Error(
				'Settings file not found: %s',
				Error::SETTINGS_FILE_NOT_FOUND,
				array( $filename )
			);
		}

		include $filename;

		// Add advance_settings if exists.
		$advanced_filename = $this->getConfigPath() . 'advanced_settings.php';

		if ( file_exists( $advanced_filename ) ) {
			include $advanced_filename;
			$settings = array_merge( $settings, $advanced_settings );
		}


		return $this->_loadSettingsFromArray( $settings );
	}

		/**
		 * Add default values if the settings info is not complete.
		 */
	private function _addDefaultValues() {
		if ( ! isset( $this->_sp['assertionConsumerService']['binding'] ) ) {
			$this->_sp['assertionConsumerService']['binding'] = Constants::BINDING_HTTP_POST;
		}
		if ( isset( $this->_sp['singleLogoutService'] ) && ! isset( $this->_sp['singleLogoutService']['binding'] ) ) {
			$this->_sp['singleLogoutService']['binding'] = Constants::BINDING_HTTP_REDIRECT;
		}

		if ( ! isset( $this->_compress['requests'] ) ) {
			$this->_compress['requests'] = true;
		}

		if ( ! isset( $this->_compress['responses'] ) ) {
			$this->_compress['responses'] = true;
		}

		// Related to nameID.
		if ( ! isset( $this->_sp['NameIDFormat'] ) ) {
			$this->_sp['NameIDFormat'] = Constants::NAMEID_UNSPECIFIED;
		}
		if ( ! isset( $this->_security['nameIdEncrypted'] ) ) {
			$this->_security['nameIdEncrypted'] = false;
		}
		if ( ! isset( $this->_security['requestedAuthnContext'] ) ) {
			$this->_security['requestedAuthnContext'] = true;
		}

		// sign provided.
		if ( ! isset( $this->_security['authnRequestsSigned'] ) ) {
			$this->_security['authnRequestsSigned'] = false;
		}
		if ( ! isset( $this->_security['logoutRequestSigned'] ) ) {
			$this->_security['logoutRequestSigned'] = false;
		}
		if ( ! isset( $this->_security['logoutResponseSigned'] ) ) {
			$this->_security['logoutResponseSigned'] = false;
		}
		if ( ! isset( $this->_security['signMetadata'] ) ) {
			$this->_security['signMetadata'] = false;
		}

		// sign expected.
		if ( ! isset( $this->_security['wantMessagesSigned'] ) ) {
			$this->_security['wantMessagesSigned'] = false;
		}
		if ( ! isset( $this->_security['wantAssertionsSigned'] ) ) {
			$this->_security['wantAssertionsSigned'] = false;
		}

		// NameID element expected.
		if ( ! isset( $this->_security['wantNameId'] ) ) {
			$this->_security['wantNameId'] = true;
		}

		// Relax Destination validation.
		if ( ! isset( $this->_security['relaxDestinationValidation'] ) ) {
			$this->_security['relaxDestinationValidation'] = false;
		}

		// Strict Destination match validation.
		if ( ! isset( $this->_security['destinationStrictlyMatches'] ) ) {
			$this->_security['destinationStrictlyMatches'] = false;
		}

		// Allow duplicated Attribute Names.
		if ( ! isset( $this->_security['allowRepeatAttributeName'] ) ) {
			$this->_security['allowRepeatAttributeName'] = false;
		}

		// InResponseTo.
		if ( ! isset( $this->_security['rejectUnsolicitedResponsesWithInResponseTo'] ) ) {
			$this->_security['rejectUnsolicitedResponsesWithInResponseTo'] = false;
		}

		// encrypt expected.
		if ( ! isset( $this->_security['wantAssertionsEncrypted'] ) ) {
			$this->_security['wantAssertionsEncrypted'] = false;
		}
		if ( ! isset( $this->_security['wantNameIdEncrypted'] ) ) {
			$this->_security['wantNameIdEncrypted'] = false;
		}

		// XML validation.
		if ( ! isset( $this->_security['wantXMLValidation'] ) ) {
			$this->_security['wantXMLValidation'] = true;
		}

		// SignatureAlgorithm.
		if ( ! isset( $this->_security['signature_algorithm'] ) ) {
			$this->_security['signature_algorithm'] = XMLSecurityKey::RSA_SHA256;
		}

		// DigestAlgorithm.
		if ( ! isset( $this->_security['digest_algorithm'] ) ) {
			$this->_security['digest_algorithm'] = XMLSecurityDSig::SHA256;
		}

		// EncryptionAlgorithm.
		if ( ! isset( $this->_security['encryption_algorithm'] ) ) {
			$this->_security['encryption_algorithm'] = XMLSecurityKey::AES128_CBC;
		}

		if ( ! isset( $this->_security['lowercaseUrlencoding'] ) ) {
			$this->_security['lowercaseUrlencoding'] = false;
		}

		// Certificates / Private key /Fingerprint.
		if ( ! isset( $this->_idp['x509cert'] ) ) {
			$this->_idp['x509cert'] = '';
		}
		if ( ! isset( $this->_idp['certFingerprint'] ) ) {
			$this->_idp['certFingerprint'] = '';
		}
		if ( ! isset( $this->_idp['certFingerprintAlgorithm'] ) ) {
			$this->_idp['certFingerprintAlgorithm'] = 'sha1';
		}

		if ( ! isset( $this->_sp['x509cert'] ) ) {
			$this->_sp['x509cert'] = '';
		}
		if ( ! isset( $this->_sp['privateKey'] ) ) {
			$this->_sp['privateKey'] = '';
		}
	}

	/**
	 * Checks the settings info.
	 *
	 * @param array $settings Array with settings data.
	 *
	 * @return array $errors  Errors found on the settings data.
	 */
	public function checkSettings( array $settings ) {
		if ( empty( $settings ) ) {
			$errors = array( 'invalid_syntax' );
		} else {
			$errors = array();
			if ( ! $this->_sp_validation_only ) {
				$idp_errors = $this->checkIdPSettings( $settings );
				$errors     = array_merge( $idp_errors, $errors );
			}
			$sp_errors = $this->checkSPSettings( $settings );
			$errors    = array_merge( $sp_errors, $errors );

			$compress_errors = $this->checkCompressionSettings( $settings );
			$errors          = array_merge( $compress_errors, $errors );
		}

		return $errors;
	}

	/**
	 * Checks the compression settings info.
	 *
	 * @param array $settings Array with settings data.
	 *
	 * @return array $errors  Errors found on the settings data.
	 */
	public function checkCompressionSettings( $settings ) {
		$errors = array();

		if ( isset( $settings['compress'] ) ) {
			if ( ! is_array( $settings['compress'] ) ) {
				$errors[] = 'invalid_syntax';
			} elseif ( isset( $settings['compress']['requests'] )
				&& true !== $settings['compress']['requests'] 
				&& false !== $settings['compress']['requests'] 
			) {
				$errors[] = "'compress'=>'requests' values must be true or false.";
			} elseif ( isset( $settings['compress']['responses'] )
				&& true !== $$settings['compress']['responses'] 
				&& false !== $$settings['compress']['responses'] 
			) {
				$errors[] = "'compress'=>'responses' values must be true or false.";
			}
		}
		return $errors;
	}

	/**
	 * Checks the IdP settings info.
	 *
	 * @param array $settings Array with settings data.
	 *
	 * @return array $errors  Errors found on the IdP settings data.
	 */
	public function checkIdPSettings( array $settings ) {
		if ( empty( $settings ) ) {
			return array( 'invalid_syntax' );
		}

		$errors = array();

		if ( ! isset( $settings['idp'] ) || empty( $settings['idp'] ) ) {
			$errors[] = 'idp_not_found';
		} else {
			$idp = $settings['idp'];
			if ( ! isset( $idp['entityId'] ) || empty( $idp['entityId'] ) ) {
				$errors[] = 'idp_entityId_not_found';
			}

			if ( ! isset( $idp['singleSignOnService'] )
				|| ! isset( $idp['singleSignOnService']['url'] )
				|| empty( $idp['singleSignOnService']['url'] )
			) {
				$errors[] = 'idp_sso_not_found';
			} elseif ( ! filter_var( $idp['singleSignOnService']['url'], FILTER_VALIDATE_URL ) ) {
				$errors[] = 'idp_sso_url_invalid';
			}

			if ( isset( $idp['singleLogoutService'] )
				&& isset( $idp['singleLogoutService']['url'] )
				&& ! empty( $idp['singleLogoutService']['url'] )
				&& ! filter_var( $idp['singleLogoutService']['url'], FILTER_VALIDATE_URL )
			) {
				$errors[] = 'idp_slo_url_invalid';
			}

			if ( isset( $idp['singleLogoutService'] )
				&& isset( $idp['singleLogoutService']['responseUrl'] )
				&& ! empty( $idp['singleLogoutService']['responseUrl'] )
				&& ! filter_var( $idp['singleLogoutService']['responseUrl'], FILTER_VALIDATE_URL )
			) {
				$errors[] = 'idp_slo_response_url_invalid';
			}

			$exists_x509            = isset( $idp['x509cert'] ) && ! empty( $idp['x509cert'] );
			$exists_multi_x509_sign = isset( $idp['x509certMulti'] ) && isset( $idp['x509certMulti']['signing'] ) && ! empty( $idp['x509certMulti']['signing'] );
			$exists_fingerprint     = isset( $idp['certFingerprint'] ) && ! empty( $idp['certFingerprint'] );
			if ( ! ( $exists_x509 || $exists_fingerprint || $exists_multi_x509_sign ) ) {
				$errors[] = 'idp_cert_or_fingerprint_not_found_and_required';
			}

			if ( isset( $settings['security'] ) ) {
				$exists_multi_x509_enc = isset( $idp['x509certMulti'] ) && isset( $idp['x509certMulti']['encryption'] ) && ! empty( $idp['x509certMulti']['encryption'] );

				if ( true == ( isset( $settings['security']['nameIdEncrypted'] ) && $settings['security']['nameIdEncrypted'] && ! ( $exists_x509 || $exists_multi_x509_enc ) ) ) {
					$errors[] = 'idp_cert_not_found_and_required';
				}
			}
		}

		return $errors;
	}

	/**
	 * Checks the SP settings info.
	 *
	 * @param array $settings Array with settings data.
	 *
	 * @return array $errors  Errors found on the SP settings data.
	 */
	public function checkSPSettings( array $settings ) {
		if ( empty( $settings ) ) {
			return array( 'invalid_syntax' );
		}

		$errors = array();

		if ( ! isset( $settings['sp'] ) || empty( $settings['sp'] ) ) {
			$errors[] = 'sp_not_found';
		} else {
			$sp       = $settings['sp'];
			$security = array();
			if ( isset( $settings['security'] ) ) {
				$security = $settings['security'];
			}

			if ( ! isset( $sp['entityId'] ) || empty( $sp['entityId'] ) ) {
				$errors[] = 'sp_entityId_not_found';
			}

			if ( ! isset( $sp['assertionConsumerService'] )
				|| ! isset( $sp['assertionConsumerService']['url'] )
				|| empty( $sp['assertionConsumerService']['url'] )
			) {
				$errors[] = 'sp_acs_not_found';
			} elseif ( ! filter_var( $sp['assertionConsumerService']['url'], FILTER_VALIDATE_URL ) ) {
				$errors[] = 'sp_acs_url_invalid';
			}

			if ( isset( $sp['singleLogoutService'] )
				&& isset( $sp['singleLogoutService']['url'] )
				&& ! filter_var( $sp['singleLogoutService']['url'], FILTER_VALIDATE_URL )
			) {
				$errors[] = 'sp_sls_url_invalid';
			}

			if ( isset( $security['signMetadata'] ) && is_array( $security['signMetadata'] ) ) {
				if ( ( ! isset( $security['signMetadata']['key_file_name'] )
					|| ! isset( $security['signMetadata']['cert_file_name'] ) ) 
					&& ( ! isset( $security['signMetadata']['privateKey'] )
					|| ! isset( $security['signMetadata']['x509cert'] ) )
				) {
					$errors[] = 'sp_signMetadata_invalid';
				}
			}

			if ( ( ( true == isset( $security['authnRequestsSigned'] ) && $security['authnRequestsSigned'] )
				|| ( true == isset( $security['logoutRequestSigned'] ) && $security['logoutRequestSigned'] )
				|| ( true == isset( $security['logoutResponseSigned'] ) && $security['logoutResponseSigned'] )
				|| ( true == isset( $security['wantAssertionsEncrypted'] ) && $security['wantAssertionsEncrypted'] )
				|| ( true == isset( $security['wantNameIdEncrypted'] ) && $security['wantNameIdEncrypted'] ) )
				&& ! $this->checkSPCerts()
			) {
				$errors[] = 'sp_certs_not_found_and_required';
			}
		}

		if ( isset( $settings['contactPerson'] ) ) {
			$types       = array_keys( $settings['contactPerson'] );
			$valid_types = array( 'technical', 'support', 'administrative', 'billing', 'other' );
			foreach ( $types as $type ) {
				if ( ! in_array( $type, $valid_types ) ) {
					$errors[] = 'contact_type_invalid';
					break;
				}
			}

			foreach ( $settings['contactPerson'] as $type => $contact ) {
				if ( ! isset( $contact['givenName'] ) || empty( $contact['givenName'] )
					|| ! isset( $contact['emailAddress'] ) || empty( $contact['emailAddress'] )
				) {
					$errors[] = 'contact_not_enought_data';
					break;
				}
			}
		}

		if ( isset( $settings['organization'] ) ) {
			foreach ( $settings['organization'] as $organization ) {
				if ( ! isset( $organization['name'] ) || empty( $organization['name'] )
					|| ! isset( $organization['displayname'] ) || empty( $organization['displayname'] )
					|| ! isset( $organization['url'] ) || empty( $organization['url'] )
				) {
					$errors[] = 'organization_not_enought_data';
					break;
				}
			}
		}

		return $errors;
	}

	/**
	 * Checks if the x509 certs of the SP exists and are valid.
	 *
	 * @return bool
	 */
	public function checkSPCerts() {
        $key  = $this->getSPkey();
		$cert = $this->getSPcert();
		return ( ! empty( $key ) && ! empty( $cert ) );
	}

	/**
	 * Returns the x509 private key of the SP.
	 *
	 * @return string SP private key.
	 */
	public function getSPkey() {
        $key = null;
		if ( isset( $this->_sp['privateKey'] ) && ! empty( $this->_sp['privateKey'] ) ) {
			$key = $this->_sp['privateKey'];
		} else {
			$key_file = $this->_paths['cert'] . 'sp.key';

			if ( file_exists( $key_file ) ) {
				$key = wpcom_vip_file_get_contents( $key_file );
			}
		}
		return $key;
	}

	/**
	 * Returns the x509 public cert of the SP.
	 *
	 * @return string SP public cert
	 */
	public function getSPcert() {
		$cert = null;

		if ( isset( $this->_sp['x509cert'] ) && ! empty( $this->_sp['x509cert'] ) ) {
			$cert = $this->_sp['x509cert'];
		} else {
			$cert_file = $this->_paths['cert'] . 'sp.crt';

			if ( file_exists( $cert_file ) ) {
				$cert = wpcom_vip_file_get_contents( $cert_file );
			}
		}
		return $cert;
	}

	/**
	 * Returns the x509 public of the SP that is.
	 * planed to be used soon instead the other.
	 * public cert.
	 *
	 * @return string SP public cert New.
	 */
	public function getSPcertNew() {
        $cert = null;

		if ( isset( $this->_sp['x509cert_new'] ) && ! empty( $this->_sp['x509cert_new'] ) ) {
			$cert = $this->_sp['x509cert_new'];
		} else {
			$cert_file = $this->_paths['cert'] . 'sp_new.crt';

			if ( file_exists( $cert_file ) ) {
				$cert = wpcom_vip_file_get_contents( $cert_file );
			}
		}
		return $cert;
	}

	/**
	 * Gets the IdP data.
	 *
	 * @return array  IdP info.
	 */
	public function getIdPData() {
		return $this->_idp;
	}

	/**
	 * Gets the SP data.
	 *
	 * @return array  SP info.
	 */
	public function getSPData() {
		return $this->_sp;
	}

	/**
	 * Gets security data.
	 *
	 * @return array  SP info.
	 */
	public function getSecurityData() {
        return $this->_security;
	}

	/**
	 * Gets contact data.
	 *
	 * @return array  SP info.
	 */
	public function getContacts() {
        return $this->_contacts;
	}

	/**
	 * Gets organization data.
	 *
	 * @return array  SP info.
	 */
	public function getOrganization() {
        return $this->_organization;
	}

	/**
	 * Should SAML requests be compressed?.
	 *
	 * @return bool Yes/No as True/False.
	 */
	public function shouldCompressRequests() {
		return $this->_compress['requests'];
	}

	/**
	 * Should SAML responses be compressed?.
	 *
	 * @return bool Yes/No as True/False.
	 */
	public function shouldCompressResponses() {
        return $this->_compress['responses'];
	}

	/**
	 * Gets the IdP SSO url.
	 *
	 * @return string|null The url of the IdP Single Sign On Service.
	 */
	public function getIdPSSOUrl() {
        $sso_url = null;
		if ( isset( $this->_idp['singleSignOnService'] ) && isset( $this->_idp['singleSignOnService']['url'] ) ) {
			$sso_url = $this->_idp['singleSignOnService']['url'];
		}
		return $sso_url;
	}

	/**
	 * Gets the IdP SLO url.
	 *
	 * @return string|null The request url of the IdP Single Logout Service.
	 */
	public function getIdPSLOUrl() {
        $slo_url = null;
		if ( isset( $this->_idp['singleLogoutService'] ) && isset( $this->_idp['singleLogoutService']['url'] ) ) {
			$slo_url = $this->_idp['singleLogoutService']['url'];
		}
		return $slo_url;
	}

	/**
	 * Gets the IdP SLO response url.
	 *
	 * @return string|null The response url of the IdP Single Logout Service.
	 */
	public function getIdPSLOResponseUrl() {
        if ( isset( $this->_idp['singleLogoutService'] ) && isset( $this->_idp['singleLogoutService']['responseUrl'] ) ) {
			return $this->_idp['singleLogoutService']['responseUrl'];
		}
		return $this->getIdPSLOUrl();
	}

	/**
	 * Gets the SP metadata. The XML representation.
	 *
	 * @param bool     $always_publish_encryption_cert When 'true', the returned
	 *                                              metadata will always
	 *                                              include an 'encryption'
	 *                                              KeyDescriptor. Otherwise,
	 *                                              the 'encryption'
	 *                                              KeyDescriptor will only
	 *                                              be included if
	 *                                              $advanced_settings['security']['wantNameIdEncrypted']
	 *                                              or
	 *                                              $advanced_settings['security']['wantAssertionsEncrypted']
	 *                                              are enabled.
	 * @param int|null $valid_until                  Metadata's valid time.i.
	 * @param int|null $cache_duration               Duration of the cache in seconds.
	 *
	 * @return string  SP metadata (xml)
	 * @throws Exception|Error Cert / key error.
	 */
	public function getSPMetadata( $always_publish_encryption_cert = false, $valid_until = null, $cache_duration = null ) {
		$metadata = Metadata::builder( $this->_sp, $this->_security['authnRequestsSigned'], $this->_security['wantAssertionsSigned'], $valid_until, $cache_duration, $this->getContacts(), $this->getOrganization() );

		$cert_new = $this->getSPcertNew();
		if ( ! empty( $cert_new ) ) {
			$metadata = Metadata::addX509KeyDescriptors(
				$metadata,
				$cert_new,
				$always_publish_encryption_cert || $this->_security['wantNameIdEncrypted'] || $this->_security['wantAssertionsEncrypted']
			);
		}

		$cert = $this->getSPcert();
		if ( ! empty( $cert ) ) {
			$metadata = Metadata::addX509KeyDescriptors(
				$metadata,
				$cert,
				$always_publish_encryption_cert || $this->_security['wantNameIdEncrypted'] || $this->_security['wantAssertionsEncrypted']
			);
		}

		// Sign Metadata.
		if ( false !== ( isset( $this->_security['signMetadata'] ) && $this->_security['signMetadata'] ) ) {
			if ( true === $this->_security['signMetadata'] ) {
				$key_meta_data  = $this->getSPkey();
				$cert_meta_data = $cert;

				if ( ! $key_meta_data ) {
					throw new Error(
						'SP Private key not found.',
						Error::PRIVATE_KEY_FILE_NOT_FOUND
					);
				}

				if ( ! $cert_meta_data ) {
					throw new Error(
						'SP Public cert not found.',
						Error::PUBLIC_CERT_FILE_NOT_FOUND
					);
				}
			} elseif ( isset( $this->_security['signMetadata']['key_file_name'] ) 
				&& isset( $this->_security['signMetadata']['cert_file_name'] )
			) {
				$key_file_name  = $this->_security['signMetadata']['key_file_name'];
				$cert_file_name = $this->_security['signMetadata']['cert_file_name'];

				$key_metadata_file  = $this->_paths['cert'] . $key_file_name;
				$cert_metadata_file = $this->_paths['cert'] . $cert_file_name;

				if ( ! file_exists( $key_metadata_file ) ) {
					throw new Error(
						'SP Private key file not found: %s',
						Error::PRIVATE_KEY_FILE_NOT_FOUND,
						array( $key_metadata_file )
					);
				}

				if ( ! file_exists( $cert_metadata_file ) ) {
					throw new Error(
						'SP Public cert file not found: %s',
						Error::PUBLIC_CERT_FILE_NOT_FOUND,
						array( $cert_metadata_file )
					);
				}
				$key_meta_data  = wpcom_vip_file_get_contents( $key_metadata_file );
				$cert_meta_data = wpcom_vip_file_get_contents( $cert_metadata_file );
			} elseif ( isset( $this->_security['signMetadata']['privateKey'] ) 
				&& isset( $this->_security['signMetadata']['x509cert'] )
			) {
				$key_meta_data  = Utils::formatPrivateKey( $this->_security['signMetadata']['privateKey'] );
				$cert_meta_data = Utils::formatCert( $this->_security['signMetadata']['x509cert'] );
				if ( ! $key_meta_data ) {
					throw new Error(
						'Private key not found.',
						Error::PRIVATE_KEY_FILE_NOT_FOUND
					);
				}

				if ( ! $cert_meta_data ) {
					throw new Error(
						'Public cert not found.',
						Error::PUBLIC_CERT_FILE_NOT_FOUND
					);
				}
			} else {
				throw new Error(
					'Invalid Setting: signMetadata value of the sp is not valid',
					Error::SETTINGS_INVALID_SYNTAX
				);

			}

			$signature_algorithm = $this->_security['signature_algorithm'];
			$digest_algorithm    = $this->_security['digest_algorithm'];
			$metadata            = Metadata::signMetadata( $metadata, $key_meta_data, $cert_meta_data, $signature_algorithm, $digest_algorithm );
		}
		return $metadata;
	}

	/**
	 * Validates an XML SP Metadata.
	 *
	 * @param string $xml Metadata's XML that will be validate.
	 *
	 * @return array The list of found errors.
	 *
	 * @throws Exception Exception on validation of XML.
	 */
	public function validateMetadata( $xml ) {
		assert( is_string( $xml ) );

		$errors = array();
		$res    = Utils::validateXML( $xml, 'saml-schema-metadata-2.0.xsd', $this->_debug, $this->getSchemasPath() );
		if ( ! $res instanceof DOMDocument ) {
			$errors[] = $res;
		} else {
			$dom     = $res;
			$element = $dom->documentElement; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( 'md:EntityDescriptor' !== $element->tagName ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$errors[] = 'noEntityDescriptor_xml';
			} else {
				$valid_until = $cache_duration;
				$expire_time = null;

				if ( $element->hasAttribute( 'valid_until' ) ) {
					$valid_until = Utils::parseSAML2Time( $element->getAttribute( 'valid_until' ) );
				}
				if ( $element->hasAttribute( 'cache_duration' ) ) {
					$cache_duration = $element->getAttribute( 'cache_duration' );
				}

				$expire_time = Utils::getExpireTime( $cache_duration, $valid_until );
				if ( isset( $expire_time ) && time() > $expire_time ) {
					$errors[] = 'expired_xml';
				}
			}
		}

		// TODO: Support Metadata Sign Validation.

		return $errors;
	}

	/**
	 * Formats the IdP cert.
	 */
	public function formatIdPCert(): void {
		if ( isset( $this->_idp['x509cert'] ) ) {
			$this->_idp['x509cert'] = Utils::formatCert( $this->_idp['x509cert'] );
		}
	}

	/**
	 * Formats the Multple IdP certs.
	 */
	public function formatIdPCertMulti(): void {
		if ( isset( $this->_idp['x509certMulti'] ) ) {
			if ( isset( $this->_idp['x509certMulti']['signing'] ) ) {
				foreach ( $this->_idp['x509certMulti']['signing'] as $i => $cert ) {
					$this->_idp['x509certMulti']['signing'][ $i ] = Utils::formatCert( $cert );
				}
			}
			if ( isset( $this->_idp['x509certMulti']['encryption'] ) ) {
				foreach ( $this->_idp['x509certMulti']['encryption'] as $i => $cert ) {
					$this->_idp['x509certMulti']['encryption'][ $i ] = Utils::formatCert( $cert );
				}
			}
		}
	}

	/**
	 * Formats the SP cert.
	 */
	public function formatSPCert(): void {
        if ( isset( $this->_sp['x509cert'] ) ) {
			$this->_sp['x509cert'] = Utils::formatCert( $this->_sp['x509cert'] );
		}
	}

	/**
	 * Formats the SP cert.
	 */
	public function formatSPCertNew(): void {
		if ( isset( $this->_sp['x509cert_new'] ) ) {
			$this->_sp['x509cert_new'] = Utils::formatCert( $this->_sp['x509cert_new'] );
		}
	}

	/**
	 * Formats the SP private key.
	 */
	public function formatSPKey(): void {
		if ( isset( $this->_sp['privateKey'] ) ) {
			$this->_sp['privateKey'] = Utils::formatPrivateKey( $this->_sp['privateKey'] );
		}
	}

	/**
	 * Returns an array with the errors, the array is empty when the settings is ok.
	 *
	 * @return array Errors
	 */
	public function getErrors(): array {
		return $this->_errors;
	}

	/**
	 * Activates or deactivates the strict mode.
	 *
	 * @param bool $value Strict parameter.
	 *
	 * @throws Exception Throws invalid value if not boolean $value.
	 */
	public function setStrict( $value ): void {
		if ( ! is_bool( $value ) ) {
			throw new Exception( 'Invalid value passed to setStrict()' );
		}

		$this->_strict = $value;
	}

	/**
	 * Returns if the 'strict' mode is active.
	 *
	 * @return bool Strict parameter
	 */
	public function isStrict(): boolean {
        return $this->_strict;
	}

	/**
	 * Returns if the debug is active.
	 *
	 * @return bool Debug parameter.
	 */
	public function isDebugActive(): boolean {
		return $this->_debug;
	}

	/**
	 * Set a baseurl value.
	 *
	 * @param string $baseurl Base URL.
	 */
	public function setBaseURL( $baseurl ): void {
		$this->_baseurl = $baseurl;
	}

	/**
	 * Returns the baseurl set on the settings if any.
	 *
	 * @return null|string The baseurl.
	 */
	public function getBaseURL(): mixed {
		return $this->_baseurl;
	}

	/**
	 * Sets the IdP certificate.
	 *
	 * @param string $cert IdP certificate.
	 */
	public function setIdPCert( $cert ) {
		$this->_idp['x509cert'] = $cert;
		$this->formatIdPCert();
	}
}
