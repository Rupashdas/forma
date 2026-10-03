<?php

namespace Forma\Engine\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

/**
 * An endless line of large type. Pure CSS: the list is printed twice and the track slides by half its width,
 * so the loop never jumps. Pauses on hover and focus, and stands still for reduced motion.
 */
final class Marquee extends Base {

	/** Each printed list repeats the items until it has at least this many, so it outruns wide screens. */
	private const MIN_ITEMS = 6;

	protected function asset(): string {
		return 'marquee';
	}

	public function get_name() {
		return 'forma-marquee';
	}

	public function get_title() {
		return esc_html__( 'Marquee', 'forma-studio-engine' );
	}

	public function get_icon() {
		return 'eicon-animation-text';
	}

	public function get_keywords() {
		return array( 'marquee', 'ticker', 'scroll', 'text', 'forma' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Items', 'forma-studio-engine' ) ) );

		$repeater = new Repeater();
		$repeater->add_control(
			'text',
			array(
				'label'       => esc_html__( 'Text', 'forma-studio-engine' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Items', 'forma-studio-engine' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => array(
					array( 'text' => 'Architecture' ),
					array( 'text' => 'Interiors' ),
					array( 'text' => 'Objects' ),
				),
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => esc_html__( 'Separator', 'forma-studio-engine' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '—',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'       => esc_html__( 'Seconds per loop', 'forma-studio-engine' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 5,
				'max'         => 200,
				'default'     => 30,
				'description' => esc_html__( 'Higher is slower.', 'forma-studio-engine' ),
			)
		);

		$this->add_control(
			'direction',
			array(
				'label'   => esc_html__( 'Direction', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
					'left'  => esc_html__( 'Right to left', 'forma-studio-engine' ),
					'right' => esc_html__( 'Left to right', 'forma-studio-engine' ),
				),
			)
		);

		$this->add_control(
			'pause',
			array(
				'label'   => esc_html__( 'Pause on hover', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Text', 'forma-studio-engine' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} .forma-marquee__item, {{WRAPPER}} .forma-marquee__sep',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Text colour', 'forma-studio-engine' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .forma-marquee__item' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'separator_color',
			array(
				'label'     => esc_html__( 'Separator colour', 'forma-studio-engine' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .forma-marquee__sep' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = array_values( array_filter( array_map( static fn( $item ) => trim( (string) ( $item['text'] ?? '' ) ), (array) ( $settings['items'] ?? array() ) ) ) );

		if ( ! $items ) {
			return;
		}

		$repeat   = (int) ceil( self::MIN_ITEMS / count( $items ) );
		$sequence = array_merge( ...array_fill( 0, $repeat, $items ) );
		$classes  = array( 'forma-marquee' );

		if ( 'right' === ( $settings['direction'] ?? '' ) ) {
			$classes[] = 'forma-marquee--right';
		}

		if ( 'yes' === ( $settings['pause'] ?? '' ) ) {
			$classes[] = 'forma-marquee--pause';
		}

		printf(
			'<div class="%s" style="--forma-marquee-duration:%ds"><div class="forma-marquee__track">%s%s</div></div>',
			esc_attr( implode( ' ', $classes ) ),
			(int) max( 5, (float) ( $settings['speed'] ?? 30 ) ),
			$this->list( $sequence, (string) ( $settings['separator'] ?? '' ), false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in list().
			$this->list( $sequence, (string) ( $settings['separator'] ?? '' ), true ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in list().
		);
	}

	private function list( array $items, string $separator, bool $copy ): string {
		$html = '<ul class="forma-marquee__list"' . ( $copy ? ' aria-hidden="true"' : '' ) . '>';

		foreach ( $items as $item ) {
			$html .= '<li class="forma-marquee__item">' . esc_html( $item ) . '</li>';

			if ( '' !== $separator ) {
				$html .= '<li class="forma-marquee__sep" aria-hidden="true">' . esc_html( $separator ) . '</li>';
			}
		}

		return $html . '</ul>';
	}
}
