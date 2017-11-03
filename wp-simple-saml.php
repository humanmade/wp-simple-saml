<?php
/*
Plugin Name: WP Simple SAML
Description: Integrate SAML 2.0 IDP without the hassle
Author: Shady Sharaf, Human Made
Version: 0.1
Author URI: http://hmn.md
Text Domain: wp-simple-saml
Domain Path: /language/

Copyright 2017 Shady Sharaf, Human Made

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

namespace HumanMade\SimpleSaml;

/**
 * Bootstrap the plugin, adding required actions and filters
 *
 * @action init
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\rewrites' );
	add_action( 'template_redirect', __NAMESPACE__ . '\\endpoint', 9 );
	add_action( 'login_form', __NAMESPACE__ . '\\login_form_link' );
	add_action( 'wp_authenticate', __NAMESPACE__ . '\\authenticate_with_sso' );
	add_action( 'wp_logout', __NAMESPACE__ . '\\go_home' );

	add_action( 'wpsimplesaml_action_login', __NAMESPACE__ . '\\cross_site_sso' );
	add_action( 'wpsimplesaml_action_verify', __NAMESPACE__ . '\\cross_site_sso' );

	add_action( 'wpsimplesaml_action_login', __NAMESPACE__ . '\\action_login' );
	add_action( 'wpsimplesaml_action_verify', __NAMESPACE__ . '\\action_verify' );
	add_action( 'wpsimplesaml_action_metadata', __NAMESPACE__ . '\\action_metadata' );

	add_action( 'wpsimplesaml_user_created', __NAMESPACE__ . '\\map_user_roles' );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

/**
 * Register SSO endpoint
 *
 * @action init
 */
function rewrites() {
	add_rewrite_endpoint( 'sso', EP_ROOT, true );

	// Attempt to flush rewrite rules on plugin activation, not perfect but it should work at least the first time
	if ( ! get_option( 'wpsimplesaml_rr_flushed' ) ) {
		flush_rewrite_rules();
		do_action( 'rri_flush_rules' ); // Proper flushing on VIP environments
		add_option( 'wpsimplesaml_rr_flushed', true );
	}
}

/**
 * SSO Endpoint handler
 *
 * @action template_redirect, 9
 */
function endpoint() {
	global $wp_query;

	// Do not block access to SSO endpoint if blog is not public
	if ( class_exists( 'ds_more_privacy_options' ) ) {
		global $ds_more_privacy_options; // @codingStandardsIgnoreLine
		remove_action( 'template_redirect', [ $ds_more_privacy_options, 'ds_users_authenticator' ] );
	}

	if ( ! isset( $wp_query->query_vars['sso'] ) ) {
		return;
	}

	// If we have no valid instance, bail completely
	if ( ! instance() ) {
		wp_die( esc_html__( 'Invalid SSO settings. Contact your administrator.', 'wp-simple-saml' ) );
	}

	$request = ! empty( $wp_query->query_vars['sso'] ) ? $wp_query->query_vars['sso'] : 'login';
	$action  = current( explode( '/', $request ) );

	do_action( 'wpsimplesaml_action_' . $action );

	// If nothing happens,
	do_action( 'wpsimplesaml_invalid_endpoint', $action );
	// i haz no monies!
	wp_safe_redirect( home_url(), 404 );
	exit;
}

/**
 * Show SSO login link in login form
 *
 * @action login_form
 */
function login_form_link() {

	/**
	 * Filter whether we should show the SSO login link in login form
	 *
	 * @return bool  Forces SSO authentication if true, defaults to True
	 */
	if ( ! apply_filters( 'wpsimplesaml_login_link', true ) ) {
		return;
	}

	$redirect_url = get_redirection_url();

	$output = sprintf(
		'<p><a href="%s" id="login-via-sso">%s</a></p>',
		esc_url( add_query_arg( 'redirect_to', urlencode( $redirect_url ), home_url( 'sso/login/' ) ) ), // @codingStandardsIgnoreLine
		esc_html( apply_filters( 'wpsimplesaml_log_in_text', __( 'Login via SSO', 'wp-simple-saml' ) ) )
	);

	echo $output; // WPCS: xss ok
}

/**
 * Replace WordPress Login
 *
 * @action wp_authenticate
 */
function authenticate_with_sso() {

	/**
	 * Filter whether the plugin should intercepts this request or not, mainly used when SSO is forced
	 *
	 * Used to whitelist IP/etc via a custom function, return true to skip -forced- SSO
	 *
	 * @param bool $ignore Disables SSO handling if true
	 */
	if ( apply_filters( 'wpsimplesaml_ignore', false ) ) {
		return;
	}

	/**
	 * Filter whether the plugin should force SSO redirection
	 *
	 * @param bool $force Forces SSO authentication if true, defaults to True
	 */
	if ( ! apply_filters( 'wpsimplesaml_force', true ) ) {
		return;
	}

	// Bail if no SAML2_Auth instance is available, mainly if no configuration was found
	if ( ! instance() ) {
		return;
	}

	$redirect = get_redirection_url();

	instance()->login( $redirect );
}

/**
 * Go to homepage after logging out, so we don't trigger the login flow again
 *
 * @action wp_logout
 */
function go_home() {
	wp_safe_redirect( home_url() );
	exit;
}

/**
 * Get an instance of SAML2 Auth object
 *
 * @return False|\OneLogin_Saml2_Auth
 */
function instance() {
	static $instance;

	if ( ! empty( $instance ) ) {
		return $instance;
	}

	require_once __DIR__ . '/vendor/onelogin/php-saml/_toolkit_loader.php';

	$config = apply_filters( 'wpsimplesaml_config', [] );

	if ( empty( $config ) ) {
		return false;
	}

	if ( empty( $instance ) ) {
		$instance = new \OneLogin_Saml2_Auth( $config );
	}

	return $instance;
}

/**
 * Pass SAMLResponse to requesting site if the request didn't originate from the main site/SP
 *
 * @action wpsimplesaml_login
 * @action wpsimplesaml_verify
 */
function cross_site_sso() {
	$redirect_url = get_redirection_url();
	if ( isset( $_POST['SAMLResponse'] ) && ( get_current_blog_id() !== get_blog_id( $redirect_url ) ) ) { // @codingStandardsIgnoreLine
		cross_site_sso_redirect( $redirect_url );
	}
}

/**
 * Handle sso/login endpoint
 *
 * @action wpsimplesaml_action_login
 */
function action_login() {
	$redirect_url = get_redirection_url();

	if ( is_user_logged_in() ) {
		wp_safe_redirect( $redirect_url );
		exit;
	}

	instance()->login( $redirect_url );
}

/**
 * Handle verification of SAMLResponse
 *
 * @action wpsimplesaml_action_verify
 */
function action_verify() {
	$redirect_url = get_redirection_url();

	if ( is_user_logged_in() ) {
		wp_safe_redirect( $redirect_url );
	}

	if ( empty( $_POST['SAMLResponse'] ) ) { // @codingStandardsIgnoreLine
		wp_die( esc_html__( 'Invalid request, did not receive a SAMLResponse to parse.', 'wp-simple-saml' ) );
	}

	$user = get_sso_user();
	if ( is_a( $user, 'WP_User' ) ) {
		// Set authentication cookie
		signon( $user );

		wp_safe_redirect( $redirect_url );
	} elseif ( is_wp_error( $user ) ) {
		wp_die( esc_html( $user->get_error_message() ) );
	}
}

/**
 * Output metadata of SP
 *
 * @action wpsimplesaml_action_metadata
 */
function action_metadata() {
	$auth     = instance();
	$settings = $auth->getSettings();
	$metadata = $settings->getSPMetadata();
	$errors   = $settings->validateMetadata( $metadata );
	if ( $errors ) {
		wp_die( esc_html__( 'Invalid SSO SP config, please contact your administrator.', 'wp-simple-saml' ) );
	}

	header( 'Content-Type: text/xml' );
	echo $metadata; // @codingStandardsIgnoreLine
	exit;
}

/**
 * Handle authentication responses
 *
 * @return \WP_User|\WP_Error
 */
function get_sso_user() {
	$saml = instance();

	$saml->processResponse();

	if ( ! empty( $saml->getErrors() ) ) {
		$errors = implode( ', ', $saml->getErrors() );

		/* translators: %s = error message */

		return new \WP_Error( 'invalid-saml', sprintf( esc_html__( 'Error: Could not parse the authentication response, please forward this error to your administrator: "%s"', 'wp-simple-saml' ), esc_html( $errors ) ) );
	}

	if ( ! $saml->isAuthenticated() ) {
		return new \WP_Error( 'not-authenticated', esc_html__( 'Error: Authentication wasn\'t completed successfully.', 'wp-simple-saml' ) );
	}

	// Assumes the email is the unique identifier set in SAML IDP
	$email = filter_var( $saml->getNameId(), FILTER_VALIDATE_EMAIL );

	if ( ! $email ) {
		return new \WP_Error( 'invalid-email', esc_html__( 'Error: Invalid email passed. Contact your administrator.', 'wp-simple-saml' ) );
	}

	return get_or_create_wp_user( $email, $saml->getAttributes() );
}

/**
 * Create a user and/or update his role based on SAML response
 *
 * @param string $email
 * @param array  $attributes
 *
 * @return \WP_User|\WP_Error
 */
function get_or_create_wp_user( $email, array $attributes = [] ) {
	/**
	 * Filters matched user, allows matching via other SAML attributes
	 *
	 * @param string $email      Email from SAMLResponse
	 * @param array  $attributes SAML Attributes parsed from SAMLResponse
	 *
	 * @return false|\WP_User User object or false if not found
	 */
	$user = apply_filters( 'wpsimplesaml_match_user', get_user_by( 'email', $email ), $email, $attributes );

	// No user yet ? lets create a new one.
	if ( empty( $user ) ) {

		$first_name = isset( $attributes['fname'] ) && is_array( $attributes['fname'] ) ? reset( $attributes['fname'] ) : '';
		$last_name  = isset( $attributes['lname'] ) && is_array( $attributes['lname'] ) ? reset( $attributes['lname'] ) : '';

		$user_data = [
			'ID'            => null,
			'user_login'    => $email,
			'user_pass'     => wp_generate_password(),
			'user_nicename' => implode( ' ', array_filter( [ $first_name, $last_name ] ) ),
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'user_email'    => $email,
		];

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = get_user_by( 'ID', $user_id );

		/**
		 * Used to handle post-user-creation logic, ie: role mapping
		 *
		 * @param \WP_User $user       User object
		 * @param array    $attributes SAML Attributes passed from IdP
		 */
		do_action( 'wpsimplesaml_user_created', $user, $attributes );
	}

	if ( ! is_a( $user, 'WP_User' ) ) {
		return new \WP_Error( 'invalid-user', esc_html__( 'Could not create a new user.', 'wp-simple-saml' ) );
	}

	return $user;
}

/**
 * Map user roles after creation
 *
 * @action wpsimplesaml_user_created
 *
 * @param \WP_User $user       User object
 * @param array    $attributes SAML Attributes
 */
function map_user_roles( $user, array $attributes ) {
	/**
	 * Filter to allow role mapping
	 *
	 * @deprecated Remove this callback from `wpsimplesaml_user_created` instead
	 *
	 * @param \WP_User $user User object
	 *
	 * @return bool Allow user role mapping
	 */
	if ( ! apply_filters( 'wpsimplesaml_manage_roles', true, $user ) ) {
		return;
	}

	/**
	 * Filter the role to apply for the new user
	 *
	 * Use `superadmin` to add the user to network super admins
	 *
	 * @param array    $attributes SAML attributes
	 * @param int      $user_id    User ID
	 * @param \WP_User $user       User object
	 *
	 * @return string|array WP Role(s) to apply to the user
	 */
	$roles = (array) apply_filters( 'wpsimplesaml_map_role', get_option( 'default_role' ), $attributes, $user->ID, $user );
	$roles = array_unique( array_filter( $roles ) );

	if ( ! empty( $roles ) ) {
		// For some reason get_current_blog_id doesn't return the proper value at this stage
		$blog_id = get_blog_id( trailingslashit( $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ); // @codingStandardsIgnoreLine
		if ( $blog_id && get_current_blog_id() !== $blog_id ) {
			$user->for_blog( $blog_id );
		}

		// Manage super admin flag
		if ( in_array( 'superadmin', $roles, true ) ) {
			$roles = array_diff( $roles, [ 'superadmin' ] );

			if ( ! is_super_admin( $user->ID ) ) {
				grant_super_admin( $user->ID );
			}
		} else {
			if ( is_super_admin( $user->ID ) ) {
				revoke_super_admin( $user->ID );
			}
		}

		$user->set_role( reset( $roles ) );
		foreach ( $roles as $role ) {
			$user->add_role( $role );
		}
	}
}

/**
 * Sign in the user, and store the auth cookie
 *
 * @param \WP_User $user
 */
function signon( $user ) {
	wp_set_auth_cookie( $user->ID, true, is_ssl() );
}

/**
 * Forward IdP response to another site
 *
 * @param string $url
 *
 * @return string  $token
 */
function cross_site_sso_redirect( $url ) {

	$host = wp_parse_url( $url, PHP_URL_HOST );
	/**
	 * Filters the allowed hosts for cross-site SSO redirection
	 *
	 * @param string $host Host name
	 * @param string $url  Redirection URL
	 *
	 * @return bool|array List of hosts to whitelist, or false to disallow all
	 */
	$allowed_hosts = apply_filters( 'wpsimplesaml_allowed_hosts', [], $host, $url );
	if ( empty( $allowed_hosts ) || ! in_array( $host, $allowed_hosts, true ) ) {
		/* translators: %s is domain of the blacklisted site */
		wp_die( sprintf( esc_html__( '%s is not whitelisted cross-network SSO site.', 'wp-simple-saml' ), esc_html( $host ) ) );
	}

	// Workaround for sub-directory installs, as we usually redirect to admin urls
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( false !== strpos( $path, '/wp-admin' ) ) {
		// If we have an admin url, we know where to start from!
		$sso_url = preg_replace( '#/wp-admin/?.*#', '/sso/verify', $url );
	} elseif ( '/' === $path || empty( $path ) ) {
		// If we're at the site root, we have nothing to guess!
		$sso_url = trailingslashit( $url ) . 'sso/verify';
	} else {
		// If we hit a protected page, OR a subsite, log to the main site / root, then redirect to that page/subsite
		// This doesn't work with protected pages in a sub-directory installs, ie anything outside of wp-admin there
		// as we cannot detect the site home URL!
		$sso_url = str_replace( $path, '/sso/verify', $url );
	}

	$sso_url = add_query_arg( 'redirect_to', $url, $sso_url );

	?>

	<?php // @codingStandardsIgnoreStart ?>
	<form action="<?php echo esc_url( $sso_url ); ?>" method="post" id="sso_form">
		<input type="hidden" name="SAMLResponse" value="<?php echo esc_attr( $_POST['SAMLResponse'] ); ?>">
		<input type="hidden" name="RelayState" value="<?php echo esc_attr( $_POST['RelayState'] ); ?>">
		<?php do_action( 'wpsimplesaml_cross_sso_form_inputs' ); ?>
	</form>
	<?php // @codingStandardsIgnoreEnd ?>

	<script>
		setTimeout( function () {
			document.getElementById( 'sso_form' ).submit();
		}, 100 );
	</script>

	<?php
	exit;
}

/**
 * Get blog ID of the passed URL, if it is on the same network
 *
 * @param $url
 *
 * @return int Blog ID if found, 0 if not
 */
function get_blog_id( $url ) {
	$fragments = wp_parse_url( $url );

	if ( empty( $fragments ) || empty( $fragments['host'] ) ) {
		return 0;
	}

	$site = get_site_by_path( $fragments['host'], $fragments['path'] );

	$blog_id = 0;
	if ( $site ) {
		$blog_id = $site->blog_id;
	}

	return absint( $blog_id );
}

/**
 * Get redirection URL from GET/POST variables, defaults to admin_url() value
 *
 * @return string
 */
function get_redirection_url() {
	// Catch the redirection URL from the login page if passed
	$redirect = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : null; // @codingStandardsIgnoreLine

	// If no redirection URL exists in the URL query, see if we have one from the SAML response
	if ( empty( $redirect ) && isset( $_POST['RelayState'] ) ) { // @codingStandardsIgnoreLine
		$redirect = $_POST['RelayState']; // WPCS: input var okay
	}

	if ( $redirect ) {
		$redirect = urldecode( $redirect );
	}

	// If redirection URL is invalid or empty, fall back to admin_url()
	if ( empty( $redirect ) || ( $redirect && ! filter_var( $redirect, FILTER_VALIDATE_URL ) ) ) {
		$redirect = admin_url();
	}

	return $redirect;
}
