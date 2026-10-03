<?php

namespace Forma\Engine\Motion;

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Forma\Engine\Contracts\Module;
use Forma\Engine\Support\Assets;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * Forma Motion: a panel on the Advanced tab of every native Elementor widget and container.
 *
 * Editors pick an entrance, a scroll effect or a cursor label on any element; this module turns those choices into
 * data-fm-* attributes, and loads GSAP plus the runtime only on pages where at least one element uses them.
 */
final class Motion implements Module {

	private const ENTRANCES = array( 'fade-up', 'lines', 'words', 'chars', 'clip-up', 'clip-left', 'scale-in' );
	private const SCROLLS   = array( 'parallax', 'expand' );

	public static function id(): string {
		return 'motion';
	}

	public function is_available(): bool {
		return did_action( 'elementor/loaded' ) > 0;
	}

	public function register(): void {
		// "common" covers every widget: the optimised-markup stack (common-optimized) fires the common hooks too,
		// so hooking both would declare the panel twice.
		foreach ( array( 'common/_section_style', 'container/section_layout' ) as $section ) {
			add_action( "elementor/element/{$section}/after_section_end", array( $this, 'controls' ) );
		}

		add_action( 'elementor/frontend/widget/before_render', array( $this, 'render' ) );
		add_action( 'elementor/frontend/container/before_render', array( $this, 'render' ) );
		add_filter( 'elementor/element/is_dynamic_content', array( $this, 'is_dynamic' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'head' ), 2 );
	}

	public function controls( Element_Base $element ): void {
		$element->start_controls_section(
			'forma_motion',
			array(
				'label' => esc_html__( 'Forma Motion', 'forma-studio-engine' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			)
		);

		$element->add_control(
			'fm_entrance',
			array(
				'label'   => esc_html__( 'Entrance', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''          => esc_html__( 'None', 'forma-studio-engine' ),
					'fade-up'   => esc_html__( 'Fade up', 'forma-studio-engine' ),
					'lines'     => esc_html__( 'Reveal lines', 'forma-studio-engine' ),
					'words'     => esc_html__( 'Reveal words', 'forma-studio-engine' ),
					'chars'     => esc_html__( 'Reveal characters', 'forma-studio-engine' ),
					'clip-up'   => esc_html__( 'Wipe up', 'forma-studio-engine' ),
					'clip-left' => esc_html__( 'Wipe from left', 'forma-studio-engine' ),
					'scale-in'  => esc_html__( 'Settle (image)', 'forma-studio-engine' ),
				),
			)
		);

		$element->add_control(
			'fm_scroll',
			array(
				'label'   => esc_html__( 'On scroll', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''         => esc_html__( 'Nothing', 'forma-studio-engine' ),
					'parallax' => esc_html__( 'Parallax', 'forma-studio-engine' ),
					'expand'   => esc_html__( 'Expand to full bleed', 'forma-studio-engine' ),
				),
			)
		);

		$element->add_control(
			'fm_speed',
			array(
				'label'     => esc_html__( 'Parallax depth', 'forma-studio-engine' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => -30,
				'max'       => 30,
				'step'      => 1,
				'default'   => 12,
				'condition' => array( 'fm_scroll' => 'parallax' ),
			)
		);

		$element->add_control(
			'fm_delay',
			array(
				'label'     => esc_html__( 'Delay (s)', 'forma-studio-engine' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 2,
				'step'      => 0.05,
				'condition' => array( 'fm_entrance!' => '' ),
			)
		);

		$element->add_control(
			'fm_stagger',
			array(
				'label'     => esc_html__( 'Stagger (s)', 'forma-studio-engine' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 0,
				'max'       => 0.3,
				'step'      => 0.01,
				'condition' => array( 'fm_entrance' => array( 'lines', 'words', 'chars' ) ),
			)
		);

		$element->add_control(
			'fm_cursor',
			array(
				'label'       => esc_html__( 'Cursor label', 'forma-studio-engine' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'View', 'forma-studio-engine' ),
				'description' => esc_html__( 'Shown in the cursor on desktop when hovering this element.', 'forma-studio-engine' ),
				'separator'   => 'before',
			)
		);

		$element->add_control(
			'fm_shared',
			array(
				'label'       => esc_html__( 'Morph project image between pages', 'forma-studio-engine' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => esc_html__( 'Use on the project image in cards and on the project hero, so it morphs during navigation.', 'forma-studio-engine' ),
			)
		);

		$element->end_controls_section();
	}

	public function render( Element_Base $element ): void {
		$settings = $element->get_settings_for_display();
		$entrance = in_array( $settings['fm_entrance'] ?? '', self::ENTRANCES, true ) ? $settings['fm_entrance'] : '';
		$scroll   = in_array( $settings['fm_scroll'] ?? '', self::SCROLLS, true ) ? $settings['fm_scroll'] : '';
		$attrs    = array();

		if ( $entrance ) {
			$attrs['data-fm-entrance'] = $entrance;
			$attrs['data-fm-delay']    = $this->number( $settings['fm_delay'] ?? '', 0, 2 );
			$attrs['data-fm-stagger']  = $this->number( $settings['fm_stagger'] ?? '', 0, 0.3 );
		}

		if ( $scroll ) {
			$attrs['data-fm-scroll'] = $scroll;
			$attrs['data-fm-speed']  = 'parallax' === $scroll ? $this->number( $settings['fm_speed'] ?? '', -30, 30 ) : '';
		}

		$cursor = mb_substr( sanitize_text_field( (string) ( $settings['fm_cursor'] ?? '' ) ), 0, 24 );

		if ( '' !== $cursor ) {
			$attrs['data-cursor'] = $cursor;
		}

		if ( 'yes' === ( $settings['fm_shared'] ?? '' ) && get_the_ID() ) {
			$attrs['style'] = 'view-transition-name: forma-project-' . (int) get_the_ID();
		}

		foreach ( array_filter( $attrs, static fn( $value ) => '' !== $value ) as $name => $value ) {
			$element->add_render_attribute( '_wrapper', $name, $value );
		}

		if ( $entrance || $scroll ) {
			Assets::enqueue( 'motion' );
		}
	}

	/**
	 * Element caching would serve animated elements without running their render hooks, and so without loading
	 * the motion runtime. Animated elements are therefore rendered live; everything else stays cacheable.
	 *
	 * @param bool  $is_dynamic Whether Elementor already treats the element as dynamic.
	 * @param array $raw_data   The element's saved data.
	 */
	public function is_dynamic( $is_dynamic, $raw_data ): bool {
		$settings = $raw_data['settings'] ?? array();

		return $is_dynamic || ! empty( $settings['fm_entrance'] ) || ! empty( $settings['fm_scroll'] );
	}

	/**
	 * Animated elements start hidden only when JS runs and motion is allowed, and a CSS failsafe reveals them
	 * after three seconds if the runtime never arrives. The runtime adds .fm-armed once it has taken over.
	 */
	public function head(): void {
		if ( is_admin() || is_feed() || Editor::active() ) {
			return;
		}

		echo "<script>document.documentElement.classList.add('fm-js')</script>\n";
		echo '<style id="forma-motion-guard">@media (prefers-reduced-motion: no-preference){html.fm-js:not(.fm-armed) [data-fm-entrance]{opacity:0;animation:fm-failsafe 0s 3s forwards}}@keyframes fm-failsafe{to{opacity:1}}</style>' . "\n";
	}


	private function number( mixed $value, float $min, float $max ): string {
		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			return '';
		}

		return (string) round( min( $max, max( $min, (float) $value ) ), 2 );
	}
}
