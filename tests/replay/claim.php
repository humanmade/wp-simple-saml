<?php
/** Run one replay claim against actual plugin code and a disposable database. */

use HumanMade\SimpleSaml as Plugin;
use HumanMade\SimpleSaml\Tests\TestWpdb;

require __DIR__ . '/fixtures.php';
require __DIR__ . '/inc/class-testwpdb.php';

$wpdb = new TestWpdb();
if ( getenv( 'SAML_PREVIOUS_SUPPRESSION' ) ) {
	$wpdb->suppress_errors();
}
if ( ( $argv[1] ?? '' ) === 'setup' ) {
	foreach ( [ 'wp_options', 'wp_2_options', 'wp_9_options' ] as $table ) {
		$wpdb->conn->query( "CREATE TABLE IF NOT EXISTS $table (option_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, option_name VARCHAR(191) NOT NULL, option_value LONGTEXT NOT NULL, autoload VARCHAR(20) NOT NULL, PRIMARY KEY(option_id), UNIQUE KEY option_name(option_name))" );
		$wpdb->conn->query( "TRUNCATE TABLE $table" );
	}
	echo "reset\n";
	exit;
}
if ( ( $argv[1] ?? '' ) === 'drop' ) {
	$wpdb->conn->query( 'DROP DATABASE `' . getenv( 'SAML_DB_NAME' ) . '`' );
	exit;
}
if ( ( $argv[1] ?? '' ) === 'seed' ) {
	$key = $argv[2] === 'legacy' ? 'wpsimplesaml_used_assertion_' . hash( 'sha256', 'https://idp.example.test/' . chr( 0 ) . '_assertion_123' )
		: 'wpsimplesaml_used_assertion_v2_' . hash( 'sha256', '25:https://idp.example.test/14:_assertion_123' );
	$wpdb->conn->query( $wpdb->prepare( 'INSERT INTO wp_options (option_name, option_value, autoload) VALUES (%s, %s, %s)', $key, '1', 'no' ) );
	exit;
}
if ( ( $argv[1] ?? '' ) === 'inspect' ) {
	$result = $wpdb->conn->query( 'SELECT option_name, option_value, autoload FROM wp_options' );
	echo json_encode( $result->fetch_all( MYSQLI_ASSOC ) ) . "\n";
	exit;
}
if ( getenv( 'SAML_MULTISITE' ) ) {
	$wpdb->options = 'wp_2_options';
}
if ( getenv( 'SAML_MISSING_TABLE' ) ) {
	$wpdb->options = 'missing_options';
}
if ( $argv[1] !== 'key' ) {
	$_POST['SAMLResponse'] = base64_encode( file_get_contents( $fixture_dir . '/' . $argv[1] . '.xml' ) );
}
if ( getenv( 'SAML_PARALLEL_START' ) ) {
	$wait = (float) getenv( 'SAML_PARALLEL_START' ) - microtime( true );
	if ( $wait > 0 ) {
		usleep( (int) ( $wait * 1000000 ) );
	}
}
try {
	if ( $argv[1] === 'key' ) {
		// Set the validated ID directly only for encoding/namespace tests.
		$config = Plugin\Admin\get_config();
		$config['idp']['entityId'] = $argv[2];
		$auth = new OneLogin\Saml2\Auth( $config );
		$property = new ReflectionProperty( $auth, '_lastAssertionId' );
		$property->setAccessible( true );
		$property->setValue( $auth, $argv[3] );
		$result = Plugin\prevent_assertion_replay( $auth );
		$user = $result === true ? new WP_User() : $result;
	} elseif ( getenv( 'SAML_LEGACY_WORKER' ) ) {
		// Exercise the old single-row claim with real signature validation.
		$auth = Plugin\process_response();
		if ( is_wp_error( $auth ) ) {
			$user = $auth;
		} else {
			$idp = $auth->getSettings()->getIdPData();
			$key = 'wpsimplesaml_used_assertion_' . hash( 'sha256', $idp['entityId'] . chr( 0 ) . $auth->getLastAssertionId() );
			$result = $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO wp_options (option_name, option_value, autoload) VALUES (%s, %s, %s)', $key, '1', 'no' ) );
			$user = $result === 1 ? new WP_User() : new WP_Error( $result === false ? 'assertion-storage-failed' : 'replayed-assertion', 'Legacy claim rejected' );
		}
	} else {
		$user = Plugin\get_sso_user();
	}
} catch ( RuntimeException $error ) {
	if ( ! getenv( 'SAML_STORAGE_THROW' ) ) {
		throw $error;
	}
	$user = new WP_Error( 'adapter-exception', $error->getMessage() );
}
if ( $user instanceof WP_User ) {
	Plugin\signon( $user );
}
echo json_encode([
	'accepted' => $user instanceof WP_User,
	'error' => is_wp_error( $user ) ? $user->code : null,
	'cookie' => $GLOBALS['cookie_call'] ?? null,
	'strict' => Plugin\instance()->getSettings()->isStrict(),
	'database_queries' => $wpdb->query_count,
	'suppression_restored' => $wpdb->error_suppressed === (bool) getenv( 'SAML_PREVIOUS_SUPPRESSION' ),
], JSON_UNESCAPED_SLASHES) . "\n";
