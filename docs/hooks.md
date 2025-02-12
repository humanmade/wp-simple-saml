These are the currently available filters in WordPress Simple SAML plugin, which controls how the plugins works.

## Filters

- **`wpsimplesaml_idp_metadata_xml_path`** `() : string = ''`

Use to pass a path to the XML file for IdP metadata.

- **`wpsimplesaml_idp_metadata_xml`** `() : string = ''`

Use to pass XML blob for IdP metadata.

- **`wpsimplesaml_idp_metadata`** `() : string = ''`

Use to pass/update IdP metadata.

- **`wpsimplesaml_config`** `() : array = []`

Use to pass/override IdP metadata.

- **`wpsimplesaml_log_in_link`** `() : bool = true`

Use to enable/disable/override displaying of log-in link in WordPress log-in form.

- **`wpsimplesaml_log_in_text`** `() : string = __( 'SSO Login' )`

Use to alter the log-in text of log-in form link.

- **`wpsimplesaml_force`** `() : bool = false`

Use to force SSO redirection via code, bypassing the log-in form completely, overrides the `SSO Status` option. Defaults to be disabled.

- **`wpsimplesaml_add_users_to_site`** `( WP_User ) : bool = true`

Use to enable/disable adding users to sites they've not been added to during SSO log-in process. Defaults to be enabled.

- **`wpsimplesaml_match_user`** `( string $email, array $saml_attributes ) : WP_User = null`

Use to match users from SAML responses to WordPress users using a different method, ie: a special attribute/meta key. Default to use the `user_login` mapped key/value (check `wpsimplesaml_attribute_mapping`).

- **`wpsimplesaml_user_data`** `( array $saml_attributes ) : array`

Use to update user data before passing to `wp_insert_user` for first-time log-ins.

- **`wpsimplesaml_attribute_mapping`** `() : array`

Use to map SAML response attribute fields to WordPress user fields, eg `[ 'user_login' => 'uid', 'user_email' => 'email' ]`. This is required for proper population of user data, based on your IdP of choice.

- **`wpsimplesaml_manage_roles`** `() : bool = false`

Use to enable management of users roles based on SAML attributes, requires use of `wpsimplesaml_map_role` to map attributes to roles.

- **`wpsimplesaml_map_role`** `( array $saml_attribute, int $user_id, WP_User $user ) : string|array`

Use to map SAML attributes to WordPress user roles.

- **`wpsimplesaml_allowed_hosts`** `( string $host ) : array = []`

Use to allow specific hosts/domains to use SSO delegation, ie: sharing the same service provider data.

- **`wpsimplesaml_network_activated`** `() : bool = false`

Use to override the standard WordPress plugin activation check, eg. when the plugin is activated via code.

## Actions

- **`wpsimplesaml_action_%ACTION%`**
- **`wpsimplesaml_invalid_endpoint`** `( string $action )`

Can be used to use `https://my.site/sso/XXX` ( where XXX is `$action` in the above )custom URLs for various SSO related endpoints, ie checking validity of the session.

- **`wpsimplesaml_user_created`** `( WP_User $user, array $saml_attributes )`

Trigger custom login on creation of new users as a result of the SSO log-in process.

- **`wpsimplesaml_cross_sso_form_inputs`** `()`

Can be used to add more data to the delegated request form the main service provider to the delegating site, as a result of a SAML Response.
