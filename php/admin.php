<?php

namespace HumanMade\SimpleSaml;

use OneLogin_Saml2_IdPMetadataParser;

/**
 * Bootstrap config/admin related actions
 *
 * @action plugins_loaded
 */
function admin_bootstrap() {
	add_filter( 'wpsimplesaml_config', __NAMESPACE__ . '\\get_config' );
	add_filter( 'wpsimplesaml_idp_metadata_xml', __NAMESPACE__ . '\\get_config_from_db' );
	add_action( 'admin_notices', __NAMESPACE__ . '\\config_admin_notice' );

	add_action( 'admin_init', __NAMESPACE__ . '\\settings_fields' );
	add_action( 'wpmu_options', __NAMESPACE__ . '\\network_settings_fields' );
	add_action( 'update_wpmu_options', __NAMESPACE__ . '\\save_network_settings_fields' );

	add_filter( 'wpsimplesaml_force', __NAMESPACE__ . '\\filter_forced_sso' );
	add_filter( 'wpsimplesaml_manage_roles', __NAMESPACE__ . '\\filter_role_management' );
}

/**
 * Return WP Simple SAML configurations
 *
 * @return array|\WP_Error Site-specific SSO settings, or WP error if an exception happens while processing the XML
 */
function get_config() {
	// Only one authority can be registered in IdP, allow overriding this via settings
	$sp_home_url = get_sso_settings( 'sso_sp_base' );
	$sp_base_url = trailingslashit( $sp_home_url ) . 'sso/';
	$settings    = [];

	try {
		/**
		 * Filters the XML metadata file for IdP authority
		 *
		 * @return string Location of the metadata XML file
		 */
		$idp_xml_file = apply_filters( 'wpsimplesaml_idp_metadata_xml_path', '' );

		if ( $idp_xml_file && file_exists( $idp_xml_file ) ) {
			$settings = OneLogin_Saml2_IdPMetadataParser::parseFileXML( $idp_xml_file );
		}

		if ( empty( $idp_xml_file ) ) {
			/**
			 * Filters the XML metadata for IdP authority
			 *
			 * @return string XML string for IdP metadata
			 */
			$idp_xml  = apply_filters( 'wpsimplesaml_idp_metadata_xml', '' );
			$settings = OneLogin_Saml2_IdPMetadataParser::parseXML( $idp_xml );
		}
	} catch ( \Exception $e ) {
		return new \WP_Error( 'invalid-idp-metadata', __( 'Invalid IdP XML metadata', 'wp-simple-saml' ), [ 'errors' => $e->getMessage() ] );
	}

	/**
	 * Filters the XML metadata array for IdP authority
	 *
	 * @return array Location of the metadata XML file
	 */
	$settings = apply_filters( 'wpsimplesaml_idp_metadata', $settings );

	if ( empty( $settings ) ) {
		return [];
	}

	$settings['sp'] = [
		'entityId'                 => $sp_home_url,
		'assertionConsumerService' => [
			'url' => $sp_base_url . 'verify',
		],
		'singleLogoutService'      => [
			'url' => $sp_base_url . 'sls',
		],
		'NameIDFormat'             => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
	];

	return $settings;
}

/**
 * Retrieves IdP metadata from network options, if specified
 *
 * @param string $default
 *
 * @return array|string
 */
function get_config_from_db( $default ) {
	$value = get_sso_settings( 'sso_idp_metadata' );

	return $value ? $value : $default;
}

/**
 * Check for invalid settings, missing files, and plugin activation
 *
 * @action admin_notices
 */
function config_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$config = get_config();
	if ( empty( $config ) ) {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'WP Simple SAML is activated but not configured, See the docs for configuration instructions.', 'wp-simple-saml' )
		);
	}

	if ( get_sso_settings( 'sso_debug' ) ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'WP Simple SAML debug mode is activated.', 'wp-simple-saml' )
		);
	}
}

/**
 * Get SSO settings from options
 *
 * @param null $option Return a specific option value instead of the whole array
 *
 * @return array|string All options, or a specific option value if $option was passed
 */
function get_sso_settings( $option = null ) {
	$options = [
		'sso_enabled'         => '',
		'sso_debug'           => 0,
		'sso_sp_base'         => is_sso_enabled_network_wide() ? get_blog_details( 'home', get_network()->site_id ) : home_url(),
		'sso_role_management' => '',
		'sso_idp_metadata'    => '',
	];

	// Network options is used instead if the plugin is activated network-wide
	if ( is_sso_enabled_network_wide() ) {
		$options = array_combine( array_keys( $options ), array_map( 'get_site_option', array_keys( $options ), array_values( $options ) ) );
	} else {
		$options = array_combine( array_keys( $options ), array_map( 'get_option', array_keys( $options ), array_values( $options ) ) );
	}

	return null === $option ? $options : ( isset( $options[ $option ] ) ? $options[ $option ] : null );
}

/**
 * Admin settings to enable/disable SSO. This is defined to be used by either the network settings, or site settings,
 * depending on whether the plugin is network activated or not
 *
 * @action admin_init
 */
function settings_fields() {

	$options = get_sso_settings();

	// Network options is used instead if the plugin is activated network-wide
	if ( is_sso_enabled_network_wide() ) {
		$settings_section = 'network_sso_settings';
	} else {
		$settings_section = 'general';
	}

	add_settings_section(
		'sso_settings',
		__( 'SSO Configuration', 'wp-simple-saml' ),
		'__return_false',
		$settings_section
	);

	register_setting( $settings_section, 'sso_enabled', 'sanitize_text' );
	add_settings_field( 'sso_enabled', __( 'SSO Status', 'wp-simple-saml' ), function () use ( $options ) {
		$value   = $options['sso_enabled'];
		$options = [
			''      => esc_html__( 'Disabled', 'wp-simple-saml' ),
			'link'  => esc_html__( 'Display log-in link', 'wp-simple-saml' ),
			'force' => esc_html__( 'Force Redirect', 'wp-simple-saml' ),
		];
		?>
		<select name="sso_enabled" id="sso_enabled">
			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
					<?php echo esc_html( $option_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}, $settings_section, 'sso_settings' );

	register_setting( $settings_section, 'sso_debug', 'absint' );
	add_settings_field( 'sso_debug', __( 'SSO Debug via Cookies', 'wp-simple-saml' ), function () use ( $options ) {
		$value = $options['sso_debug'];
		?>
		<input type="checkbox" name="sso_debug" id="sso_debug" value="1" <?php checked( $value ); ?>>
		<?php
	}, $settings_section, 'sso_settings' );

	register_setting( $settings_section, 'sso_sp_base', 'sanitize_url' );
	add_settings_field( 'sso_sp_base', __( 'SSO Base URL', 'wp-simple-saml' ), function () use ( $options ) {
		$value = $options['sso_sp_base'];
		?>
		<input type="text" name="sso_sp_base" id="sso_sp_base" value="<?php echo esc_url_raw( $value ); ?>">
		<?php
	}, $settings_section, 'sso_settings' );

	register_setting( $settings_section, 'sso_role_management', 'sanitize_text' );
	add_settings_field( 'sso_role_management', __( 'SSO Role Management', 'wp-simple-saml' ), function () use ( $options ) {
		$value   = $options['sso_role_management'];
		$choices = [
			''        => esc_html__( 'Disabled', 'wp-simple-saml' ),
			'enabled' => esc_html__( 'Enabled, fails gracefully', 'wp-simple-saml' ),
			'forced'  => esc_html__( 'Enabled, forced', 'wp-simple-saml' ),
		];
		?>
		<select name="sso_role_management" id="sso_role_management">
			<?php foreach ( $choices as $option_value => $option_label ) : ?>
				<option
					value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}, $settings_section, 'sso_settings' );

	register_setting( $settings_section, 'sso_idp_metadata', 'sanitize_text' );
	add_settings_field( 'sso_idp_metadata', __( 'SSO IdP Metadata', 'wp-simple-saml' ), function () use ( $options ) {
		remove_filter( 'wpsimplesaml_idp_metadata_xml', __NAMESPACE__ . '\\get_config_from_db' );
		$xml = apply_filters( 'wpsimplesaml_idp_metadata_xml_path', '' ) || apply_filters( 'wpsimplesaml_idp_metadata_xml_path', '' );
		add_filter( 'wpsimplesaml_idp_metadata_xml', __NAMESPACE__ . '\\get_config_from_db' );
		if ( $xml ) {
			esc_html_e( 'Managed via code', 'wp-simple-saml' );
		} else {
			$value = $options['sso_idp_metadata'];
			?>
			<textarea name="sso_idp_metadata" id="sso_idp_metadata" style="width: 100%; height: 200px" <?php disabled( (bool) $xml ); ?>><?php echo esc_html( $value ); ?></textarea>
			<?php
		}
	}, $settings_section, 'sso_settings' );

	add_settings_field( 'sso_config_validate', __( 'SSO Config validation', 'wp-simple-saml' ), function() {
		$path     = apply_filters( 'wpsimplesaml_idp_metadata_xml_path', '' );
		if ( $path ) {
			$xml = true;
		} else {
			remove_filter( 'wpsimplesaml_idp_metadata_xml', __NAMESPACE__ . '\\get_config_from_db' );
			$xml = apply_filters( 'wpsimplesaml_idp_metadata_xml', '' );
			add_filter( 'wpsimplesaml_idp_metadata_xml', __NAMESPACE__ . '\\get_config_from_db' );
		}
		$config   = apply_filters( 'wpsimplesaml_config', [] );
		$instance = instance();
		$errors   = $instance ? $instance->getErrors() : null;

		printf(
			'<strong>%s</strong>: %s',
			esc_html( 'XML Path' ),
			$path ? esc_html( $path ) : esc_html( 'No' )
		);

		printf(
			'<br/><strong>%s</strong>: %s',
			esc_html( 'XML' ),
			$xml ? esc_html( 'Yes' ) : esc_html( 'No' )
		);

		printf(
			'<br/><strong>%s</strong>: %s',
			esc_html( 'Passed config' ),
			( ! empty( $config ) && ! is_wp_error( $config ) ) ? esc_html( 'Yes' ) : esc_html( 'No' )
		);

		printf(
			'<br/><strong>%s</strong>: %s',
			esc_html( 'Valid config' ),
			( $config && $instance ) ? esc_html( 'Yes' ) : ( is_wp_error( $config ) ? $config->get_error_message() : esc_html( 'No' ) ) // WPCS: @codingStandardsIgnoreLine
		);

		printf(
			'<br/><strong>%s</strong>: %s',
			esc_html( 'Errors' ),
			$errors ? sprintf( '<br/><code>%s</code>', wp_json_encode( $errors ) ) : esc_html( 'No' )
		);
	}, $settings_section, 'sso_settings' );
}

/**
 * Show network settings fields
 *
 * @action wpmu_options
 */
function network_settings_fields() {
	if ( ! is_sso_enabled_network_wide() ) {
		return;
	}

	do_settings_sections( 'network_sso_settings' );
	wp_nonce_field( 'network_sso_options', 'network_sso_options_nonce' );
}

/**
 * Save network settings
 *
 * @action update_wpmu_options
 */
function save_network_settings_fields() {
	$nonce = isset( $_POST['network_sso_options_nonce'] ) ? wp_unslash( $_POST['network_sso_options_nonce'] ) : null; // @codingStandardsIgnoreLine

	if ( ! wp_verify_nonce( $nonce, 'network_sso_options' ) ) {
		return;
	}

	if ( isset( $_POST['sso_enabled'] ) ) { // WPCS input var ok
		update_site_option( 'sso_enabled', sanitize_text_field( wp_unslash( $_POST['sso_enabled'] ) ) ); // WPCS input var ok
	}

	if ( isset( $_POST['sso_debug'] ) ) { // WPCS input var ok
		update_site_option( 'sso_debug', absint( $_POST['sso_debug'] ) ); // WPCS input var ok
	} else {
		delete_site_option( 'sso_debug' ); // WPCS input var ok
	}

	if ( isset( $_POST['sso_sp_base'] ) ) { // WPCS input var ok
		update_site_option( 'sso_sp_base', esc_url_raw( wp_unslash( $_POST['sso_sp_base'] ) ) ); // WPCS input var ok
	}

	if ( isset( $_POST['sso_role_management'] ) ) { // WPCS input var ok
		update_site_option( 'sso_role_management', sanitize_text_field( wp_unslash( $_POST['sso_role_management'] ) ) ); // WPCS input var ok
	}

	if ( isset( $_POST['sso_idp_metadata'] ) ) { // WPCS input var ok
		update_site_option( 'sso_idp_metadata', wp_unslash( $_POST['sso_idp_metadata'] ) ); // WPCS input var ok
	}
}

/**
 * Activate/deactivate SSO based on configuration.
 *
 * @filter wpsimplesaml_force
 *
 * @return bool Whether to ignore SSO.
 */
function filter_forced_sso() {
	if ( get_sso_settings( 'sso_debug' ) && isset( $_COOKIE['sso_debug'] ) ) {
		$force = 'force' !== wp_unslash( $_COOKIE['sso_debug'] );
	} elseif ( ( defined( 'A8C_PROXIED_REQUEST' ) && A8C_PROXIED_REQUEST ) || ( defined( 'HM_IS_PROXIED' ) && HM_IS_PROXIED ) ) {
		$force = false;
	} else {
		$force = 'force' === get_sso_settings( 'sso_enabled' );
	}

	return $force;
}

/**
 * Enable or disable role management based on settings
 *
 * @filter wpsimplesaml_manage_roles
 *
 * @return string
 */
function filter_role_management() {
	return get_sso_settings( 'sso_role_management' );
}
