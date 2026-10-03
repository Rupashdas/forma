<?php

namespace Forma\Engine\Contracts;

defined( 'ABSPATH' ) || exit;

interface Module {

	/**
	 * Stable key for the module, used by Plugin::module().
	 */
	public static function id(): string;

	/**
	 * Whether the module's dependencies are present.
	 */
	public function is_available(): bool;

	/**
	 * Attach hooks. Called once, only when available.
	 */
	public function register(): void;
}
