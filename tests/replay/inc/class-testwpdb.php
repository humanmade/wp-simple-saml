<?php
/** MySQL adapter for the standalone replay tests. */

namespace HumanMade\SimpleSaml\Tests;

use Exception;
use mysqli;
use RuntimeException;

/** Standalone WordPress test adapter. */
class TestWpdb {
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $options = 'wp_options';
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $error_suppressed = false;
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $last_error = '';
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $query_count = 0;
	/**
	 * Standalone test adapter state.
	 *
	 * @var mixed
	 */
	public $conn;
	/**
	 * Initialize the test adapter.
	 *
	 * @throws Exception When the disposable database cannot be opened.
	 */
	public function __construct() {
		// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_report -- Exercise a real database without WordPress.
		mysqli_report( MYSQLI_REPORT_OFF );
		// phpcs:ignore WordPress.DB.RestrictedClasses.mysql__mysqli -- Disposable database connection for regression tests.
		$this->conn = new mysqli( getenv( 'SAML_DB_HOST' ) ?: 'localhost', getenv( 'SAML_DB_USER' ) ?: 'root', getenv( 'SAML_DB_PASSWORD' ) ?: '', '', (int) ( getenv( 'SAML_DB_PORT' ) ?: 3306 ), getenv( 'SAML_DB_SOCKET' ) ?: null );
		if ( $this->conn->connect_error ) {
			throw new Exception( $this->conn->connect_error );
		}
		$database = getenv( 'SAML_DB_NAME' );
		if ( ! $database || ! preg_match( '/^saml_replay_test_[a-zA-Z0-9_]+$/', $database ) ) {
			throw new Exception( 'Use a disposable SAML_DB_NAME beginning with saml_replay_test_.' );
		}
		if ( ! $this->conn->select_db( $database ) ) {
			throw new Exception( $this->conn->error );
		}
	}
	/**
	 * Provide get_blog_prefix behavior for standalone regression tests.
	 *
	 * @param mixed $id Test input.
	 * @return mixed
	 */
	public function get_blog_prefix( $id ) {
		return $id === 1 ? 'wp_' : 'wp_' . $id . '_';
	}
	/**
	 * Provide suppress_errors behavior for standalone regression tests.
	 *
	 * @param mixed $v Test input.
	 * @return mixed
	 */
	public function suppress_errors( $v = true ) {
		$old = $this->error_suppressed;
		$this->error_suppressed = $v;
		return $old;
	}
	/**
	 * Provide prepare behavior for standalone regression tests.
	 *
	 * @param mixed $sql Test input.
	 * @param mixed ...$args Test input.
	 * @return mixed
	 */
	public function prepare( $sql, ...$args ) {
		foreach ( $args as $arg ) {
			$sql = preg_replace( '/%s/', "'" . $this->conn->real_escape_string( $arg ) . "'", $sql, 1 );
		}
		return $sql;
	}
	/**
	 * Provide query behavior for standalone regression tests.
	 *
	 * @throws RuntimeException When testing error-suppression restoration.
	 *
	 * @param mixed $sql Test input.
	 * @return mixed
	 */
	public function query( $sql ) {
		++$this->query_count;
		if ( getenv( 'SAML_STORAGE_FAIL' ) ) {
			return false;
		}
		if ( getenv( 'SAML_STORAGE_THROW' ) ) {
			throw new RuntimeException( 'Simulated adapter exception' );
		}
		if ( ! $this->conn->query( $sql ) ) {
			$this->last_error = $this->conn->error;
			return false;
		}
		return $this->conn->affected_rows;
	}
}
