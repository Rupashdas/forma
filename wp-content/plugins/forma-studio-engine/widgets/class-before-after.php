<?php
/**
 * Before / After image comparison widget.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Before_After extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_before_after';
		}

		public function get_title() {
			return esc_html__( 'FORMA Before / After', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-image-compare';
		}

		public function get_keywords() {
			return array( 'forma', 'before', 'after', 'compare', 'slider' );
		}

		/**
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'section_content',
			 array(
				'label' => esc_html__( 'Images', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'before_image',
			 array(
				'label'       => esc_html__( 'Before Image', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'default'     => array(),
			 )
			);

			$this->add_control(
				'before_label',
			 array(
				'label'       => esc_html__( 'Before Label', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Before', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'after_image',
			 array(
				'label'       => esc_html__( 'After Image', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'default'     => array(),
			 )
			);

			$this->add_control(
				'after_label',
			 array(
				'label'       => esc_html__( 'After Label', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'After', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'starting_position',
			 array(
				'label'      => esc_html__( 'Starting Position (%)', 'forma-studio-engine' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array( 'min' => 0, 'max' => 100 ),
				),
				'default'    => array(
					'size' => 50,
					'unit' => '%',
				),
			 )
			);

			$this->end_controls_section();
		}

		/**
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();

			$before = $settings['before_image'] ?? array();
			$after  = $settings['after_image'] ?? array();

			if ( empty( $before['url'] ) || empty( $after['url'] ) ) {
				echo '<p>' . esc_html__( 'Please add both before and after images.', 'forma-studio-engine' ) . '</p>';
				return;
			}

			$before_label = $settings['before_label'] ?? '';
			$after_label  = $settings['after_label'] ?? '';
			$start        = $settings['starting_position']['size'] ?? 50;

			?>

			<div class="forma-before-after"
			     data-start="<?php echo esc_attr( $start ); ?>"
			     data-before-label="<?php echo esc_attr( $before_label ); ?>"
			     data-after-label="<?php echo esc_attr( $after_label ); ?>">

				<div class="forma-before-after__before">
					<img src="<?php echo esc_url( $before['url'] ); ?>"
					     alt="<?php echo esc_attr( $before['alt'] ?? $before_label ); ?>"
					     loading="lazy"
					     width="<?php echo esc_attr( $before['width'] ?? 1600 ); ?>"
					     height="<?php echo esc_attr( $before['height'] ?? 900 ); ?>">
					<span class="forma-before-after__label forma-before-after__label--before">
						<?php echo esc_html( $before_label ); ?>
					</span>
				</div>

				<div class="forma-before-after__after">
					<img src="<?php echo esc_url( $after['url'] ); ?>"
					     alt="<?php echo esc_attr( $after['alt'] ?? $after_label ); ?>"
					     loading="lazy"
					     width="<?php echo esc_attr( $after['width'] ?? 1600 ); ?>"
					     height="<?php echo esc_attr( $after['height'] ?? 900 ); ?>">
					<span class="forma-before-after__label forma-before-after__label--after">
						<?php echo esc_html( $after_label ); ?>
					</span>
				</div>

				<div class="forma-before-after__handle" style="left: <?php echo esc_attr( $start ); ?>%;">
					<span class="forma-before-after__handle-line"></span>
				</div>
			</div>

			<?php
		}

		/**
		 * @return void
		 */
		protected function content_template() {
			?>
			<div class="forma-before-after">
				<div class="forma-before-after__before">
					<img src="" alt="">
				</div>
				<div class="forma-before-after__after">
					<img src="" alt="">
				</div>
				<div class="forma-before-after__handle" style="left: 50%;"></div>
			</div>
			<?php
		}
	}

endif;