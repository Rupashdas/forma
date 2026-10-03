<?php

namespace Forma\Engine\Elementor\Widgets;

use Forma\Engine\Elementor\Module;
use Forma\Engine\Support\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Shared plumbing for Forma widgets: the Forma panel category, and style/script dependencies named after the
 * widget ("forma-marquee" → assets/css/marquee.css, assets/js/marquee.js if registered), so Elementor loads them
 * only on pages that use the widget.
 */
abstract class Base extends \Elementor\Widget_Base {

	/** Asset name, e.g. "marquee". */
	abstract protected function asset(): string;

	public function get_categories() {
		return array( Module::CATEGORY );
	}

	public function get_style_depends(): array {
		Assets::register_assets();

		return array( 'forma-' . $this->asset() );
	}

	public function get_script_depends(): array {
		Assets::register_assets();
		$handle = 'forma-' . $this->asset();

		return wp_script_is( $handle, 'registered' ) ? array( $handle ) : array();
	}

	/** One wrapper element is enough; the optimised-markup inner wrapper adds nothing for these widgets. */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}
}
