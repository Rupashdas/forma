<?php

namespace Forma\Engine\Motion;

use Forma\Engine\Contracts\Module;
use Forma\Engine\Support\Assets;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * Smooth scrolling on the front end of every page: Lenis (self-hosted, MIT) eases the wheel into the page's own
 * scroll, driven from GSAP's ticker so ScrollTrigger stays in step. The runtime (assets/js/smooth-scroll.js) stands
 * down for reduced motion and for touch-only devices, and it never starts inside Elementor's editor, so nothing is
 * lost when it does not run: the page just scrolls natively.
 */
final class Smooth implements Module {

	public static function id(): string {
		return 'smooth';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
	}

	public function enqueue(): void {
		if ( is_admin() || is_feed() || Editor::active() ) {
			return;
		}

		Assets::enqueue( 'smooth-scroll' );
	}
}
