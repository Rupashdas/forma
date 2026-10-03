<?php

namespace Forma\Engine\Transitions;

use Forma\Engine\Contracts\Module;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * Page transitions without JavaScript: CSS cross-document View Transitions crossfade every navigation, and the
 * project image morphs from card to hero where the Forma Motion "morph" switch names it on both pages.
 * Browsers without support simply navigate as usual; reduced motion turns it off.
 */
final class Transitions implements Module {

	public static function id(): string {
		return 'transitions';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_action( 'wp_head', array( $this, 'head' ), 3 );
	}

	public function head(): void {
		if ( is_admin() || is_feed() || Editor::active() ) {
			return;
		}

		echo '<style id="forma-transitions">'
			. '@view-transition{navigation:auto}'
			. '::view-transition-old(root),::view-transition-new(root){animation-duration:.35s}'
			. '::view-transition-group(*){animation-duration:.6s;animation-timing-function:cubic-bezier(.22,1,.36,1)}'
			. '@media (prefers-reduced-motion: reduce){@view-transition{navigation:none}}'
			. "</style>\n";
	}
}
