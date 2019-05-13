<table width="100%">
	<tr>
		<td align="left" width="70">
			<strong>WordPress Simple SAML</strong><br />
			Easy to use Single-sign-on ( SSO ) SAML integration plugin for WordPress, with multi-site / multi-network support. 
		</td>
		<td align="right" width="20%">
			<a href="https://travis-ci.org/humanmade/wp-simple-saml">
				<img src="https://travis-ci.org/humanmade/wp-simple-saml.svg?branch=master" alt="Build status">
			</a>
		</td>
	</tr>
	<tr>
		<td>
			A <strong><a href="https://hmn.md/">Human Made</a></strong> project. Maintained by <a href="https://github.com/shadyvb">Shady Sharaf</a>.
		</td>
		<td align="center">
			<img src="https://hmn.md/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" />
		</td>
	</tr>
</table>

WordPress Simple SAML is a flexible, extensible SAML integration plugin, which does most of the grunt work while keeping everything configurable through actions and filters throughout the plugin.

The plugin supports multi-site networks, and cross-network SSO delegation as well. Can be installed on site-level, or on network-level.

WordPress Simple SAML uses [OneLogin's PHP-SAML](https://github.com/onelogin/php-saml) toolkit for SAML API integration.

## Getting Set Up


- Copy the plugin files to your `wp-content/plugins` directory
- Activate the plugin
- Head over to [configuration screen](#Configuration).
- Send Service Provider metadata URL ( or content ) to your Identity Provider authority (IdP) ( find the link in settings page ).

**Note**: If you're activating the plugin network-wide via code, you might need to use the filter `wpsimplesaml_network_activated` to override the standard WordPress check, something like this would be what you need:

```php
add_filter( 'wpsimplesaml_network_activated', '__return_true' )
```  

## Configuration

There is two ways to configure the plugin, either from the admin interface or using filters, both can be used interchangably as the project requires, usually filters prevail database settings though.

### Admin configurations

Depending on whether the plugin is network-activated or not, you will need to go to `Settings \ General` or `Network Settings` pages.

- `SSO Status`
is how the plugin integrates with WordPress login process, available options are `Disable`, `Display login link` which only provides a link in the login form, `Force redirect` which overrides the login form altogether and directly goes to SSO login page.
- `SSO Base URL` (optional)
is the home URL of the WordPress site that serves as the delegate ( main service provider ) to which SAML responses will be posted, usually this is the main site of the network, and is the same value for `siteurl` option, eg `https://my.site/`
- `SSO IdP Metadata` (required, if not filtered)
Copy of the SSO IdP metadata XML file, which can also be passed via either `wpsimplesaml_idp_metadata_xml_path` for a path to the XML file, or `wpsimplesaml_idp_metadata_xml` for the contents of the XML, or `wpsimplesaml_idp_metadata` for the configuration array.
- `SSO delegation whitelisted hosts`
List of hosts to whitelist during delegation of SAML responses, ie: secondary domains that needs to use SSO as well from the same IdP. Local sites are allowed by default.  
- `SSO Role Management`
Enables developers to assign different roles to users based on SAML Responses, disabled by default, and is controlled via a few filters,  
- `SSO Debug via Cookies`
Allows developers to use a special cookie named `sso_debug` to override the `SSO Status` option during testing. Possible value of the cookie are `force` and `link`, which are self-explanatory.
- `SSO Config validation`
Shows information about IdP metadata and validity of its settings.

### Configuration via code

WordPress Simple SAML is built to be as extensible as possible, so most aspects of the login/validation process can be tweaked as needed by using the available well-documented [Hooks](https://github.com/humanmade/wp-simple-saml/wiki/Hooks).

TL;DR; This is the basic minimum filters you'd need to get an implementation working with the default options.

```php
// SAML metadata XML file path
add_filter( 'wpsimplesaml_idp_metadata_xml_path', function(){
	return ABSPATH . '/.private/sso/test.idp.xml';
} );

// Configure attribute mapping between WordPress and SSO IdP SAML attributes
add_filter( 'wpsimplesaml_attribute_mapping', function(){
	return [
		'user_login' => 'uid',
		'user_email' => 'email',
	];
} );
```  

## Testing

As SSO authorities are usually a pain to change, and credentials take time to be configured, we've documented steps to get a sample SAML IdP ( Identity provider ) up and running using a test docker container. See more at [Testing SSO Locally](https://github.com/humanmade/wp-simple-saml/wiki/Testing-SSO-locally). 

## Contribute

First, thanks for contributing!

### Setting up

To get development dependencies, install composer and npm dependencies via:

```bash
composer install
npm install
```

This installs Human made's version of WordPress Coding Standards via PHP Code Sniffer, and symlinks a precommit hook to automatically check all commits for code quality concerns.

### Workflow

- Find an issue you'd like to help with, or create a new one for the change you'd like to introduce.
- Fork the repo to your own account
- Issue pull-requests from your fork to ours
- Tag the issue you're trying to resolve in your pull-request for some context
- Make sure the pull-request passed all Travis checks
- Tag any of the contributors for a review.

## Next

Check [issues list](https://github.com/humanmade/wp-simple-saml/issues) for what's planned next.

## Credits
Created by Human Made for network-wide SAML SSO Integrations, because of the lack of a well-written WordPress integration with the features/flexibility our clients require.

Written and maintained by [Shady Sharaf](https://github.com/shadyvb). Thanks to all our [contributors](https://github.com/humanmade/wp-simple-saml/graphs/contributors).

Interested in joining in on the fun? [Join us, and become human!](https://hmn.md/is/hiring/)

## Changelog

- 0.3
  - Fix compatibilty with WordPress single site (no multisite).
  - Fix error caused by using WordPress dashboard function outside the dashboard.

- 0.2.1
  - Fix missing composer dependency and updated .gitignore

- 0.2
  - Updating PHP SAML library to 3.0 to support PHP 7.x

- 0.1
  - Stable version
