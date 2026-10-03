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
