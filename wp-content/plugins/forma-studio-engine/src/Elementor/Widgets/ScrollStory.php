<?php

namespace Forma\Engine\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

/**
 * A sequence told by scrolling: "The Making of a Space" on Home (horizontal, pinned) and the six stages on Process
 * (steps beside a sticky image). Markup is a plain ordered list, complete without JavaScript; the scroll behaviour
 * is added on desktop when motion is allowed, and mobile always reads it as a vertical story.
 */
final class ScrollStory extends Base {

	/** Background surfaces, matching the theme's page, raised and deep tokens. */
	public const SURFACES = array( 'page', 'raised', 'deep' );

	protected function asset(): string {
		return 'scroll-story';
	}

	public function get_name() {
		return 'forma-scroll-story';
	}

	public function get_title() {
		return esc_html__( 'Scroll Story', 'forma-studio-engine' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_keywords() {
		return array( 'story', 'horizontal', 'scroll', 'timeline', 'process', 'steps', 'forma' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_panels', array( 'label' => esc_html__( 'Story', 'forma-studio-engine' ) ) );

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'horizontal',
				'options' => array(
					'horizontal' => esc_html__( 'Horizontal (pinned)', 'forma-studio-engine' ),
					'steps'      => esc_html__( 'Steps beside a sticky image', 'forma-studio-engine' ),
				),
			)
		);

		$this->add_control(
			'surface',
			array(
				'label'   => esc_html__( 'Surface', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'raised',
				'options' => array(
					'page'   => esc_html__( 'Page (Chalk)', 'forma-studio-engine' ),
					'raised' => esc_html__( 'Raised', 'forma-studio-engine' ),
					'deep'   => esc_html__( 'Deep (Bottle green)', 'forma-studio-engine' ),
				),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Panel title tag', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control( 'image', array( 'label' => esc_html__( 'Image', 'forma-studio-engine' ), 'type' => Controls_Manager::MEDIA ) );
		$repeater->add_control( 'label', array( 'label' => esc_html__( 'Label', 'forma-studio-engine' ), 'type' => Controls_Manager::TEXT ) );
		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'forma-studio-engine' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);
		$repeater->add_control( 'text', array( 'label' => esc_html__( 'Text', 'forma-studio-engine' ), 'type' => Controls_Manager::TEXTAREA ) );
		$repeater->add_control( 'meta', array( 'label' => esc_html__( 'Meta (e.g. duration)', 'forma-studio-engine' ), 'type' => Controls_Manager::TEXT ) );

		$this->add_control(
			'panels',
			array(
				'label'       => esc_html__( 'Panels', 'forma-studio-engine' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array( 'title' => esc_html__( 'Survey', 'forma-studio-engine' ) ),
					array( 'title' => esc_html__( 'Sketch', 'forma-studio-engine' ) ),
					array( 'title' => esc_html__( 'Model', 'forma-studio-engine' ) ),
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$panels   = array_values( array_filter( (array) ( $settings['panels'] ?? array() ), static fn( $panel ) => '' !== trim( (string) ( $panel['title'] ?? '' ) ) ) );

		if ( ! $panels ) {
			return;
		}

		$layout  = 'steps' === ( $settings['layout'] ?? '' ) ? 'steps' : 'horizontal';
		$surface = in_array( $settings['surface'] ?? '', self::SURFACES, true ) ? $settings['surface'] : 'raised';
		$tag     = in_array( $settings['title_tag'] ?? '', array( 'h2', 'h3', 'h4' ), true ) ? $settings['title_tag'] : 'h3';

		printf( '<div class="forma-story forma-story--%1$s forma-story--%2$s">', esc_attr( $layout ), esc_attr( $surface ) );

		if ( 'steps' === $layout ) {
			echo '<div class="forma-story__media" aria-hidden="true">';
			foreach ( $panels as $index => $panel ) {
				echo $this->image( $panel, 'large', array( 'alt' => '', 'data-index' => $index ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
			}
			echo '</div>';
		}

		echo '<ol class="forma-story__panels">';

		foreach ( $panels as $index => $panel ) {
			echo '<li class="forma-story__panel">';

			$image = $this->image( $panel, 'horizontal' === $layout ? 'forma-960' : 'large' );

			if ( $image ) {
				echo '<figure class="forma-story__figure">' . $image . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
			}

			echo '<div class="forma-story__text">';
			printf( '<span class="forma-story__no">%02d</span>', $index + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integer.

			if ( ! empty( $panel['label'] ) ) {
				printf( '<span class="forma-story__label">%s</span>', esc_html( $panel['label'] ) );
			}

			printf( '<%1$s class="forma-story__title">%2$s</%1$s>', tag_escape( $tag ), esc_html( $panel['title'] ) );

			if ( ! empty( $panel['text'] ) ) {
				printf( '<p class="forma-story__body">%s</p>', esc_html( $panel['text'] ) );
			}

			if ( ! empty( $panel['meta'] ) ) {
				printf( '<span class="forma-story__meta">%s</span>', esc_html( $panel['meta'] ) );
			}

			echo '</div></li>';
		}

		echo '</ol><div class="forma-story__rail" aria-hidden="true"><span></span></div></div>';
	}

	private function image( array $panel, string $size, array $attributes = array() ): string {
		$id = (int) ( $panel['image']['id'] ?? 0 );

		if ( ! $id ) {
			return '';
		}

		return (string) wp_get_attachment_image(
			$id,
			$size,
			false,
			$attributes + array(
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
	}
}
