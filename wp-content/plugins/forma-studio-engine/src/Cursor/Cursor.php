<?php

namespace Forma\Engine\Cursor;

use Forma\Engine\Contracts\Module;
use Forma\Engine\Support\Assets;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * A small Sodium dot that trails the pointer and grows into a labelled disc over elements with a cursor label
 * (set in the Forma Motion panel or by Forma widgets). Fine pointers only; the native cursor is never hidden.
 */
final class Cursor implements Module {

	public static function id(): string {
		return 'cursor';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
	}

	public function enqueue(): void {
		if ( ! Editor::active() ) {
			Assets::enqueue( 'cursor' );
		}
	}
}
