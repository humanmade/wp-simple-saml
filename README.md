# WordPress Simple SAML

<table width="100%">
	<tr>
		<td align="left" width="70%">
			Easy to use Single-sign-on ( SSO ) SAML integration plugin for WordPress, with multi-site / multi-network support.
		</td>
		<td align="center">
			<p style="font-size:small">Created by</p>
			<img src="https://humanmade.com/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" />
		</td>
	</tr>
</table>

---

WordPress Simple SAML is a flexible, extensible SAML integration plugin, which does most of the grunt work while keeping everything configurable through actions and filters throughout the plugin.

The plugin supports multi-site networks, and cross-network SSO delegation as well. Can be installed on site-level, or on network-level.

WordPress Simple SAML uses [OneLogin's PHP-SAML](https://github.com/onelogin/php-saml) toolkit for SAML API integration.

## Installation

-   Copy the plugin files to your `wp-content/plugins` directory
-   Activate the plugin
-   Head over to [configuration screen](#Configuration).
-   Send Service Provider metadata URL (or content) to your Identity Provider authority (IdP), find the link in settings page.

**Note**: If you're activating the plugin network-wide via code, you might need to use the filter `wpsimplesaml_network_activated` to override the standard WordPress check, something like this would be what you need:

```php
add_filter( 'wpsimplesaml_network_activated', '__return_true' )
```

### Configuration

There is two ways to configure the plugin, either from the admin interface or using filters, both can be used interchangably as the project requires, usually filters prevail database settings though.

#### Option 1: Admin

Go to `Settings \ General` if single installation, or `Network Settings` if multisite.

-   `SSO Status`
    is how the plugin integrates with WordPress login process, available options are `Disable`, `Display login link` which only provides a link in the login form, `Force redirect` which overrides the login form altogether and directly goes to SSO login page.
-   `SSO Base URL` (optional)
    is the home URL of the WordPress site that serves as the delegate ( main service provider ) to which SAML responses will be posted, usually this is the main site of the network, and is the same value for `siteurl` option, eg `https://my.site/`
-   `SSO IdP Metadata` (required, if not filtered)
    Copy of the SSO IdP metadata XML file, which can also be passed via either `wpsimplesaml_idp_metadata_xml_path` for a path to the XML file, or `wpsimplesaml_idp_metadata_xml` for the contents of the XML, or `wpsimplesaml_idp_metadata` for the configuration array.
-   `SSO delegation whitelisted hosts`
    List of hosts to whitelist during delegation of SAML responses, ie: secondary domains that needs to use SSO as well from the same IdP. Local sites are allowed by default.
-   `SSO Role Management`
    Enables developers to assign different roles to users based on SAML Responses, disabled by default, and is controlled via a few filters,
-   `SSO Debug via Cookies`
    Allows developers to use a special cookie named `sso_debug` to override the `SSO Status` option during testing. Possible value of the cookie are `force` and `link`, which are self-explanatory.
-   `SSO Config validation`
    Shows information about IdP metadata and validity of its settings.

#### Option 1: Code

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

## Development

### Testing

> **TL;DR**

```
# Make sure you have Docker installed, then run:
npm install
npm run dev
```

## Contribute

**TL;DR**

```bash
# Make sure you have Docker installed, then run:
npm install
npm run dev
```

For convenience, you can use `@wordpress/env`, a local WordPress server can run by executing `npm run dev`: this command will start a local server at http://localhost:8888/ (requires Docker).

Run `npm run wp-env stop` to shut down the server when finished.

As SSO authorities are usually a pain to change, and credentials take time to be configured, we've documented steps to get a sample SAML IdP ( Identity provider ) up and running using a test docker container. See more at [Testing SSO Locally](https://github.com/humanmade/wp-simple-saml/wiki/Testing-SSO-locally).

### Developing

To get development dependencies, install composer and npm dependencies via:

```bash
composer install
npm install
```

This installs Human made's version of WordPress Coding Standards via PHP Code Sniffer, and symlinks a precommit hook to automatically check all commits for code quality concerns.

## Contributing

- Fork the repository
- Create a feature branch (git checkout -b feature/amazing-feature)
- Commit changes (git commit -m 'Add some amazing feature')
- Push to branch (git push origin feature/amazing-feature)
- Open a Pull Request

### Contribution Guidelines

## Credits

Created by <a href="https://hmn.md/"><img src="https://humanmade.com/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" alt="Human Made" /></a>

Thanks to all our [contributors](https://github.com/humanmade/wp-simple-saml/graphs/contributors).

=======

## Changelog

-   0.3
    -   Fix compatibilty with WordPress single site (no multisite).
    -   Fix error caused by using WordPress dashboard function outside the dashboard.
-   0.2.1
    -   Fix missing composer dependency and updated .gitignore
-   0.2
    -   Updating PHP SAML library to 3.0 to support PHP 7.x
-   0.1
    -   Stable version

## Credits

Created by <a href="https://hmn.md/"><img src="https://humanmade.com/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" alt="Human Made" /></a>

Thanks to all our [contributors](https://github.com/humanmade/wp-simple-saml/graphs/contributors).

Interested in joining in on the fun? [Join us, and become human!](https://hmn.md/is/hiring/)
