<?php
/**
 * Project Grid Widget — dynamic project listing.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Project_Grid extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_project_grid';
		}

		public function get_title() {
			return esc_html__( 'FORMA Project Grid', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-gallery-grid';
		}

		public function get_keywords() {
			return array( 'forma', 'portfolio', 'projects', 'grid' );
		}

		/**
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'section_query',
			 array(
				'label' => esc_html__( 'Query', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'columns',
			 array(
				'label'   => esc_html__( 'Columns', 'forma-studio-engine' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'2' => esc_html__( '2', 'forma-studio-engine' ),
					'3' => esc_html__( '3', 'forma-studio-engine' ),
					'4' => esc_html__( '4', 'forma-studio-engine' ),
				),
			 )
			);

			$this->add_control(
				'posts_per_page',
			 array(
				'label'       => esc_html__( 'Posts Per Page', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 12,
				'min'         => 1,
				'max'         => 60,
			 )
			);

			$this->add_control(
				'category',
			 array(
				'label'       => esc_html__( 'Categories', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_category_options(),
				'description' => esc_html__( 'Leave empty to show all.', 'forma-studio-engine' ),
			 )
			);

			$this->add_control(
				'load_more',
			 array(
				'label'        => esc_html__( 'Load More', 'forma-studio-engine' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			 )
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'section_style_grid',
			 array(
				'label' => esc_html__( 'Grid Style', 'forma-studio-engine' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			 )
			);

			$this->add_control(
				'gap',
			 array(
				'label'      => esc_html__( 'Gap', 'forma-studio-engine' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 100 ),
				),
				'default'    => array(
					'size' => 32,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .forma-project-grid' => 'gap: {{SIZE}}{{UNIT}};',
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

			$columns     = $settings['columns'] ?? '3';
			$posts_per_page = $settings['posts_per_page'] ?? 12;
			$categories  = $settings['category'] ?? array();
			$load_more   = $settings['load_more'] ?? 'yes';

			$query_args = array(
				'post_type'   => 'forma_project',
				'post_status' => 'publish',
				'posts_per_page' => $posts_per_page,
			);

			if ( ! empty( $categories ) ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'project_category',
						'terms'    => array_map( 'absint', $categories ),
						'field'    => 'id',
						'operator' => 'IN',
					),
				);
			}

			$loop = new \WP_Query( $query_args );

			?>

			<div class="forma-project-grid forma-grid-cols-<?php echo esc_attr( $columns ); ?>"
			     data-load-more="<?php echo esc_attr( $load_more ); ?>"
			     data-page="1"
			     data-per-page="<?php echo esc_attr( $posts_per_page ); ?>"
			     data-nonce="<?php echo esc_attr( wp_create_nonce( 'forma_filter_projects' ) ); ?>">

				<?php if ( $loop->have_posts() ) : ?>
					<div class="forma-project-grid__inner">
						<?php
						while ( $loop->have_posts() ) :
							$loop->the_post();
							forma_render_project_card( get_the_ID() );
						endwhile;
						?>
					</div>

					<?php if ( $load_more === 'yes' && $loop->max_num_pages > 1 ) : ?>
						<div class="forma-project-grid__load-more">
							<button class="forma-project-grid__load-more-btn" data-action="load-more">
								<?php echo esc_html__( 'Load more projects', 'forma-studio-engine' ); ?>
							</button>
						</div>
					<?php endif; ?>
				<?php
					else :
						echo '<p>' . esc_html__( 'No projects found.', 'forma-studio-engine' ) . '</p>';
				 endif;
					?>
			</div>

			<?php
			wp_reset_postdata();
		}

		/**
		 * @return void
		 */
		protected function content_template() {
			?>
			<div class="forma-project-grid forma-grid-cols-{{ settings.columns }}">
				<div class="forma-project-grid__inner">
					<div class="forma-project-card">
						<div class="forma-project-card__image"></div>
						<div class="forma-project-card__body">
							<div class="forma-project-card__title"></div>
							<div class="forma-project-card__meta"></div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * @return array
		 */
		private function get_category_options() {
			$terms = get_terms(
			 array(
				'taxonomy'   => 'project_category',
				'hide_empty' => false,
			 )
			);
			$options = array();
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
			return $options;
		}
	}

endif;