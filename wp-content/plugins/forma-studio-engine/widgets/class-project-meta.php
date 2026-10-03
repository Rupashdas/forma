<?php
/**
 * Project Meta Widget — structured project metadata.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) :

	final class Project_Meta extends Forma_Widget_Base {

		public function get_name() {
			return 'forma_project_meta';
		}

		public function get_title() {
			return esc_html__( 'FORMA Project Meta', 'forma-studio-engine' );
		}

		public function get_icon() {
			return 'eicon-info-circle';
		}

		public function get_keywords() {
			return array( 'forma', 'project', 'meta', 'details' );
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

			$this->end_controls_section();
		}

		/**
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();
			$post_id  = ! empty( $settings['post_id'] ) ? absint( $settings['post_id'] ) : get_the_ID();

			$meta = $this->build_meta( $post_id );

			if ( empty( $meta ) ) {
				echo '<p>' . esc_html__( 'No metadata available.', 'forma-studio-engine' ) . '</p>';
				return;
			}

			?>

			<dl class="forma-project-meta">
				<?php foreach ( $meta as $item ) : ?>
					<?php if ( empty( $item['value'] ) ) {
						continue;
					} ?>
					<div class="forma-project-meta__row">
						<dt><?php echo esc_html( $item['label'] ); ?></dt>
						<dd><?php echo esc_html( $item['value'] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>

			<?php
		}

		/**
		 * @param int $post_id
		 * @return array
		 */
		private function build_meta( $post_id ) {
			$meta = array();

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

			foreach ( $items as $key => $label ) {
				$value = forma_get_field( $key, $post_id );
				if ( $value ) {
					$meta[] = array(
						'label' => $label,
						'value' => $value,
					);
				}
			}

			return $meta;
		}

		/**
		 * @return void
		 */
		protected function content_template() {
			?>
			<dl class="forma-project-meta">
				<div class="forma-project-meta__row">
					<dt></dt>
					<dd></dd>
				</div>
			</dl>
			<?php
		}
	}

endif;