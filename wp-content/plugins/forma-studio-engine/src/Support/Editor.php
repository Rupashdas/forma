<?php

namespace Forma\Engine\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request is Elementor's editor or its preview frame, where front-end behaviour
 * (motion, cursor, page transitions) must stay out of the editor's way.
 */
final class Editor {

	public static function active(): bool {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		return $elementor->preview->is_preview_mode() || $elementor->editor->is_edit_mode();
	}
}
