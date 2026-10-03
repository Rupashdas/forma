<?php
/**
 * Plugin Name:       Forma Studio Engine
 * Description:       Functionality for the FORMA studio site: the Projects post type, custom Elementor widgets and the Forma Motion extension, GSAP motion, view transitions, media and SEO, plus the WP-CLI build.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  elementor
 * Author:            Rupash Das
 * Author URI:        https://devrupash.com
 * License:           GPL-2.0-or-later
 * Text Domain:       forma-studio-engine
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMA_ENGINE_VERSION', '1.0.0' );
define( 'FORMA_ENGINE_FILE', __FILE__ );
define( 'FORMA_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORMA_ENGINE_URL', plugin_dir_url( __FILE__ ) );

require_once FORMA_ENGINE_PATH . 'src/Autoloader.php';

Forma\Engine\Autoloader::register( 'Forma\\Engine\\', FORMA_ENGINE_PATH . 'src/' );

add_action( 'plugins_loaded', array( Forma\Engine\Plugin::class, 'boot' ), 20 );

register_activation_hook( __FILE__, array( Forma\Engine\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
