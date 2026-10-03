<?php

namespace Forma\Engine;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Module classes in load order. Each decides for itself whether its dependencies exist.
	 *
	 * @var array<class-string<Module>>
	 */
	private const MODULES = array(
		Projects\Projects::class,
		Media\Media::class,
		Elementor\Module::class,
		Motion\Motion::class,
		Cursor\Cursor::class,
		Transitions\Transitions::class,
		Seo\Seo::class,
	);

	/** @var array<string, Module> */
	private static array $modules = array();

	public static function boot(): void {
		Support\Assets::register();

		foreach ( self::MODULES as $class ) {
			$module = new $class();

			if ( ! $module->is_available() ) {
				continue;
			}

			$module->register();
			self::$modules[ $class::id() ] = $module;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'forma', Cli\Command::class );
			add_action( 'elementor/init', array( self::class, 'keep_all_controls' ) );
		}

		/**
		 * Fires after Forma Studio Engine has registered its modules.
		 *
		 * @param array<string, Module> $modules Active modules keyed by id.
		 */
		do_action( 'forma_engine_loaded', self::$modules );
	}

	/**
	 * Elementor keeps style-only controls (padding, min height, flex direction, …) out of an element's control list
	 * on front-end requests, which includes WP-CLI. The seeders and their checks need the complete list to tell a real
	 * setting key from a typo, so under WP-CLI Elementor is told this is not a front-end request.
	 */
	public static function keep_all_controls(): void {
		if ( ! class_exists( \Elementor\Core\Frontend\Performance::class ) ) {
			return;
		}

		( new \ReflectionProperty( \Elementor\Core\Frontend\Performance::class, 'is_frontend' ) )->setValue( null, false );
	}

	public static function module( string $id ): ?Module {
		return self::$modules[ $id ] ?? null;
	}

	public static function activate(): void {
		Projects\Projects::register_types();
		flush_rewrite_rules();
	}
}
