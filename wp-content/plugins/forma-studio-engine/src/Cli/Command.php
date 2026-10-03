<?php

namespace Forma\Engine\Cli;

use Forma\Engine\Seed\Setup;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the FORMA site from the plugin's data files. Every step is safe to rerun.
 *
 * ## EXAMPLES
 *
 *     wp forma all
 *     wp forma setup
 */
final class Command {

	/**
	 * Configure site, media, permalink and Elementor settings.
	 */
	public function setup(): void {
		( new Setup( $this->logger() ) )->run();
		WP_CLI::success( 'Site configured.' );
	}

	private function logger(): \Closure {
		return static function ( string $message ): void {
			WP_CLI::log( $message );
		};
	}
}
