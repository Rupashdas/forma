<?php
/**
 * Project meta template — used by the Project Meta widget.
 *
 * @var int $post_id
 */

defined( 'ABSPATH' ) || exit;

$post_id = $post_id ?? get_the_ID();

$items = array(
	'client'    => esc_html__( 'Client', 'forma-studio-engine' ),
	'location'  => esc_html__( 'Location', 'forma-studio-engine' ),
	'year'      => esc_html__( 'Year', 'forma-studio-engine' ),
	'area'      => esc_html__( 'Area', 'forma-studio-engine' ),
	'type'      => esc_html__( 'Type', 'forma-studio-engine' ),
	'status'    => esc_html__( 'Status', 'forma-studio-engine' ),
	'architect' => esc_html__( 'Architect', 'forma-studio-engine' ),
	'designer'  => esc_html__( 'Designer', 'forma-studio-engine' ),
);

$has_value = false;
foreach ( $items as $key => $label ) {
	 if ( forma_get_field( $key, $post_id ) ) {
		$has_value = true;
		break;
	}
}

if ( ! $has_value ) {
	echo '<p>' . esc_html__( 'No metadata available.', 'forma-studio-engine' ) . '</p>';
	return;
}
?>

<dl class="forma-project-meta">
	<?php foreach ( $items as $key => $label ) :
		$value = forma_get_field( $key, $post_id );
		if ( ! $value ) {
			continue;
		}
		?>
		<div class="forma-project-meta__row">
			<dt><?php echo esc_html( $label ); ?></dt>
			<dd><?php echo esc_html( $value ); ?></dd>
		</div>
	<?php endforeach; ?>
</dl>