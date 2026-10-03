<?php
/**
 * Project Gallery Widget — masonry/lightbox gallery.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Project_Gallery extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_project_gallery';
		}

		public function get_title() {
			return esc_html__( 'FORMA Project Gallery', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-gallery';
		}

		public function get_keywords() {
			return array( 'forma', 'gallery', 'images', 'masonry' );
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
				'post_id',
			 array(
				'label'       => esc_html__( 'Project ID', 'forma-studio-engine' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'description' => esc_html__( 'Leave empty to use the current project.', 'forma-studio-engine' ),
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
				'enable_lightbox',
			 array(
				'label'        => esc_html__( 'Lightbox', 'forma-studio-engine' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			 )
			);

			$this->end_controls_section();
		}

		/**
		 * @return void
		 */
		protected function render() {
			$settings   = $this->get_settings_for_display();
			$post_id    = ! empty( $settings['post_id'] ) ? absint( $settings['post_id'] ) : get_the_ID();
			$columns    = $settings['columns'] ?? '3';
			$lightbox   = $settings['enable_lightbox'] ?? 'yes';

			$gallery = forma_get_field( 'project_gallery', $post_id );
			if ( ! $gallery || ! is_array( $gallery ) ) {
				echo '<p>' . esc_html__( 'No gallery images.', 'forma-studio-engine' ) . '</p>';
				return;
			}

			?>

			<div class="forma-project-gallery forma-grid-cols <?php echo esc_attr( $columns ); ?>"
			     data-lightbox="<?php echo esc_attr( $lightbox ); ?>">

				<div class="forma-project-gallery__inner">
					<?php foreach ( $gallery as $index => $image ) :
						if ( ! is_array( $image ) || empty( $image['url'] ) ) {
							continue;
						}
						$caption = $image['caption'] ?? '';
						?>
						<figure class="forma-project-gallery__item" data-index="<?php echo esc_attr( $index ); ?>">
							<img src="<?php echo esc_url( $image['url'] ); ?>"
							     alt="<?php echo esc_attr( $image['alt'] ?? $caption ); ?>"
							     loading="lazy"
							     width="<?php echo esc_attr( $image['width'] ?? 800 ); ?>"
							     height="<?php echo esc_attr( $image['height'] ?? 1000 ); ?>">
							<?php if ( $caption ) : ?>
								<figcaption><?php echo esc_html( $caption ); ?></figcaption>
							<?php endif; ?>
						</figure>
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
			<div class="forma-project-gallery forma-grid-cols-3">
				<div class="forma-project-gallery__inner">
					<div class="forma-project-gallery__item">
						<img src="" alt="">
					</div>
				</div>
			</div>
			<?php
		}
	}

endif;