# Testing locally

Usually SSO systems are managed by external entities to the development team, and most times - at least in my experience - it has been a daunting experience asking for configuration changes back and forth to be able to test the SSO implementation, specially because SSO SPs ( service providers, which is our site in this case ) require a single URL to redirect to after authentication succeeds.

Here is where kenchan0130 [docker-simplesaml](https://github.com/kenchan0130/docker-simplesamlphp) docker image comes to relieve some of the pressure, enabling you to test the SSO process with minimal changes required.

## Steps:

1. First we run the docker image, and pass some parameters to introduce our site's SP. Remember to update the parameters `SIMPLESAMLPHP_SP_ENTITY_ID`, `SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE` and `SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE` to match your local WordPress address. On the example it's pointing to `localhost:8888`, default address of wp-env.

```bash
docker run --name=idp \
  -p 8080:8080 \
  -e SIMPLESAMLPHP_SP_ENTITY_ID=http://localhost:8888/ \
  -e SIMPLESAMLPHP_SP_ASSERTION_CONSUMER_SERVICE=http://localhost:8888/sso/verify \
  -e SIMPLESAMLPHP_SP_SINGLE_LOGOUT_SERVICE=http://localhost:8888/sso/logout \
  -d kenchan0130/simplesamlphp
```

2. Second we need to configure the plugin using one of the two options, trough admin or trough code using filters.

### Option 1: Admin

- Go to `Settings \ General` if single installation, or `Network Settings` if multisite.
- In order to do that, we need to copy the IdP metadata XML from our dockerized IdP, which should live at http://localhost:8080/simplesaml/saml2/idp/metadata.php?output=xhtml, if you used the example above ), and save it locally where our site can read it. Let's assume it is under `ABSPATH . '/.private/sso/test.idp.xml'`.
- Create a new integration plugin / edit functions.php, and filter the plugin configuration as follows:

```
// SAML metadata XML file path
add_filter( 'wpsimplesaml_idp_metadata_xml', function(){
	return ABSPATH . '/.private/sso/test.idp.xml';
} );

// Configure attribute mapping between WordPress and SSO IdP
add_filter( 'wpsimplesaml_attribute_mapping', function(){
	return [
		'user_login' => 'uid',
		'user_email' => 'email',
	];
} );
```
- Now you can start testing using the sample _static_ users provided by the [docker-simplesaml](https://hub.docker.com/r/kristophjunge/test-saml-idp/)

**Note**: The docker command in the example removes the image automatically once the container is removed, as no state needs to be preserved, you just need to stop the container after you're finished, for the sake of your battery, using `docker stop idp`.