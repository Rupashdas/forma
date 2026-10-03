<?php
/**
 * Single Project template override.
 *
 * This is a presentation-level template. Project data is read via ACF
 * fields handled by the companion plugin. Elementor is used to compose
 * the body when the post meta flag `_elementor_edit_mode` exists; otherwise
 * this template renders the editorial layout directly.
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
<body <?php body_class( 'forma-single-project' ); ?> data-project-id="<?php echo esc_attr( get_the_ID() ); ?>">

	<main id="content" class="forma-project">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();

				$hero_image = get_field( 'hero_image' );
				$gallery    = get_field( 'project_gallery' );
				$before     = get_field( 'before_image' );
				$after      = get_field( 'after_image' );
				?>

				<!-- 1. Project hero -->
				<section class="forma-project-hero">
					<div class="forma-container">
						<div class="forma-project-hero__inner">
							<div class="forma-project-hero__meta">
								<span class="forma-project-hero__category">
									<?php
									$terms = get_the_terms( get_the_ID(), 'project_category' );
									if ( $terms && ! is_wp_error( $terms ) ) :
										echo esc_html( implode( ' / ', array_map( 'strtoupper', wp_list_pluck( $terms, 'name' ) ) ) );
									else :
										echo esc_html__( 'Project', 'forma-hello-child' );
								 endif; ?>
								</span>
								<h1 class="forma-project-hero__title"><?php the_title(); ?></h1>
							</div>
							<?php if ( $hero_image ) : ?>
								<div class="forma-project-hero__image">
									<img src="<?php echo esc_url( $hero_image['url'] ); ?>"
									     alt="<?php echo esc_attr( $hero_image['alt'] ?? get_the_title() ); ?>"
									     loading="eager"
									     width="<?php echo esc_attr( $hero_image['width'] ); ?>"
									     height="<?php echo esc_attr( $hero_image['height'] ); ?>">
								</div>
							<?php endif; ?>
						</div>
					</div>
				</section>

				<!-- 3. Metadata -->
				<section class="forma-project-meta">
					<div class="forma-container">
						<?php echo do_shortcode( '[project_meta project_id="' . get_the_ID() . '"]' ); ?>
					</div>
				</section>

				<!-- 4. Intro -->
				<?php $intro = get_field( 'short_description' ); ?>
				<?php if ( $intro ) : ?>
					<section class="forma-project-intro">
						<div class="forma-container">
							<p class="forma-project-intro__text"><?php echo nl2br( esc_html( $intro ) ); ?></p>
						</div>
					</section>
				<?php endif; ?>

				<!-- 5. Full-width image -->
				<?php if ( $gallery && is_array( $gallery ) && ! empty( $gallery ) ) : ?>
					<section class="forma-project-gallery">
						<div class="forma-container">
							<?php echo do_shortcode( '[project_gallery project_id="' . get_the_ID() . '"]' ); ?>
						</div>
					</section>
				<?php endif; ?>

				<!-- 6. Narrative -->
				<?php $challenge = get_field( 'challenge' ); ?>
				<?php $approach  = get_field( 'approach' ); ?>
				<?php $result    = get_field( 'result' ); ?>
				<?php if ( $challenge || $approach || $result ) : ?>
					<section class="forma-project-narrative">
						<div class="forma-container">
							<div class="forma-project-narrative__grid">
								<?php if ( $challenge ) : ?>
									<div class="forma-project-narrative__block">
										<span class="forma-project-narrative__label"><?php echo esc_html__( 'Challenge', 'forma-hello-child' ); ?></span>
										<p><?php echo nl2br( esc_html( $challenge ) ); ?></p>
									</div>
								<?php endif; ?>
								<?php if ( $approach ) : ?>
									<div class="forma-project-narrative__block">
										<span class="forma-project-narrative__label"><?php echo esc_html__( 'Approach', 'forma-hello-child' ); ?></span>
										<p><?php echo nl2br( esc_html( $approach ) ); ?></p>
									</div>
								<?php endif; ?>
								<?php if ( $result ) : ?>
									<div class="forma-project-narrative__block">
										<span class="forma-project-narrative__label"><?php echo esc_html__( 'Result', 'forma-hello-child' ); ?></span>
										<p><?php echo nl2br( esc_html( $result ) ); ?></p>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</section>
								<?php endif; ?>

				<!-- 9. Before / After -->
				<?php if ( $before && $after ) : ?>
					<section class="forma-project-before-after">
						<div class="forma-container">
							<?php echo do_shortcode( '[before_after before_image="' . esc_attr( $before['url'] ) . '" after_image="' . esc_attr( $after['url'] ) . '"]' ); ?>
						</div>
					</section>
				<?php endif; ?>

				<!-- 12. Next project -->
				<section class="forma-project-next">
					<div class="forma-container">
						<?php forma_prev_project_link(); ?>
					</div>
				</section>
			<?php
			endwhile;
		else :
			?>
			<p><?php echo esc_html__( 'Project not found.', 'forma-hello-child' ); ?></p>
		<?php
	 endif; ?>
	</main>

	<?php get_footer(); ?>
</body>
</html>
<?php
// No wp_get_footer() — keep full document for editorial control.