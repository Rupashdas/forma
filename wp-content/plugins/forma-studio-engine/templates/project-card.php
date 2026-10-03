<?php
/**
 * Project card template — used by the Project Grid widget.
 * Data is passed in via $data from forma_get_project_card().
 *
 * @var array $data
 */

defined( 'ABSPATH' ) || exit;

$data = $data ?? array(
	'id'       => get_the_ID(),
	'title'    => get_the_title(),
	'link'     => get_permalink(),
	'image'    => get_the_post_thumbnail_url( get_the_ID(), 'forma-grid' ),
	'category' => '',
	'location' => '',
	'year'     => '',
);

$categories = $data['category'] ?? '';
$location   = $data['location'] ?? '';
$year       = $data['year'] ?? '';
?>

<a href="<?php echo esc_url( $data['link'] ); ?>"
   class="forma-project-card"
   data-project-id="<?php echo esc_attr( $data['id'] ); ?>"
   aria-label="<?php echo esc_attr( sprintf( '%s — %s', $data['title'], $categories ) ); ?>">

	<div class="forma-project-card__image">
		<?php if ( ! empty( $data['image'] ) ) : ?>
			<img src="<?php echo esc_url( $data['image'] ); ?>"
			     alt="<?php echo esc_attr( $data['title'] ); ?>"
			     loading="lazy"
			     width="800"
			     height="1000">
		<?php else : ?>
			<div class="forma-project-card__image-placeholder"></div>
		<?php endif; ?>
	</div>

	<div class="forma-project-card__body">
		<span class="forma-project-card__category"><?php echo esc_html( $categories ); ?></span>
		<div class="forma-project-card__title"><?php echo esc_html( $data['title'] ); ?></div>
		<div class="forma-project-card__meta">
			<?php echo esc_html( implode( ' · ', array_filter( array( $location, $year ) ) ) ); ?>
		</div>
	</div>
</a>