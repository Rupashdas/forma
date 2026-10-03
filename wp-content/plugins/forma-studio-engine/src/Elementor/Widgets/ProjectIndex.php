<?php

namespace Forma\Engine\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Forma\Engine\Projects\ProjectNumber;
use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * The studio's work as an editorial list: number, name, type, place and year, one row per project.
 *
 * On desktop the hovered project's image follows the pointer; keyboard focus pins it beside the row; touch screens
 * get a small thumbnail in each row instead. Every row is a plain link, so the list works without JavaScript.
 */
final class ProjectIndex extends Base {

	protected function asset(): string {
		return 'project-index';
	}

	public function get_name() {
		return 'forma-project-index';
	}

	public function get_title() {
		return esc_html__( 'Project Index', 'forma-studio-engine' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	public function get_keywords() {
		return array( 'projects', 'index', 'list', 'portfolio', 'forma' );
	}

	/** The list reflects other posts, so it is rendered live rather than served from Elementor's element cache. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_query', array( 'label' => esc_html__( 'Projects', 'forma-studio-engine' ) ) );

		$types = array( '' => esc_html__( 'All types', 'forma-studio-engine' ) );

		foreach ( get_terms( array( 'taxonomy' => Projects::TYPE_TAX, 'hide_empty' => false ) ) ?: array() as $term ) {
			$types[ $term->slug ] = $term->name;
		}

		$this->add_control(
			'project_type',
			array(
				'label'   => esc_html__( 'Type', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $types,
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'newest',
				'options' => array(
					'newest' => esc_html__( 'Newest first', 'forma-studio-engine' ),
					'oldest' => esc_html__( 'Oldest first', 'forma-studio-engine' ),
				),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'       => esc_html__( 'Number of projects', 'forma-studio-engine' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => 0,
				'description' => esc_html__( '0 shows them all.', 'forma-studio-engine' ),
			)
		);

		foreach ( array(
			'show_type'     => esc_html__( 'Show type', 'forma-studio-engine' ),
			'show_location' => esc_html__( 'Show location', 'forma-studio-engine' ),
			'show_year'     => esc_html__( 'Show year', 'forma-studio-engine' ),
		) as $control => $label ) {
			$this->add_control(
				$control,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SWITCHER,
					'default' => 'yes',
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Titles', 'forma-studio-engine' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .forma-index__title',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$query    = array(
			'post_type'      => Projects::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) ( $settings['limit'] ?? 0 ) > 0 ? (int) $settings['limit'] : -1,
			'orderby'        => 'date',
			'order'          => 'oldest' === ( $settings['order'] ?? '' ) ? 'ASC' : 'DESC',
			'no_found_rows'  => true,
		);

		if ( ! empty( $settings['project_type'] ) ) {
			$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery -- one small taxonomy filter.
				array(
					'taxonomy' => Projects::TYPE_TAX,
					'field'    => 'slug',
					'terms'    => sanitize_title( $settings['project_type'] ),
				),
			);
		}

		$projects = get_posts( $query );

		if ( ! $projects ) {
			return;
		}

		echo '<div class="forma-index"><ol class="forma-index__list">';

		foreach ( $projects as $project ) {
			$this->row( $project, $settings );
		}

		echo '</ol><div class="forma-index__preview" aria-hidden="true"><img alt="" decoding="async"></div></div>';
	}

	private function row( \WP_Post $project, array $settings ): void {
		$term  = static function ( string $taxonomy ) use ( $project ): string {
			$terms = get_the_terms( $project, $taxonomy );
			return $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
		};
		$metas = array_filter(
			array(
				'type'     => 'yes' === ( $settings['show_type'] ?? '' ) ? $term( Projects::TYPE_TAX ) : '',
				'location' => 'yes' === ( $settings['show_location'] ?? '' ) ? $term( Projects::LOCATION_TAX ) : '',
				'year'     => 'yes' === ( $settings['show_year'] ?? '' ) ? get_the_date( 'Y', $project ) : '',
			)
		);

		printf(
			'<li class="forma-index__row"><a class="forma-index__link" href="%s" data-cursor="%s"><span class="forma-index__no">%s</span><span class="forma-index__title">%s</span><span class="forma-index__metas">',
			esc_url( get_permalink( $project ) ),
			esc_attr__( 'View', 'forma-studio-engine' ),
			esc_html( ProjectNumber::for_post( $project->ID ) ),
			esc_html( get_the_title( $project ) )
		);

		foreach ( $metas as $key => $value ) {
			printf( '<span class="forma-index__meta forma-index__meta--%s">%s</span>', esc_attr( $key ), esc_html( $value ) );
		}

		echo '</span>';
		echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
			$project,
			'forma-960',
			array(
				'class'    => 'forma-index__thumb',
				'alt'      => '',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => '(max-width: 767px) 96px, 420px',
			)
		);
		echo '</a></li>';
	}
}
