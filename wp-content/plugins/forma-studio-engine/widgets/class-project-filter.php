<?php
/**
 * Project Filter Widget — category/location filtering with AJAX.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Project_Filter extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_project_filter';
		}

		public function get_title() {
			return esc_html__( 'FORMA Project Filter', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-filter';
		}

		public function get_keywords() {
			return array( 'forma', 'filter', 'projects', 'categories' );
		}

		/**
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'section_content',
			 array(
				'label' => esc_html__( 'Settings', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'taxonomy',
			 array(
				'label'   => esc_html__( 'Taxonomy', 'forma-studio-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'project_category',
				'options' => array(
					'project_category' => esc_html__( 'Category', 'forma-studio-engine' ),
					'project_location' => esc_html__( 'Location', 'forma-studio-engine' ),
				),
			 )
			);

			$this->add_control(
				'show_search',
			 array(
				'label'        => esc_html__( 'Show Search', 'forma-studio-engine' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			 )
			);

			$this->add_control(
				'show_all',
			 array(
				'label'        => esc_html__( 'Show "All"', 'forma-studio-engine' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			 )
			);

			$this->add_control(
				'ajax',
			 array(
				'label'        => esc_html__( 'AJAX Filtering', 'forma-studio-engine' ),
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
				'align',
			 array(
				'label'              => esc_html__( 'Alignment', 'forma-studio-engine' ),
				'type'               => \Elementor\Controls_Manager::CHOOSE,
				'options'            => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'forma-studio-engine' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'forma-studio-engine' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'forma-studio-engine' ), 'icon' => 'eicon-text-align-right' ),
				),
				'default' => 'left',
				'selectors' => array(
					'{{WRAPPER}} .forma-project-filter' => 'justify-content: {{VALUE}};',
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

			$taxonomy  = $settings['taxonomy'] ?? 'project_category';
			$show_all  = $settings['show_all'] ?? 'yes';
			$show_search = $settings['show_search'] ?? 'yes';
			$ajax      = $settings['ajax'] ?? 'yes';

			$terms = get_terms(
			 array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			 )
			);

			?>

			<div class="forma-project-filter"
			     data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
			     data-ajax="<?php echo esc_attr( $ajax ); ?>"
			     data-nonce="<?php echo esc_attr( wp_create_nonce( 'forma_filter_projects' ) ); ?>">

				<?php if ( $show_search === 'yes' ) : ?>
					<div class="forma-project-filter__search">
						<input type="search"
						       class="forma-project-filter__search-input"
						       placeholder="<?php echo esc_attr__( 'Search projects...', 'forma-studio-engine' ); ?>"
						       aria-label="<?php echo esc_attr__( 'Search projects', 'forma-studio-engine' ); ?>">
					</div>
				<?php endif; ?>

				<div class="forma-project-filter__chips" role="tablist">
					<?php if ( $show_all === 'yes' ) : ?>
						<button class="forma-project-filter__chip is-active"
						        data-filter="all"
						        role="tab" aria-selected="true">
							<?php echo esc_html__( 'All', 'forma-studio-engine' ); ?>
						</button>
					<?php endif; ?>

					<?php foreach ( $terms as $term ) : ?>
						<button class="forma-project-filter__chip"
						        data-filter="<?php echo esc_attr( $term->term_id ); ?>"
						        role="tab" aria-selected="false">
							<?php echo esc_html( $term->name ); ?>
							<span class="forma-project-filter__count">(<?php echo esc_html( $term->count ); ?>)</span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<?php
		}

		/**
		 * @return void
		 */
		protected function content_template() {
			?>
			<div class="forma-project-filter">
				<div class="forma-project-filter__search">
					<input type="search" class="forma-project-filter__search-input" placeholder="Search projects...">
				</div>
				<div class="forma-project-filter__chips">
					<button class="forma-project-filter__chip is-active" data-filter="all">All</button>
				</div>
			</div>
			<?php
		}
	}

endif;