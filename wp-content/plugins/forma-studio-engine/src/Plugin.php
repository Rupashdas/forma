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
	private const MODULES = array();

	/** @var array<string, Module> */
	private static array $modules = array();

	public static function boot(): void {
		foreach ( self::MODULES as $class ) {
			$module = new $class();

			if ( ! $module->is_available() ) {
				continue;
			}

			$module->register();
			self::$modules[ $class::id() ] = $module;
		}

		/**
		 * Fires after Forma Studio Engine has registered its modules.
		 *
		 * @param array<string, Module> $modules Active modules keyed by id.
		 */
		do_action( 'forma_engine_loaded', self::$modules );
	}

	public static function module( string $id ): ?Module {
		return self::$modules[ $id ] ?? null;
	}

	public static function activate(): void {
		flush_rewrite_rules();
	}
}
