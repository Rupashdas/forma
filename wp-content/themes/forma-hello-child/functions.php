<?php
/**
 * Forma Hello Child — functions.php
 *
 * Theme-level presentation hooks only.
 * All functionality (CPT, ACF, widgets, GSAP, AJAX) lives in
 * the companion plugin: Forma Studio Engine.
 *
 * @package Forma
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue theme-level styles.
 */
function forma_enqueue_styles() {
	$theme = wp_get_theme();
	$version = $theme->get( 'Version' );

	// Parent theme first.
	if ( ! style_is_registered( 'hello-elementor' ) ) {
		return;
	}

	// Parent style.
	wp_enqueue_style(
		'hello-elementor',
		get_template_directory_uri() . '/style.css',
		array(),
		$version
	);

	// Child style — load after parent so it wins.
	wp_enqueue_style(
		'forma-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'hello-elementor' ),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'forma_enqueue_styles' );

/**
 * Register theme-level text domains / nav menus if needed.
 */
function forma_register_menus() {
	register_nav_menus(
	 array(
		'forma-primary'   => esc_html__( 'Primary', 'forma-hello-child' ),
		'forma-footer'    => esc_html__( 'Footer', 'forma-hello-child' ),
	 )
	);
}
add_action( 'init', 'forma_register_menus' );

/**
 * Add theme support for editorial image sizes used by FORMA.
 */
function forma_add_theme_support() {
	add_theme_support( 'post thumbnails' );
	add_image_size( 'forma-hero', 1920, 1080, true );
	add_image_size( 'forma-grid', 800, 1000, true );
	add_image_size( 'forma-single', 1600, 900, true );
	add_image_size( 'forma-thumb', 400, 500, true );
}
add_action( 'after_setup_theme', 'forma_add_theme_support' );

/**
 * Output an accessible "back to archive" link for single project pages.
 * Kept here because it is presentation-level navigation markup.
 */
function forma_prev_project_link() {
	$prev = get_previous_post(
	 array(
		'taxonomy' => 'project_category',
		'post_type' => 'forma_project',
	 )
	);
	if ( ! $prev ) {
		return;
	}

	$thumb = get_the_post_thumbnail( $prev->ID, 'forma-thumb' );
	?>

	<a href="<?php echo esc_url( get_permalink( $prev->ID ) ); ?>"
	   class="forma-prev-link"
	   aria-label="<?php echo esc_attr__( 'Previous project', 'forma-hello-child' ); ?>">
		<span class="forma-prev-link__label"><?php echo esc_html__( 'Previous', 'forma-hello-child' ); ?></span>
		<span class="forma-prev-link__title"><?php echo esc_html( get_the_title( $prev->ID ) ); ?></span>
		<?php if ( $thumb ) : ?>
			<span class="forma-prev-link__thumb"><?php echo wp_specialchars_decode( $thumb, ENT_QUOTES | ENT_SUBSTITUTE ); ?></span>
		<?php endif; ?>
	</a>

	<?php
}