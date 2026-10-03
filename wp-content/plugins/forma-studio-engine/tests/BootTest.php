<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class BootTest extends TestCase {

	public function test_plugin_constants_are_defined(): void {
		$this->assert_true( defined( 'FORMA_ENGINE_VERSION' ), 'FORMA_ENGINE_VERSION is defined (is the plugin active?)' );
		$this->assert_same( '1.0.0', FORMA_ENGINE_VERSION );
		$this->assert_true( is_dir( FORMA_ENGINE_PATH . 'src' ), 'FORMA_ENGINE_PATH points at the plugin folder' );
	}

	public function test_autoloader_resolves_plugin_classes(): void {
		$this->assert_true( class_exists( \Forma\Engine\Plugin::class ), 'Forma\Engine\Plugin autoloads' );
		$this->assert_true( interface_exists( \Forma\Engine\Contracts\Module::class ), 'Module contract autoloads' );
	}

	public function test_boot_fires_loaded_action(): void {
		$this->assert_true( did_action( 'forma_engine_loaded' ) > 0, 'forma_engine_loaded has fired' );
	}
}
