<?php
/**
 * Plugin Name: WP Simple SAML
 * Description: Integrate SAML 2.0 IDP without the hassle
 * Author: Shady Sharaf, Human Made
 * Version: 0.1
 * Author URI: http://hmn.md
 * Text Domain: wp-simple-saml
 * Domain Path: /language/
 * 
 * Copyright 2017 Shady Sharaf, Human Made
 * 
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * @package nuk-wp-simple-saml
 */

namespace HumanMade\SimpleSaml;

use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Utils;
use OneLogin\Saml2\ValidationError;
use WP_Error;
use WP_User;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Get an instance of SAML2 Auth object
 *
 * @return False|Auth
 * @throws Error False.
 */
function instance() {

	static $instance;

	if ( ! empty( $instance ) ) {
		return $instance;
	}

	$config = apply_filters( 'wpsimplesaml_config', array() );

	if ( empty( $config ) ) {
		return false;
	}

	$instance = new Auth( $config );

	return $instance;
}


/**
 * Intercept login requests
 *
 * @action login_init
 *
 * @param string $action The action being intercepted.
 * @param string $redirect Redirect URL.
 *
 * @throws Error|ValidationError SSO Settings, User error.
 */
function intercept( string $action = 'login', string $redirect = '' ) { 
	// If we have no valid instance, bail completely.
	if ( ! instance() ) {
		wp_die( 'Invalid SSO settings. Contact your administrator.' );
	}

	// WPCS: input var okay.
	if ( isset( $_POST['RelayState'] ) && Utils::getSelfURL() !== $_POST['RelayState'] ) {
		// WPCS: input var okay.
		$redirect = $_POST['RelayState'];
	}

	if ( $redirect ) {
		$redirect = urldecode( $redirect );
	}

	if ( $redirect && ! filter_var( $redirect, FILTER_VALIDATE_URL ) ) {
		wp_die( 'Invalid SSO Redirection URL. Contact your administrator.' );
	}

	if ( empty( $redirect ) ) {
		$redirect = admin_url();
	}

	$is_subdirectory_install = is_multisite() && ! is_subdomain_install();

	if ( is_user_logged_in()
		// Valid action ?.
		&& in_array( $action, array( 'login', 'verify' ), true )
		// Subdirectory installs share cookie domains, so user is already logged in, but we still need to verify SSO token.
		&& ! ( 'verify' === $action && $is_subdirectory_install )
		// If we have not been passed a valid URL, there is nothing to be done.
		&& filter_var( $redirect, FILTER_VALIDATE_URL )
	) {
		// Are we going to an internal link ?.
		if ( get_current_blog_id() === get_blog_id( $redirect ) ) {
			// Home sweet home!.
			redirect( $redirect );
		} else { // Else we're trying to login to another environment.
			cross_site_sso_redirect( $redirect );
		}   
	} elseif ( 'login' === $action ) {
		login( $redirect );
	} elseif ( 'verify' === $action && ! empty( $_POST['SAMLResponse'] ) ) {
		// We'veo got a SAML response to parse already.
		// WPCS: input var okay.
		$user = verify();
		if ( is_a( $user, WP_User::class ) ) {

			// Set authentication cookie.
			signon( $user );

			// If we're authenticating to another site.
			if ( get_current_blog_id() === get_blog_id( $redirect ) ) {
				redirect( $redirect );
			} else {
				cross_site_sso_redirect( $redirect );
			}
		} elseif ( is_wp_error( $user ) ) {
			wp_die( 'Error creating a new user. Contact your administrator. Message: ' . $user->get_error_message() );
		} else {
			wp_die( 'Invalid SSO response. Contact your administrator, or stop playing around, we can see you!' );
		}
	} else {
		wp_safe_redirect( home_url(), 404 );
		exit;
	}
}

/**
 * Initiate IDP login.
 *
 * @param string $redirect Redirect to after login.
 *
 * @throws Error Insance error.
 */
function login( string $redirect = '' ) {
	instance()->login( $redirect );
}

/**
 * Handle authentication responses.
 *
 * @return WP_User|WP_Error
 * @throws Error|ValidationError WordPress error invalid email or failed parse the authentication response.
 */
function verify() {
    $saml = instance();

	$saml->processResponse();

	if ( ! empty( $saml->get_errors() ) ) {
		$errors = implode( ', ', $saml->get_errors() );
		wp_die(
			sprintf(
				'Error: Could not parse the authentication response, '
				. 'please forward this error to your administrator: "%s", last error reason: "%s"',
				esc_html( $errors ),
				esc_html( $saml->getLastErrorReason() )
			)
		);
	}

	if ( ! $saml->isAuthenticated() ) {
		wp_die( "Error: Authentication wasn't completed successfully." );
	}

	// Assumes the email is the unique identifier set in SAML IDP.
	$email = filter_var( $saml->getNameId(), FILTER_VALIDATE_EMAIL );

	if ( ! $email ) {
		return new WP_Error( 'invalid_email', 'Error: Invalid email passed. Contact your administrator.' );
	}

	$attrs = array(
		'email'      => $email,
		'attributes' => $saml->getAttributes(),
	);

	return settle( null, $attrs );
}

/**
 * Create a user and/or update his role based on SAML response.
 *
 * @param string $user_id UserID of user logging in.
 * @param array  $attributes Array of User attribites from IDP.
 *
 * @return WP_User|WP_Error
 */
function settle( $user_id, array $attributes = array() ): mixed {
	// Does this user_id exist ?.
	if ( ! empty( $user_id ) ) {
		$user = get_user_by( 'ID', $user_id );
	}

	// Is this email registered by an existing user ?.
	if ( empty( $user ) ) {
		$user = get_user_by( 'email', $attributes['email'] );
	}

	// No user yet ? lets create a new one.
	if ( empty( $user ) ) {
		$saml_attrs = $attributes['attributes'];

		$first_name = isset( $saml_attrs['fname'] ) && is_array( $saml_attrs['fname'] )
		? reset( $saml_attrs['fname'] )
		: '';
		$last_name  = isset( $saml_attrs['lname'] ) && is_array( $saml_attrs['lname'] )
		? reset( $saml_attrs['lname'] )
		: '';

		$user_data = array(
			'ID'            => $user_id,
			'user_login'    => $attributes['email'],
			'user_pass'     => wp_generate_password(),
			'user_nicename' => implode( ' ', array_filter( array( $first_name, $last_name ) ) ),
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'user_email'    => $attributes['email'],
		);

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = get_user_by( 'ID', $user_id );
	}

	/**
	 * Filter the role to apply for the new user.
	 */
	$role = apply_filters( 'wpsimplesaml_map_role', get_option( 'default_role' ), $attributes, $user_id );

	// For some reason get_current_blog_id doesn't return the proper value at this stage.

	$url     = ( $_SERVER['REQUEST_SCHEME'] ?? 'https' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$blog_id = get_blog_id( trailingslashit( $url ) );
	if ( $blog_id && get_current_blog_id() !== $blog_id ) {
		$user->for_site( $blog_id );
	}

	// Assign the selected role, if not already assigned.
	if ( ! in_array( $role, $user->roles, true ) ) {
		$user->set_role( $role );
	}

	return $user;
}

/**
 * Sign in the user, and store the auth cookie.
 *
 * @param WP_User $user User Object.
 */
function signon( WP_User $user ) {
	wp_set_auth_cookie( $user->ID, true, is_ssl() ); 
}

/**
 * Pass SSO token to a subsite to authenticate a user.
 *
 * @param string $url The url to redirect to after login.
 */
function cross_site_sso_redirect( string $url ) { 
	if ( ! apply_filters( 'wpsimplesaml_allowed_hosts', false, wp_parse_url( $url, PHP_URL_HOST ), $url ) ) {
		wp_die( 'This is not an allowed Cross-network SSO site.' );
	}

	// Workaround for sub-directory installs, as we usually redirect to admin urls.
	if ( false !== strpos( $url, '/wp-admin' ) ) {
		$sso_url = preg_replace( '#/wp-admin/?.*#', '/sso/verify', $url );
	} else {
		$sso_url = str_replace( wp_parse_url( $url, PHP_URL_PATH ), '/sso/verify', $url );
	}

	?>

	<form action="<?php echo esc_url( $sso_url ); ?>" method="post" id="sso_form">
		<input type="hidden" name="SAMLResponse" value="<?php echo esc_attr( $_POST['SAMLResponse'] ); ?>"> 
		<input type="hidden" name="RelayState" value="<?php echo esc_attr( $_POST['RelayState'] ); ?>">
	</form>

	<script>
	setTimeout(function () {
		document.getElementById('sso_form').submit();
	}, 100);
	</script>

	<?php
}

/**
 * Get blog ID of the passed URL, if it is on the same network.
 *
 * @param string $url Url Site to return blogID.
 *
 * @return int Blog ID if found, 0 if not.
 */
function get_blog_id( $url ): int {
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
 * Redirect to a URL OR Admin dashboard.
 *
 * @param string $url URL to redirect to.
 */
function redirect( string $url = '' ) {
    wp_safe_redirect( $url );
	exit;
}

/**
 * Register SSO endpoint.
 *
 * @action init
 */
function rewrites() {
	add_rewrite_endpoint( 'sso', EP_ROOT, true );
}

add_action( 'init', __NAMESPACE__ . '\\rewrites' );

/**
 * SSO Endpoint handler.
 *
 * @action template_redirect.
 */
function endpoint() { 
	// Bail if not needed.
	if ( apply_filters( 'wpsimplesaml_ignore', false ) ) {
		return;
	}

	global $wp_query;
	if ( isset( $wp_query->query_vars['sso'] ) ) {
		$req = ! empty( $wp_query->query_vars['sso'] ) ? $wp_query->query_vars['sso'] : 'login';
		call_user_func_array( __NAMESPACE__ . '\\intercept', explode( '/', $req ) );
		exit;
	}

	// Do not block access to SSO endpoint if blog is not public.
	if ( class_exists( 'ds_more_privacy_options' ) ) {
		global $ds_more_privacy_options;
		remove_action( 'template_redirect', array( $ds_more_privacy_options, 'ds_users_authenticator' ) );
	}
}

add_action( 'template_redirect', __NAMESPACE__ . '\\endpoint', 9 );

/**
 * Go to homepage after logging out, so we don't trigger the login flow again.
 */
function go_home() {
    wp_safe_redirect( home_url() );
	exit;
}

add_action( 'wp_logout', __NAMESPACE__ . '\\go_home' );

/**
 * Replace WordPress Login.
 *
 * @throws Error Login error.
 */
function authenticate_with_sso() { 
	// Bail if not needed.
	if ( apply_filters( 'wpsimplesaml_ignore', false ) || ! apply_filters( 'wpsimplesaml_force', true ) ) {
		return;
	}

	// WPCS: input var okay.
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		// WPCS: input var okay.
		$redirect_to = urldecode( wp_unslash( $_REQUEST['redirect_to'] ) );
	} else {
		$redirect_to = admin_url();
	}

	login( $redirect_to );
}

add_action( 'wp_authenticate', __NAMESPACE__ . '\\authenticate_with_sso' );

/**
 * Show SSO login link in login form.
 *
 * @action login_form
 */
function login_via_sso_link() { 
	if ( apply_filters( 'wpsimplesaml_ignore', false ) || ! apply_filters( 'wpsimplesaml_login_link', true ) ) {
		return;
	}

	$redirect = isset( $_GET['redirect_to'] ) ? urldecode( wp_unslash( $_GET['redirect_to'] ) ) : ''; 

	$output = sprintf(
		'<p><a href="%s" id="login-via-sso">%s</a></p>',
		esc_url( site_url( 'sso/login/' ) . urlencode( $redirect ) ),
		esc_html( apply_filters( 'wpsimplesaml_login_text', __( 'Login via SSO', 'wpsimplesaml' ) ) )
	);

	echo $output;
}

add_action( 'login_form', __NAMESPACE__ . '\\login_via_sso_link' );
