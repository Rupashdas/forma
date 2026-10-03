<?php

namespace Forma\Engine\Elementor\Widgets;

use Elementor\Controls_Manager;
use Forma\Engine\Projects\ProjectNumber;
use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * The end of every project page: the next project by completion date as one large link, wrapping from the newest
 * back to the oldest. Its image carries the next project's transition name, so it morphs into that page's hero.
 */
final class NextProject extends Base {

	protected function asset(): string {
		return 'next-project';
	}

	public function get_name() {
		return 'forma-next-project';
	}

	public function get_title() {
		return esc_html__( 'Next Project', 'forma-studio-engine' );
	}

	public function get_icon() {
		return 'eicon-post-navigation';
	}

	public function get_keywords() {
		return array( 'next', 'project', 'navigation', 'forma' );
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Next project', 'forma-studio-engine' ) ) );

		$this->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Label', 'forma-studio-engine' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Next project', 'forma-studio-engine' ),
			)
		);

		$this->add_control(
			'wrap',
			array(
				'label'       => esc_html__( 'Loop back to the first project', 'forma-studio-engine' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'description' => esc_html__( 'After the newest project, link to the oldest.', 'forma-studio-engine' ),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => esc_html__( 'Image size', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'large',
				'options' => array(
					'large' => esc_html__( 'Large (1600)', 'forma-studio-engine' ),
					'full'  => esc_html__( 'Full (2400)', 'forma-studio-engine' ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$next     = $this->next( 'yes' === ( $settings['wrap'] ?? '' ) );

		if ( ! $next ) {
			return;
		}

		$title = get_the_title( $next );
		$size  = 'full' === ( $settings['image_size'] ?? '' ) ? 'full' : 'large';
		?>
		<a class="forma-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>" data-cursor="<?php echo esc_attr__( 'Next', 'forma-studio-engine' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: project name. */ __( 'Next project: %s', 'forma-studio-engine' ), $title ) ); ?>">
			<span class="forma-next__media" style="view-transition-name: forma-project-<?php echo (int) $next; ?>">
				<?php
				echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
					$next,
					$size,
					array(
						'alt'      => '',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '100vw',
					)
				);
				?>
			</span>
			<span class="forma-next__text">
				<span class="forma-next__label"><?php echo esc_html( (string) ( $settings['label'] ?? '' ) ); ?></span>
				<span class="forma-next__no"><?php echo esc_html( 'No. ' . ProjectNumber::for_post( $next ) ); ?></span>
				<span class="forma-next__title"><?php echo esc_html( $title ); ?></span>
			</span>
		</a>
		<?php
	}

	/**
	 * The project after the current one by completion date. Outside a project (e.g. editing the template in
	 * Elementor) the newest project stands in, so the editor still shows a realistic preview.
	 */
	private function next( bool $wrap ): int {
		$ids     = ProjectNumber::ordered_ids();
		$current = (int) get_the_ID();

		if ( ! $ids ) {
			return 0;
		}

		if ( Projects::POST_TYPE !== get_post_type( $current ) ) {
			return \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ? (int) end( $ids ) : 0;
		}

		$index = array_search( $current, $ids, true );

		if ( false === $index ) {
			return 0;
		}

		if ( isset( $ids[ $index + 1 ] ) ) {
			return $ids[ $index + 1 ];
		}

		return $wrap && $ids[0] !== $current ? $ids[0] : 0;
	}
}
