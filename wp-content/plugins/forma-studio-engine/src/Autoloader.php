<?php

namespace Forma\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal PSR-4 autoloader, so the plugin ships without a vendor/ directory
 * (fewer files on inode-limited hosting, no build step to deploy).
 */
final class Autoloader {

	public static function register( string $prefix, string $base_dir ): void {
		spl_autoload_register(
			static function ( string $class ) use ( $prefix, $base_dir ) {
				if ( ! str_starts_with( $class, $prefix ) ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

				if ( is_readable( $file ) ) {
					require $file;
				}
			}
		);
	}
}
