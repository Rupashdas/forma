<?php

namespace Forma\Engine\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Forma\Engine\Model\Model;
use Forma\Engine\Model\Models;
use Forma\Engine\Projects\Projects;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * A study model: a block-built massing model of a project (or of the studio, a plot, the design process), drawn in
 * real time with Three.js. The model is a list of volumes (a recipe in data/models.php, a project's own list, or
 * the widget's repeater); assets/js/model-stage.js turns it into a canvas and its behaviours: assemble, drag,
 * keyboard, scroll orbit, explosion, stages, and swapping to another project.
 *
 * Complete without JavaScript or WebGL: the markup carries the project's photograph, which the runtime reveals
 * only when it cannot draw.
 */
final class StudyModel extends Base {

	private const CAMERAS = array( 'hero', 'three-quarter', 'top', 'close' );

	protected function asset(): string {
		return 'study-model';
	}

	public function get_name() {
		return 'forma-study-model';
	}

	public function get_title() {
		return esc_html__( 'Study model', 'forma-studio-engine' );
	}

	public function get_icon() {
		return 'eicon-cube';
	}

	public function get_keywords() {
		return array( 'model', '3d', 'three', 'webgl', 'massing', 'project', 'forma' );
	}

	/** The model can come from other posts, and the page needs the runtime, so it is rendered live. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls() {
		$this->source_controls();
		$this->presentation_controls();
		$this->behaviour_controls();
	}

	private function source_controls(): void {
		$this->start_controls_section( 'section_source', array( 'label' => esc_html__( 'Model', 'forma-studio-engine' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Model source', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => esc_html__( 'This project', 'forma-studio-engine' ),
					'project' => esc_html__( 'Another project', 'forma-studio-engine' ),
					'recipe'  => esc_html__( 'Studio, Lisbon block, plot or process', 'forma-studio-engine' ),
					'custom'  => esc_html__( 'Custom volumes', 'forma-studio-engine' ),
				),
			)
		);

		$projects = Models::project_options();

		$this->add_control(
			'project',
			array(
				'label'     => esc_html__( 'Project', 'forma-studio-engine' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => (string) ( array_key_first( $projects ) ?? '' ),
				'options'   => $projects,
				'condition' => array( 'source' => 'project' ),
			)
		);

		$this->add_control(
			'recipe',
			array(
				'label'     => esc_html__( 'Model', 'forma-studio-engine' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'plot',
				'options'   => $this->recipe_labels(),
				'condition' => array( 'source' => 'recipe' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'kind',
			array(
				'label'   => esc_html__( 'Shape', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'box',
				'options' => array(
					'box'      => esc_html__( 'Box', 'forma-studio-engine' ),
					'gable'    => esc_html__( 'Gabled block', 'forma-studio-engine' ),
					'cylinder' => esc_html__( 'Cylinder', 'forma-studio-engine' ),
					'slab'     => esc_html__( 'Slab', 'forma-studio-engine' ),
					'wire'     => esc_html__( 'Dashed outline', 'forma-studio-engine' ),
				),
			)
		);

		foreach ( array(
			'w' => array( esc_html__( 'Width (X)', 'forma-studio-engine' ), 1.0, 0.02, 40 ),
			'h' => array( esc_html__( 'Height', 'forma-studio-engine' ), 1.0, 0.02, 40 ),
			'd' => array( esc_html__( 'Depth (Z)', 'forma-studio-engine' ), 1.0, 0.02, 40 ),
			'x' => array( esc_html__( 'Centre X', 'forma-studio-engine' ), 0, -40, 40 ),
			'y' => array( esc_html__( 'Base height', 'forma-studio-engine' ), 0.18, -40, 40 ),
			'z' => array( esc_html__( 'Centre Z', 'forma-studio-engine' ), 0, -40, 40 ),
		) as $key => [ $label, $default, $min, $max ] ) {
			$repeater->add_control(
				$key,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::NUMBER,
					'default' => $default,
					'min'     => $min,
					'max'     => $max,
					'step'    => 0.05,
				)
			);
		}

		$repeater->add_control(
			'rot',
			array(
				'label'   => esc_html__( 'Rotation (degrees)', 'forma-studio-engine' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => -360,
				'max'     => 360,
				'step'    => 5,
			)
		);

		$repeater->add_control(
			'material',
			array(
				'label'   => esc_html__( 'Material', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'foam',
				'options' => array(
					'foam'  => esc_html__( 'Foam', 'forma-studio-engine' ),
					'shade' => esc_html__( 'Shaded foam', 'forma-studio-engine' ),
					'ink'   => esc_html__( 'Ink', 'forma-studio-engine' ),
					'glass' => esc_html__( 'Glass', 'forma-studio-engine' ),
					'wire'  => esc_html__( 'Blue wire', 'forma-studio-engine' ),
				),
			)
		);

		$repeater->add_control(
			'part',
			array(
				'label'       => esc_html__( 'Part label', 'forma-studio-engine' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Shown beside the part in the exploded view. Leave empty for no label.', 'forma-studio-engine' ),
			)
		);

		$repeater->add_control(
			'stage',
			array(
				'label'       => esc_html__( 'Stage', 'forma-studio-engine' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 6,
				'description' => esc_html__( '1 to 6 when the model grows in stages as you scroll; 0 is always shown.', 'forma-studio-engine' ),
			)
		);

		$this->add_control(
			'volumes',
			array(
				'label'       => esc_html__( 'Volumes', 'forma-studio-engine' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ kind }}} {{{ part }}}',
				'default'     => array(
					array(
						'kind'     => 'slab',
						'w'        => 6.4,
						'h'        => 0.18,
						'd'        => 4.2,
						'x'        => 0,
						'y'        => 0,
						'z'        => 0,
						'material' => 'foam',
					),
					array(
						'kind'     => 'box',
						'w'        => 3.2,
						'h'        => 1.3,
						'd'        => 2.3,
						'x'        => -1.3,
						'y'        => 0.18,
						'z'        => -0.5,
						'material' => 'foam',
						'part'     => esc_html__( 'Main volume', 'forma-studio-engine' ),
					),
					array(
						'kind'     => 'gable',
						'w'        => 1.6,
						'h'        => 1.4,
						'd'        => 2.2,
						'x'        => 1.7,
						'y'        => 0.18,
						'z'        => 0.4,
						'material' => 'ink',
						'part'     => esc_html__( 'Gabled wing', 'forma-studio-engine' ),
					),
				),
				'condition'   => array( 'source' => 'custom' ),
			)
		);

		$this->end_controls_section();
	}

	private function presentation_controls(): void {
		$this->start_controls_section( 'section_view', array( 'label' => esc_html__( 'Presentation', 'forma-studio-engine' ) ) );

		$this->add_control(
			'camera',
			array(
				'label'   => esc_html__( 'Camera', 'forma-studio-engine' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'hero',
				'options' => array(
					'hero'          => esc_html__( 'Hero', 'forma-studio-engine' ),
					'three-quarter' => esc_html__( 'Three-quarter', 'forma-studio-engine' ),
					'top'           => esc_html__( 'Top', 'forma-studio-engine' ),
					'close'         => esc_html__( 'Close', 'forma-studio-engine' ),
				),
			)
		);

		$this->add_responsive_control(
			'view_height',
			array(
				'label'      => esc_html__( 'Height', 'forma-studio-engine' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'vh', 'px', 'custom' ),
				'range'      => array(
					'vh' => array(
						'min' => 20,
						'max' => 120,
					),
					'px' => array(
						'min' => 200,
						'max' => 1400,
					),
				),
				'default'    => array(
					'unit' => 'vh',
					'size' => 70,
				),
				'selectors'  => array( '{{WRAPPER}} .forma-model' => '--forma-model-h: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'caption',
			array(
				'label'       => esc_html__( 'Caption', 'forma-studio-engine' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$this->end_controls_section();
	}

	private function behaviour_controls(): void {
		$this->start_controls_section( 'section_behaviour', array( 'label' => esc_html__( 'Behaviour', 'forma-studio-engine' ) ) );

		foreach ( array(
			'assemble'       => array( esc_html__( 'Assemble on load', 'forma-studio-engine' ), esc_html__( 'The volumes drop in one after another.', 'forma-studio-engine' ), '' ),
			'drag'           => array( esc_html__( 'Drag to rotate', 'forma-studio-engine' ), '', 'yes' ),
			'keyboard'       => array( esc_html__( 'Arrow keys rotate', 'forma-studio-engine' ), esc_html__( 'The model can be focused and turned with the left and right arrow keys.', 'forma-studio-engine' ), '' ),
			'callouts'       => array( esc_html__( 'Callouts', 'forma-studio-engine' ), esc_html__( 'Labels the named parts at rest, as pills on dotted leader lines. On screens narrower than 768px they show only in the exploded view.', 'forma-studio-engine' ), '' ),
			'scroll_orbit'   => array( esc_html__( 'Orbit on scroll', 'forma-studio-engine' ), esc_html__( 'The model turns three quarters of a circle over the scroll trigger.', 'forma-studio-engine' ), '' ),
			'scroll_explode' => array( esc_html__( 'Explode on scroll', 'forma-studio-engine' ), esc_html__( 'The parts move apart and are labelled as you scroll.', 'forma-studio-engine' ), '' ),
			'explode_toggle' => array( esc_html__( 'Exploded view button', 'forma-studio-engine' ), '', '' ),
			'stages'         => array( esc_html__( 'Grow in stages on scroll', 'forma-studio-engine' ), esc_html__( 'Volumes with a stage from 1 to 6 appear as you scroll (the Process model).', 'forma-studio-engine' ), '' ),
		) as $key => [ $label, $description, $default ] ) {
			$this->add_control(
				$key,
				array(
					'label'       => $label,
					'type'        => Controls_Manager::SWITCHER,
					'default'     => $default,
					'description' => $description,
				)
			);
		}

		$this->add_control(
			'swap',
			array(
				'label'       => esc_html__( 'Swap to another project', 'forma-studio-engine' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'none',
				'options'     => array(
					'none'   => esc_html__( 'Never', 'forma-studio-engine' ),
					'hover'  => esc_html__( 'When a marked element is hovered or focused', 'forma-studio-engine' ),
					'scroll' => esc_html__( 'To the marked element nearest the centre', 'forma-studio-engine' ),
				),
				'description' => esc_html__( 'Mark elements with "Swap the page\'s study model to this project" in their Forma Motion panel.', 'forma-studio-engine' ),
				'separator'   => 'before',
			)
		);

		$this->add_control(
			'scroll_trigger',
			array(
				'label'       => esc_html__( 'Scroll trigger', 'forma-studio-engine' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'self',
				'options'     => array(
					'self'    => esc_html__( 'This widget', 'forma-studio-engine' ),
					'section' => esc_html__( 'The section it is in', 'forma-studio-engine' ),
				),
				'description' => esc_html__( 'What the scroll behaviours follow. A section taller than the screen, with this widget pinned inside it, makes a tour.', 'forma-studio-engine' ),
				'separator'   => 'before',
			)
		);

		$this->end_controls_section();
	}

	/** @return array<string, string> */
	private function recipe_labels(): array {
		return array(
			'studio'       => esc_html__( 'The studio building', 'forma-studio-engine' ),
			'lisbon-block' => esc_html__( 'The Lisbon block', 'forma-studio-engine' ),
			'plot'         => esc_html__( 'An empty plot', 'forma-studio-engine' ),
			'process'      => esc_html__( 'The process, in six stages', 'forma-studio-engine' ),
		);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$model    = $this->model( $settings );

		if ( ! $model ) {
			if ( Editor::active() ) {
				echo '<div class="forma-model forma-model--empty">' . esc_html__( 'Choose a project, a built-in model or custom volumes for this study model.', 'forma-studio-engine' ) . '</div>';
			}

			return;
		}

		$on        = static fn( string $key ): bool => 'yes' === ( $settings[ $key ] ?? '' );
		$swap      = in_array( $settings['swap'] ?? '', array( 'hover', 'scroll' ), true ) ? $settings['swap'] : 'none';
		$trigger   = 'section' === ( $settings['scroll_trigger'] ?? '' ) ? 'section' : 'self';
		$camera    = in_array( $settings['camera'] ?? '', self::CAMERAS, true ) ? $settings['camera'] : 'hero';
		$caption   = trim( (string) ( $settings['caption'] ?? '' ) );
		$scrolling = $on( 'scroll_orbit' ) || $on( 'scroll_explode' ) || $on( 'stages' ) || 'scroll' === $swap;

		$data = array(
			'id'            => $model['id'],
			'title'         => $model['title'],
			'volumes'       => $model['volumes'],
			'camera'        => $camera,
			'behaviours'    => array(
				'assemble'      => $on( 'assemble' ),
				'drag'          => $on( 'drag' ),
				'keyboard'      => $on( 'keyboard' ),
				'callouts'      => $on( 'callouts' ),
				'scrollOrbit'   => $on( 'scroll_orbit' ),
				'scrollExplode' => $on( 'scroll_explode' ),
				'explodeToggle' => $on( 'explode_toggle' ),
				'stages'        => $on( 'stages' ),
				'swap'          => $swap,
			),
			'scrollTrigger' => $trigger,
		);

		if ( ! empty( $model['distance'] ) ) {
			$data['distance'] = $model['distance'];
		}

		Model::enqueue( $scrolling );

		if ( 'none' !== $swap ) {
			Model::need_projects_json();
		}

		$classes = array( 'forma-model' );

		if ( ! $on( 'drag' ) ) {
			$classes[] = 'forma-model--fixed';
		}

		printf(
			'<figure class="%s" data-forma-model="%s"%s>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( (string) wp_json_encode( $data ) ),
			$on( 'drag' ) ? ' data-cursor="' . esc_attr__( 'Drag', 'forma-studio-engine' ) . '"' : ''
		);

		if ( ! empty( $model['photo'] ) ) {
			echo $model['photo']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
		}

		printf(
			'<span class="screen-reader-text">%s</span>',
			esc_html( sprintf( /* translators: %s: project or model name */ __( 'Study model of %s', 'forma-studio-engine' ), $model['title'] ) )
		);

		if ( $on( 'explode_toggle' ) ) {
			printf(
				'<button class="forma-model__explode" type="button" aria-pressed="false">%s</button>',
				esc_html__( 'Exploded view', 'forma-studio-engine' )
			);
		}

		if ( '' !== $caption ) {
			printf( '<figcaption class="forma-model__caption">%s</figcaption>', esc_html( $caption ) );
		}

		echo '</figure>';
	}

	/**
	 * The model this instance shows: id, title, volumes, an optional camera distance factor, and the fallback photo.
	 *
	 * @return array{id: string, title: string, volumes: list<array>, distance?: float, photo?: string}|null
	 */
	private function model( array $settings ): ?array {
		$source = (string) ( $settings['source'] ?? 'current' );

		if ( 'custom' === $source ) {
			$volumes = Models::normalise( (array) ( $settings['volumes'] ?? array() ) );

			return $volumes ? array(
				'id'      => 'custom',
				'title'   => __( 'the building', 'forma-studio-engine' ),
				'volumes' => $volumes,
			) : null;
		}

		if ( 'recipe' === $source ) {
			$key    = (string) ( $settings['recipe'] ?? 'plot' );
			$recipe = in_array( $key, Models::RECIPES, true ) ? Models::recipe( $key ) : null;

			if ( ! $recipe ) {
				return null;
			}

			$labels = $this->recipe_labels();

			return array(
				'id'       => $key,
				'title'    => mb_strtolower( $labels[ $key ] ?? $key ),
				'volumes'  => $recipe['volumes'],
				'distance' => $recipe['camera']['distance'] ?? null,
			);
		}

		$post_id = 'project' === $source ? (int) ( $settings['project'] ?? 0 ) : (int) get_the_ID();
		$project = $post_id && Projects::POST_TYPE === get_post_type( $post_id ) ? Models::for_project( $post_id ) : null;

		if ( ! $project ) {
			return null;
		}

		return array(
			'id'       => $project['slug'],
			'title'    => $project['title'],
			'volumes'  => $project['volumes'],
			'distance' => $project['camera']['distance'] ?? null,
			'photo'    => (string) get_the_post_thumbnail(
				$post_id,
				'large',
				array(
					'class'    => 'forma-model__fallback',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			),
		);
	}
}
