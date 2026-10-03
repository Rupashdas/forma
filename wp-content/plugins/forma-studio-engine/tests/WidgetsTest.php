<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

final class WidgetsTest extends TestCase {

	public function test_widgets_are_in_the_forma_panel(): void {
		foreach ( array( 'forma-marquee', 'forma-before-after' ) as $name ) {
			$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $name );
			$this->assert_true( null !== $widget, "{$name} registered" );
			$this->assert_same( array( 'forma' ), $widget->get_categories(), "{$name} category" );
		}
	}

	public function test_marquee_duplicates_its_list_for_a_seamless_loop(): void {
		$html = $this->render(
			'forma-marquee',
			array(
				'items'     => array(
					array( 'text' => 'Architecture' ),
					array( 'text' => 'Interiors <script>' ),
					array( 'text' => 'Objects' ),
				),
				'separator' => '—',
				'speed'     => 40,
			)
		);

		$this->assert_same( 2, substr_count( $html, '<ul class="forma-marquee__list"' ), 'two lists' );
		$this->assert_same( 1, substr_count( $html, 'aria-hidden="true"><li' ), 'second list hidden from assistive tech' );
		$this->assert_true( str_contains( $html, '--forma-marquee-duration:40s' ), 'speed as a CSS variable' );
		$this->assert_true( str_contains( $html, 'Interiors &lt;script&gt;' ), 'item text escaped' );
		$this->assert_true( wp_style_is( 'forma-marquee', 'enqueued' ) || in_array( 'forma-marquee', \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'forma-marquee' )->get_style_depends(), true ), 'style dependency declared' );
	}

	public function test_before_after_is_an_accessible_range_over_two_images(): void {
		$before = Images::attachment_id( 'casa-nera-01.jpg' );
		$after  = Images::attachment_id( 'casa-nera-02.jpg' );

		$html = $this->render(
			'forma-before-after',
			array(
				'before_image' => array( 'id' => $before ),
				'after_image'  => array( 'id' => $after ),
				'before_label' => 'Drawing',
				'after_label'  => 'Built',
				'start'        => array(
					'unit' => '%',
					'size' => 40,
				),
			)
		);

		$this->assert_true( str_contains( $html, '--forma-ba:40%' ), 'start position as a CSS variable' );
		$this->assert_true( str_contains( $html, 'type="range"' ), 'native range input' );
		$this->assert_true( str_contains( $html, 'value="40"' ), 'range starts at 40' );
		$this->assert_true( str_contains( $html, 'aria-valuetext="40% Built"' ), 'spoken value' );
		$this->assert_same( 2, substr_count( $html, '<img' ), 'both images' );
		$this->assert_true( str_contains( $html, 'alt="' . esc_attr( get_post_meta( $before, '_wp_attachment_image_alt', true ) ) . '"' ), 'real alt text' );
		$this->assert_true( str_contains( $html, '>Drawing<' ) && str_contains( $html, '>Built<' ), 'labels' );
	}

	public function test_before_after_needs_both_images(): void {
		$html = $this->render( 'forma-before-after', array( 'before_image' => array( 'id' => Images::attachment_id( 'casa-nera-01.jpg' ) ) ) );

		$this->assert_true( ! str_contains( $html, 'type="range"' ), 'nothing interactive without the second image' );
	}

	public function test_project_index_lists_published_projects_newest_first(): void {
		$html = $this->render( 'forma-project-index', array() );

		$this->assert_same( 11, substr_count( $html, '<li class="forma-index__row"' ), 'one row per project' );
		$this->assert_true( strpos( $html, 'Northline Residence' ) < strpos( $html, 'Forma Pavilion' ), 'newest first' );
		$this->assert_true( str_contains( $html, 'href="' . esc_url( get_permalink( get_page_by_path( 'casa-nera', OBJECT, 'forma_project' ) ) ) . '"' ), 'rows link to projects' );
		$this->assert_true( str_contains( $html, '<span class="forma-index__no">11</span>' ), 'project numbers' );
		$this->assert_true( str_contains( $html, '>Tromsø, Norway<' ), 'location column' );
		$this->assert_true( str_contains( $html, 'data-cursor="View"' ), 'cursor label' );
		$this->assert_true( str_contains( $html, 'class="forma-index__preview" aria-hidden="true"' ), 'decorative preview is hidden from assistive tech' );
	}

	public function test_project_index_can_show_one_type_oldest_first(): void {
		$html = $this->render(
			'forma-project-index',
			array(
				'project_type' => 'interiors',
				'order'        => 'oldest',
			)
		);

		$this->assert_same( 2, substr_count( $html, '<li class="forma-index__row"' ), 'two interiors projects' );
		$this->assert_true( strpos( $html, 'Atelier 27' ) < strpos( $html, 'House of Light' ), 'oldest first' );
	}

	public function test_next_project_follows_date_order_and_wraps(): void {
		$next = $this->render_on( 'casa-nera', 'forma-next-project', array() );

		$this->assert_true( str_contains( $next, 'aria-label="Next project: The Quiet Hotel"' ), 'Casa Nera → The Quiet Hotel' );
		$this->assert_true( str_contains( $next, 'href="' . esc_url( get_permalink( get_page_by_path( 'the-quiet-hotel', OBJECT, 'forma_project' ) ) ) . '"' ), 'links to it' );
		$this->assert_true( str_contains( $next, 'data-cursor="Next"' ), 'cursor label' );
		$this->assert_true( str_contains( $next, 'view-transition-name: forma-project-' . get_page_by_path( 'the-quiet-hotel', OBJECT, 'forma_project' )->ID ), 'image morphs into the next hero' );

		$wrap = $this->render_on( 'northline-residence', 'forma-next-project', array() );
		$this->assert_true( str_contains( $wrap, 'aria-label="Next project: Forma Pavilion"' ), 'newest wraps to the oldest' );

		$end = $this->render_on( 'northline-residence', 'forma-next-project', array( 'wrap' => '' ) );
		$this->assert_true( ! str_contains( $end, '<a ' ), 'no link at the end when wrapping is off' );
	}

	public function test_scroll_story_renders_an_ordered_list_of_panels(): void {
		$panels = array();

		foreach ( array( 'home-02.jpg' => 'Survey', 'home-03.jpg' => 'Sketch', 'home-04.jpg' => 'Model' ) as $file => $title ) {
			$panels[] = array(
				'image' => array( 'id' => Images::attachment_id( $file ) ),
				'label' => 'Stage',
				'title' => $title,
				'text'  => "{$title} text",
				'meta'  => '2–4 weeks',
			);
		}

		$html = $this->render(
			'forma-scroll-story',
			array(
				'layout'  => 'horizontal',
				'surface' => 'raised',
				'panels'  => $panels,
			)
		);

		$this->assert_true( str_contains( $html, 'forma-story--horizontal' ), 'layout class' );
		$this->assert_true( str_contains( $html, 'forma-story--raised' ), 'surface class' );
		$this->assert_same( 1, substr_count( $html, '<ol class="forma-story__panels"' ), 'one ordered list' );
		$this->assert_same( 3, substr_count( $html, '<li class="forma-story__panel"' ), 'three panels' );
		$this->assert_true( str_contains( $html, '<span class="forma-story__no">01</span>' ) && str_contains( $html, '<span class="forma-story__no">03</span>' ), 'panels numbered automatically' );
		$this->assert_true( str_contains( $html, 'alt="' . esc_attr( get_post_meta( Images::attachment_id( 'home-02.jpg' ), '_wp_attachment_image_alt', true ) ) . '"' ), 'images keep their alt text' );
		$this->assert_true( str_contains( $html, 'class="forma-story__rail" aria-hidden="true"' ), 'decorative progress rail' );
	}

	public function test_scroll_story_steps_layout(): void {
		$html = $this->render(
			'forma-scroll-story',
			array(
				'layout' => 'steps',
				'panels' => array(
					array(
						'title' => 'Discovery',
						'text'  => 'We start on site.',
					),
				),
			)
		);

		$this->assert_true( str_contains( $html, 'forma-story--steps' ), 'steps layout class' );
		$this->assert_true( str_contains( $html, '>Discovery<' ), 'step title' );
	}

	public function test_scroll_story_surfaces_are_page_raised_and_deep(): void {
		$this->assert_same( array( 'page', 'raised', 'deep' ), \Forma\Engine\Elementor\Widgets\ScrollStory::SURFACES );

		foreach ( array( 'page', 'deep' ) as $surface ) {
			$html = $this->render(
				'forma-scroll-story',
				array(
					'surface' => $surface,
					'panels'  => array( array( 'title' => 'Survey' ) ),
				)
			);
			$this->assert_true( str_contains( $html, "forma-story--{$surface}" ), "{$surface} surface class" );
		}

		$html = $this->render(
			'forma-scroll-story',
			array(
				'surface' => 'basalt',
				'panels'  => array( array( 'title' => 'Survey' ) ),
			)
		);
		$this->assert_true( str_contains( $html, 'forma-story--raised' ), 'unknown surface falls back to raised' );
	}

	private function render_on( string $slug, string $widget, array $settings ): string {
		$GLOBALS['post'] = get_page_by_path( $slug, OBJECT, 'forma_project' );
		setup_postdata( $GLOBALS['post'] );
		$html = $this->render( $widget, $settings );
		wp_reset_postdata();

		return $html;
	}

	private function render( string $widget, array $settings ): string {
		$element = \Elementor\Plugin::$instance->elements_manager->create_element_instance(
			array(
				'id'         => 'fwtest1',
				'elType'     => 'widget',
				'widgetType' => $widget,
				'settings'   => $settings,
			)
		);

		$this->assert_true( null !== $element, "{$widget} can be instantiated" );

		ob_start();
		$element->print_element();
		return (string) ob_get_clean();
	}
}
