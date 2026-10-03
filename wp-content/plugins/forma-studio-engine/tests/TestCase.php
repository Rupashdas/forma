<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

/**
 * Tiny integration test base. Each test runs inside a database transaction that is rolled back afterwards,
 * so tests can create posts, terms and options without touching the site's real content.
 */
abstract class TestCase {

	/**
	 * Run one test method; returns null on success or a failure message.
	 */
	public static function run( string $class, string $method ): ?string {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );
		$test = new $class();

		try {
			$test->set_up();
			$test->$method();
			return null;
		} catch ( \Throwable $e ) {
			return $e->getMessage() . ' @ ' . self::origin( $e );
		} finally {
			$test->tear_down();
			$wpdb->query( 'ROLLBACK' );
			wp_cache_flush();
		}
	}

	/**
	 * File:line of the first frame inside a *Test.php file, so failures point at the assertion.
	 */
	private static function origin( \Throwable $e ): string {
		$frames = array_merge(
			array(
				array(
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				),
			),
			$e->getTrace()
		);

		foreach ( $frames as $frame ) {
			if ( isset( $frame['file'] ) && str_ends_with( $frame['file'], 'Test.php' ) ) {
				return basename( $frame['file'] ) . ':' . $frame['line'];
			}
		}

		return basename( $e->getFile() ) . ':' . $e->getLine();
	}

	protected function set_up(): void {}

	protected function tear_down(): void {}

	protected function assert_same( mixed $expected, mixed $actual, string $message = '' ): void {
		if ( $expected !== $actual ) {
			$this->fail( trim( $message . ' — expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) ) );
		}
	}

	protected function assert_true( bool $condition, string $message ): void {
		if ( ! $condition ) {
			$this->fail( $message );
		}
	}

	protected function fail( string $message ): never {
		throw new AssertionFailed( $message );
	}
}

final class AssertionFailed extends \Exception {}
