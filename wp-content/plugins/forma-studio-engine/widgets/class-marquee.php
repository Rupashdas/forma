<?php
/**
 * Marquee Widget — infinite horizontal text.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Marquee extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_marquee';
		}

		public function get_title() {
			return esc_html__( 'FORMA Marquee', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-horizontal';
		}

		public function get_keywords() {
			return array( 'forma', 'marquee', 'scroll', 'text', 'ticker' );
		}

		/**
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'section_content',
			 array(
				'label' => esc_html__( 'Content', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'text',
			 array(
				'label'       => esc_html__( 'Text', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'FORMA — architecture, interiors, objects', 'forma-studio-engine' ),
				'placeholder' => esc_html__( 'Enter marquee text', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'separator',
			 array(
				'label'       => esc_html__( 'Separator', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( '—', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'speed',
			 array(
				'label'      => esc_html__( 'Speed', 'forma-studio-engine' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 's' ),
				'range'      => array(
					's' => array( 'min' => 5, 'max' => 60, 'step' => 1 ),
				),
				'default'    => array(
					'size' => 25,
					'unit' => 's',
				),
			 )
			);

			$this->add_control(
				'direction',
			 array(
				'label'   => esc_html__( 'Direction', 'forma-studio-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'forward',
				'options' => array(
					'forward' => esc_html__( 'Forward', 'forma-studio-engine' ),
					'reverse' => esc_html__( 'Reverse', 'forma-studio-engine' ),
				),
			 )
			);

			$this->add_control(
				'pause_on_hover',
			 array(
				'label'        => esc_html__( 'Pause on Hover', 'forma-studio-engine' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			 )
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style',
			 array(
				'label' => esc_html__( 'Style', 'forma-studio-engine' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			 )
			);

			$this->add_control(
				'text_color',
			 array(
				'label'     => esc_html__( 'Text Color', 'forma-studio-engine' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .forma-marquee__track span' => 'color: {{VALUE}};',
				),
			 )
			);

			$this->add_group_control(
				\Elementor\Controls_Group_Control_Typography::get_type(),
			 array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .forma-marquee__track span',
			 )
			);

			$this->end_controls_section();
		}

		/**
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();

			$text       = $settings['text'] ?? '';
			$separator  = $settings['separator'] ?? '';
			$speed      = $settings['speed']['size'] ?? 25;
			$direction  = $settings['direction'] ?? 'forward';
			$pause      = $settings['pause_on_hover'] ?? 'yes';

			$track = sprintf( '%s %s %s %s %s', $text, $separator, $text, $separator, $text );

			?>

			<div class="forma-marquee"
			     data-speed="<?php echo esc_attr( $speed ); ?>"
			     data-direction="<?php echo esc_attr( $direction ); ?>"
			     data-pause="<?php echo esc_attr( $pause ); ?>">

				<div class="forma-marquee__track">
					<?php for ( $i = 0; $i < 2; $i++ ) : ?>
						<span><?php echo esc_html( $track ); ?></span>
					<?php endfor; ?>
				</div>
			</div>

			<?php
		}

		/**
		 * @return void
		 */
		protected function content_template() {
			?>
			<div class="forma-marquee">
				<div class="forma-marquee__track">
					<span></span>
					<span></span>
				</div>
			</div>
			<?php
		}
	}

endif;