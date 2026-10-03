<?php
/**
 * Projects archive template override.
 * Data-driven via Forma Studio Engine widgets. This template is
 * presentation-level only; filtering and grid logic live in the plugin.
 *
 * @package Forma
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>
<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'forma-projects-archive' ); ?>>

	<main id="content" class="forma-projects">
		<!-- Page intro -->
		<header class="forma-projects__header">
			<div class="forma-container">
				<span class="forma-projects__eyebrow"><?php echo esc_html__( 'Selected Works', 'forma-hello-child' ); ?></span>
				<h1 class="forma-projects__title"><?php echo esc_html__( 'Projects', 'forma-hello-child' ); ?></h1>
				<p class="forma-projects__intro">
					<?php echo esc_html__( 'A curated selection of architecture, interiors, and objects.', 'forma-hello-child' ); ?>
				</p>
			</div>
		</header>

		<!-- Filter + grid -->
		<div class="forma-container">
			<?php echo do_shortcode( '[project_filter taxonomy="project_category"]' ); ?>
			<?php echo do_shortcode( '[project_grid columns="3" posts_per_page="12"]' ); ?>
		</div>
	</main>

	<?php get_footer(); ?>
</body>
</html>
<?php