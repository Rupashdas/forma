<?php

namespace Forma\Engine\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Drawing / Built: two aligned images and a native range input that reveals the second over the first.
 * The range does the work (keyboard, touch, screen readers); a few lines of JS only mirror its value into CSS.
 */
final class BeforeAfter extends Base {

	protected function asset(): string {
		return 'before-after';
	}

	public function get_name() {
		return 'forma-before-after';
	}

	public function get_title() {
		return esc_html__( 'Drawing / Built', 'forma-studio-engine' );
	}

	public function get_icon() {
		return 'eicon-image-before-after';
	}

	public function get_keywords() {
		return array( 'before', 'after', 'compare', 'drawing', 'slider', 'forma' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_images', array( 'label' => esc_html__( 'Images', 'forma-studio-engine' ) ) );

		$this->add_control(
			'before_image',
			array(
				'label' => esc_html__( 'Before (underneath)', 'forma-studio-engine' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'before_label',
			array(
				'label'   => esc_html__( 'Before label', 'forma-studio-engine' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Drawing', 'forma-studio-engine' ),
			)
		);

		$this->add_control(
			'after_image',
			array(
				'label'     => esc_html__( 'After (revealed)', 'forma-studio-engine' ),
				'type'      => Controls_Manager::MEDIA,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'after_label',
			array(
				'label'   => esc_html__( 'After label', 'forma-studio-engine' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Built', 'forma-studio-engine' ),
			)
		);

		$this->add_control(
			'start',
			array(
				'label'      => esc_html__( 'Starting reveal', 'forma-studio-engine' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => '%',
					'size' => 50,
				),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => esc_html__( 'Image size', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => array(
					'forma-960' => esc_html__( 'Half width (960)', 'forma-studio-engine' ),
					'large'     => esc_html__( 'Large (1600)', 'forma-studio-engine' ),
					'full'      => esc_html__( 'Full (2400)', 'forma-studio-engine' ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$before   = (int) ( $settings['before_image']['id'] ?? 0 );
		$after    = (int) ( $settings['after_image']['id'] ?? 0 );

		if ( ! $before || ! $after ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Choose both images to show the comparison.', 'forma-studio-engine' ) . '</p>';
			}

			return;
		}

		$size        = in_array( $settings['image_size'] ?? '', array( 'forma-960', 'large', 'full' ), true ) ? $settings['image_size'] : 'large';
		$start       = (int) round( min( 100, max( 0, (float) ( $settings['start']['size'] ?? 50 ) ) ) );
		$after_label = (string) ( $settings['after_label'] ?? '' );
		$image       = static fn( int $id, string $class ) => wp_get_attachment_image(
			$id,
			$size,
			false,
			array(
				'class'    => $class,
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		?>
		<figure class="forma-ba" style="--forma-ba:<?php echo esc_attr( $start ); ?>%" data-after="<?php echo esc_attr( $after_label ); ?>">
			<div class="forma-ba__frame">
				<?php echo $image( $before, 'forma-ba__before' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup. ?>
				<?php echo $image( $after, 'forma-ba__after' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup. ?>
				<input class="forma-ba__range" type="range" min="0" max="100" step="1" value="<?php echo esc_attr( $start ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: label of the revealed image, e.g. Built. */ __( 'Reveal: %s', 'forma-studio-engine' ), $after_label ) ); ?>" aria-valuetext="<?php echo esc_attr( $start . '% ' . $after_label ); ?>">
				<span class="forma-ba__handle" aria-hidden="true"></span>
				<span class="forma-ba__label forma-ba__label--after"><?php echo esc_html( $after_label ); ?></span>
				<span class="forma-ba__label forma-ba__label--before"><?php echo esc_html( (string) ( $settings['before_label'] ?? '' ) ); ?></span>
			</div>
		</figure>
		<?php
	}
}
