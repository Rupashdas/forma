<?php

namespace Forma\Engine\Model;

use Forma\Engine\Contracts\Module;
use Forma\Engine\Support\Assets;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * The 3D side of the site: registers the study-model runtime and its self-hosted Three.js as script modules, the
 * style, and prints the JSON every swapping view reads its models from.
 *
 * Nothing loads on a page until a study-model widget (or the editor) asks for it.
 */
final class Model implements Module {

	/** Version of the vendored Three.js build in assets/vendor/three/ (MIT, ES module). */
	public const THREE_VERSION = '0.186.1';

	private static bool $json_queued = false;

	public static function id(): string {
		return 'model';
	}

	public function is_available(): bool {
		return function_exists( 'wp_register_script_module' );
	}

	public function register(): void {
		add_action( 'init', array( self::class, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'editor_preview' ), 20 );
	}

	/**
	 * Script modules: `three`, the model runtime (which imports it), the loader that fetches the runtime after the page has loaded, and the stylesheet.
	 */
	public static function register_assets(): void {
		wp_register_script_module( 'three', FORMA_ENGINE_URL . 'assets/vendor/three/three.module.min.js', array(), self::THREE_VERSION );
		wp_register_script_module( 'forma-model-runtime', FORMA_ENGINE_URL . 'assets/js/model-stage.js', array( 'three' ), Assets::version( 'assets/js/model-stage.js' ) );
		// The loader is what pages enqueue; it imports the runtime (and so Three.js) once the page has loaded.
		wp_register_script_module( 'forma-model-stage', FORMA_ENGINE_URL . 'assets/js/model-stage-loader.js', array( array( 'id' => 'forma-model-runtime', 'import' => 'dynamic' ) ), Assets::version( 'assets/js/model-stage-loader.js' ) );
		Assets::register_style( 'forma-study-model', 'assets/css/study-model.css' );
	}

	/**
	 * Load the runtime and its style. GSAP and ScrollTrigger come along for the scroll behaviours (the runtime
	 * works without them, minus any scroll control).
	 */
	public static function enqueue( bool $scroll = false ): void {
		if ( ! wp_script_is( 'forma-gsap', 'registered' ) ) {
			Assets::register_assets();
		}

		wp_enqueue_script_module( 'forma-model-stage' );
		wp_enqueue_style( 'forma-study-model' );

		if ( $scroll ) {
			wp_enqueue_script( 'forma-gsap' );
			wp_enqueue_script( 'forma-scrolltrigger' );
		}
	}

	/**
	 * Ask for the JSON of every project's model, printed once in the footer as
	 * `<script type="application/json" id="forma-models">{ "<post id>": { id, slug, title, url, number, volumes } }</script>`.
	 */
	public static function need_projects_json(): void {
		if ( self::$json_queued ) {
			return;
		}

		self::$json_queued = true;
		add_action( 'wp_footer', array( self::class, 'print_projects_json' ), 5 );
	}

	public static function print_projects_json(): void {
		$models = array();

		foreach ( Models::all_projects() as $id => $model ) {
			$models[ (string) $id ] = $model;
		}

		printf(
			'<script type="application/json" id="forma-models">%s</script>' . "\n",
			wp_json_encode( (object) $models, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with tag and ampersand escapes.
		);
	}

	/**
	 * In Elementor's editor preview widgets are rendered by AJAX after the page has loaded, too late for the
	 * footer to print their assets, so the preview loads everything up front.
	 */
	public function editor_preview(): void {
		if ( Editor::active() ) {
			self::enqueue( true );
			self::need_projects_json();
		}
	}
}
