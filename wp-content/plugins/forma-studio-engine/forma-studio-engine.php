<?php
/**
 * Plugin Name: Forma Studio Engine
 * Description: Companion plugin for the FORMA Elementor portfolio. Registers the Project custom post type, ACF fields, custom Elementor widgets, GSAP motion, AJAX filtering, and dynamic templates. All reusable functionality belongs here — not in the child theme.
 * Version: 1.0.0
 * Author: FORMA Studio
 * Text Domain: forma-studio-engine
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

const FORMA_STUDIO_ENGINE_VERSION = '1.0.0';
const FORMA_STUDIO_ENGINE_PATH = plugin_dir_path( __FILE__ );
const FORMA_STUDIO_ENGINE_URL  = plugin_dir_url( __FILE__ );

require_once FORMA_STUDIO_ENGINE_PATH . 'includes/helpers.php';

require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-plugin.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-project-post-type.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-project-taxonomy.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-assets.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-elementor-integration.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-ajax.php';

require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-widget-base.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'includes/class-project-meta-fields.php';

// Register widgets.
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-project-grid.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-project-meta.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-project-gallery.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-before-after.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-animated-heading.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-marquee.php';
require_once FORMA_STUDIO_ENGINE_PATH . 'widgets/class-project-filter.php';

Forma\Studio\Engine\Main::instance();