<?php
/*
Plugin Name: WP Simple SAML
Description: Integrate SAML 2.0 IDP without the hassle
Author: Shady Sharaf, Human Made
Version: 0.4.1
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

use WP_CLI;

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/admin/namespace.php';

if ( ! class_exists( '\\OneLogin\\Saml2\\Auth' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\\Admin\\admin_bootstrap' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/inc/class-simple-saml-command.php';
	require_once __DIR__ . '/inc/class-response-command.php';
	WP_CLI::add_command( 'simple-saml', 'HumanMade\\SimpleSaml\\Simple_Saml_Command' );
	WP_CLI::add_command( 'simple-saml response', 'HumanMade\\SimpleSaml\\Response_Command' );
}
