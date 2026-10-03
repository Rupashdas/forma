<?php
/**
 * Animated Heading Widget — word / character / line reveal.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Animated_Heading extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_animated_heading';
		}

		public function get_title() {
			return esc_html__( 'FORMA Animated Heading', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-animated-headline';
		}

		public function get_keywords() {
			return array( 'forma', 'heading', 'animated', 'reveal', 'text' );
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
				'label'       => esc_html__( 'Heading Text', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Designing spaces that last.', 'forma-studio-engine' ),
				'placeholder' => esc_html__( 'Enter heading text', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'html_tag',
			 array(
				'label'   => esc_html__( 'HTML Tag', 'forma-studio-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'span' => 'span',
					'div'  => 'div',
				),
			 )
			);

			$this->add_control(
				'animation_type',
			 array(
				'label'   => esc_html__( 'Animation Type', 'forma-studio-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'word',
				'options' => array(
					'word'     => esc_html__( 'Word Reveal', 'forma-studio-engine' ),
					'char'     => esc_html__( 'Character Reveal', 'forma-studio-engine' ),
					'line'     => esc_html__( 'Line Reveal', 'forma-studio-engine' ),
					'static'   => esc_html__( 'Static', 'forma-studio-engine' ),
				),
			 )
			);

			$this->add_control(
				'trigger',
			 array(
				'label'   => esc_html__( 'Trigger', 'forma-studio-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'scroll',
				'options' => array(
					'scroll' => esc_html__( 'On Scroll', 'forma-studio-engine' ),
					'load'   => esc_html__( 'On Load', 'forma-studio-engine' ),
				),
			 )
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_heading',
			 array(
				'label' => esc_html__( 'Heading Style', 'forma-studio-engine' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			 )
			);

			$this->add_control(
				'text_color',
			 array(
				'label'     => esc_html__( 'Text Color', 'forma-studio-engine' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .forma-animated-heading' => 'color: {{VALUE}};',
				),
			 )
			);

			$this->add_group_control(
				\Elementor\Controls_Group_Control_Typography::get_type(),
			 array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .forma-animated-heading',
			 )
			);

			$this->end_controls_section();
		}

		/**
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();

			$text          = $settings['text'] ?? '';
			$tag           = in_array( $settings['html_tag'], array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'div', 'p' ), true ) ? $settings['html_tag'] : 'h2';
			$animation     = $settings['animation_type'] ?? 'word';
			$trigger       = $settings['trigger'] ?? 'scroll';

			if ( 'static' === $animation ) {
				echo '<' . esc_attr( $tag ) . ' class="forma-animated-heading">' . nl2br( esc_html( $text ) ) . '</' . esc_attr( $tag ) . '>';
				return;
			}

			$classes = array(
				'forma-animated-heading',
				'forma-animated-heading--' . $animation,
				'forma-animated-heading--' . $trigger,
			);

			$words = preg_split( '/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY );

			?>

			<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			 <?php if ( 'scroll' === $trigger ) : ?>
				data-trigger="scroll"
			 <?php endif; ?>>

				<span class="forma-animated-heading__inner">
					<?php if ( 'line' === $animation ) : ?>
						<span class="forma-animated-heading__line">
							<?php echo nl2br( esc_html( $text ) ); ?>
						</span>
					<?php else : ?>
						<?php foreach ( $words as $index => $word ) : ?>
							<span class="forma-animated-heading__word" data-index="<?php echo esc_attr( $index ); ?>">
								<?php if ( 'char' === $animation ) : ?>
									<?php
									$chars = mb_str_split( $word );
									foreach ( $chars as $char ) :
										?>
										<span class="forma-animated-heading__char"><?php echo esc_html( $char ); ?></span>
									<?php endforeach; ?>
									<span class="forma-animated-heading__space"> </span>
									<?php
									else :
										?>
										<span class="forma-animated-heading__word-text"><?php echo esc_html( $word ); ?></span>
										<span class="forma-animated-heading__space"> </span>
									<?php
								 endif; ?>
								</span>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php endif; ?>
				</span>
			</<?php echo esc_attr( $tag ); ?>>

			<?php
		}

		/**
		 * @return void
		 */
		protected function content_template() {
			?>
			<{{{ settings.html_tag }}} class="forma-animated-heading forma-animated-heading--word">
				<span class="forma-animated-heading__inner"></span>
			</{{{ settings.html_tag }}}>
			<?php
		}
	}

endif;