<?php
/**
 * Base class for FORMA Elementor widgets.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	/**
	 * Abstract base providing shared helpers.
	 */
	abstract class Forma_Widget_Base extends \Elementor\Widget_Base {

		/**
		 * @return string
		 */
		public function get_categories() {
			return array( 'forma' );
		}

		/**
		 * @return array
		 */
		public function get_style_depends() {
			return array( 'forma-widgets' );
		}

		/**
		 * @return array
		 */
		public function get_script_depends() {
			return array();
		}

		/**
		 * Register shared controls for spacing.
		 */
		protected function register_spacing_controls() {
			$this->start_controls_section(
				'section_style',
			 array(
				'label' => esc_html__( 'FORMA Style', 'forma-studio-engine' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			 )
			);

			$this->add_control(
				'align',
			 array(
				'label'              => esc_html__( 'Alignment', 'forma-studio-engine' ),
				'type'               => \Elementor\Controls_Manager::CHOOSE,
				'options'            => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'forma-studio-engine' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'forma-studio-engine' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'forma-studio-engine' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default' => 'left',
				'selectors' => array(
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				),
			 )
			);

			$this->end_controls_section();
		}
	}

endif;