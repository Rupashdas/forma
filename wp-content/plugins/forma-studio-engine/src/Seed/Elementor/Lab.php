<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Drawings;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * A private "Widget lab" page that shows every Forma widget and Motion effect on real content, for QA in the browser.
 * Not part of `wp forma all`, never in a menu, never indexed.
 */
final class Lab {

	/** Its own marker, so the lab never counts as seeded site content. */
	public const META_KEY = '_forma_lab';

	private const INK   = '#161917';
	private const MUTED = '#575C57';

	public function __construct( private \Closure $log ) {}

	public function build(): int {
		$id = $this->page();

		Builder::reset( 'lab' );

		if ( Builder::save( $id, array_merge( $this->motion(), $this->widgets() ) ) ) {
			update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
			( $this->log )( 'Widget lab: ' . get_permalink( $id ) );
		} else {
			( $this->log )( 'Widget lab: ' . Builder::SKIPPED );
		}

		return $id;
	}

	/**
	 * Sections for the Forma widgets, added as each widget is built.
	 */
	private function widgets(): array {
		return array(
			$this->section(
				'Marquee',
				Builder::widget(
					'forma-marquee',
					array(
						'items' => array(
							array( 'text' => 'Architecture' ),
							array( 'text' => 'Interiors' ),
							array( 'text' => 'Objects' ),
						),
						'speed' => 32,
					)
				),
				true
			),
			$this->section(
				'Drawing / Built',
				Builder::widget(
					'forma-before-after',
					array(
						'before_image' => Builder::image( Drawings::attachment_id( 'casa-nera' ) ),
						'after_image'  => Builder::image( Images::attachment_id( 'casa-nera-01.jpg' ) ),
						'start'        => Builder::size( 45, '%' ),
					)
				)
			),
			$this->section( 'Project Index', Builder::widget( 'forma-project-index', array() ) ),
			$this->section(
				'Scroll Story — horizontal',
				Builder::widget(
					'forma-scroll-story',
					array(
						'layout'  => 'horizontal',
						'surface' => 'raised',
						'panels'  => $this->story(
							array(
								'home-02.jpg' => array( 'Survey', 'We walk the site with a level and a notebook before anyone draws a line.' ),
								'home-03.jpg' => array( 'Sketch', 'Ideas start by hand, fast and loose, at the scale of a fingertip.' ),
								'home-04.jpg' => array( 'Model', 'Card and foam models test light, mass and the way in.' ),
								'home-05.jpg' => array( 'Drawing', 'Plans, sections and details: every decision written down.' ),
								'home-06.jpg' => array( 'Site', 'We are on site every week, from foundations to the last coat of lime.' ),
								'home-07.jpg' => array( 'Light', 'The moment we work towards: the first morning the sun comes in.' ),
							)
						),
					)
				),
				true
			),
			$this->section(
				'Scroll Story — steps',
				Builder::widget(
					'forma-scroll-story',
					array(
						'layout'  => 'steps',
						'surface' => 'deep',
						'panels'  => $this->story(
							array(
								'casa-nera-01.jpg'       => array( 'Discovery', 'Two site visits, a brief written together, and a measured survey.' ),
								'monolith-house-01.jpg'  => array( 'Concept', 'Three directions as models and sketches; we choose one together.' ),
								'the-quiet-hotel-01.jpg' => array( 'Development', 'Plans, materials and costs refined until the design holds.' ),
							)
						),
					)
				)
			),
		);
	}

	/**
	 * @param array<string, array{0: string, 1: string}> $panels Image file => [title, text].
	 */
	private function story( array $panels ): array {
		$out = array();

		foreach ( $panels as $file => [ $title, $text ] ) {
			$out[] = array(
				'_id'   => Builder::id(),
				'image' => Builder::image( Images::attachment_id( $file ) ),
				'label' => 'Stage',
				'title' => $title,
				'text'  => $text,
				'meta'  => '2–4 weeks',
			);
		}

		return $out;
	}

	/**
	 * A lab section: a label above one widget, boxed or full width.
	 */
	private function section( string $name, array $widget, bool $full = false ): array {
		return Builder::container(
			array(
				'content_width'  => $full ? 'full' : 'boxed',
				'flex_direction' => 'column',
				'flex_gap'       => array(
					'column' => '24',
					'row'    => '24',
					'unit'   => 'px',
				),
				'padding'        => Builder::box( 96, $full ? 0 : 48, 96, $full ? 0 : 48 ),
				'border_border'  => 'solid',
				'border_width'   => Builder::box( 1, 0, 0, 0 ),
				'border_color'   => 'rgba(22,25,23,.14)',
			),
			array( $this->label( 'Widget — ' . $name ), $widget )
		);
	}

	private function motion(): array {
		return array(
			Builder::container(
				array(
					'content_width'   => 'full',
					'flex_direction'  => 'column',
					'flex_justify_content' => 'flex-end',
					'min_height'      => Builder::size( 92, 'vh' ),
					'padding'         => Builder::box( 48, 48, 48, 48 ),
				),
				array(
					$this->label( 'Lab — Forma Motion', 'fade-up' ),
					$this->display( 'FORMA', 'chars', 18, 'vw' ),
					$this->display( 'Architecture, interiors and objects', 'lines', 64, 'px', 0.2 ),
				)
			),
			Builder::container(
				array(
					'content_width' => 'full',
					'padding'       => Builder::box( 0, 48, 96, 48 ),
				),
				array(
					Builder::widget(
						'image',
						array(
							'image'      => Builder::image( Images::attachment_id( 'home-01.jpg' ) ),
							'image_size' => 'full',
							'width'      => Builder::size( 100, '%' ),
							'fm_scroll'  => 'expand',
						)
					),
				)
			),
			Builder::container(
				array(
					'content_width'  => 'boxed',
					'flex_direction' => 'row',
					'flex_gap'       => array(
						'column' => '48',
						'row'    => '48',
						'unit'   => 'px',
					),
					'padding'        => Builder::box( 96, 48, 96, 48 ),
				),
				array(
					Builder::container(
						array(
							'width'          => Builder::size( 50, '%' ),
							'flex_direction' => 'column',
						),
						array(
							$this->label( 'Reveal words · Wipe up', 'fade-up' ),
							Builder::widget(
								'text-editor',
								array(
									'editor'                 => '<p>We design houses, hotels, workplaces and the objects inside them, for the slow parts of life. Every project starts on site, with a level and a notebook, long before the first drawing.</p>',
									'text_color'             => self::INK,
									'typography_typography'  => 'custom',
									'typography_font_family' => 'Bodoni Moda',
									'typography_font_size'   => Builder::size( 30 ),
									'typography_line_height' => Builder::size( 1.25, 'em' ),
									'fm_entrance'            => 'words',
								)
							),
						),
						true
					),
					Builder::container(
						array(
							'width' => Builder::size( 50, '%' ),
						),
						array(
							Builder::widget(
								'image',
								array(
									'image'       => Builder::image( Images::attachment_id( 'casa-nera-01.jpg' ) ),
									'image_size'  => 'large',
									'fm_entrance' => 'clip-up',
									'fm_cursor'   => 'View',
								)
							),
						),
						true
					),
				)
			),
			Builder::container(
				array(
					'content_width' => 'full',
					'min_height'    => Builder::size( 80, 'vh' ),
					'padding'       => Builder::box( 0 ),
				),
				array(
					Builder::widget(
						'image',
						array(
							'image'        => Builder::image( Images::attachment_id( 'monolith-house-01.jpg' ) ),
							'image_size'   => 'full',
							'width'        => Builder::size( 100, '%' ),
							'height'       => Builder::size( 80, 'vh' ),
							'object-fit'   => 'cover',
							'fm_scroll'    => 'parallax',
							'fm_speed'     => 16,
							'fm_entrance'  => 'scale-in',
						)
					),
				)
			),
			Builder::container(
				array(
					'content_width'  => 'boxed',
					'flex_direction' => 'column',
					'padding'        => Builder::box( 120, 48, 200, 48 ),
				),
				array(
					$this->label( 'Reveal lines · Wipe from left', 'fade-up' ),
					$this->display( 'Six stages, one conversation. From the first site visit to the day you move in.', 'lines', 72, 'px' ),
					Builder::widget(
						'image',
						array(
							'image'       => Builder::image( Images::attachment_id( 'the-quiet-hotel-01.jpg' ) ),
							'image_size'  => 'large',
							'fm_entrance' => 'clip-left',
						)
					),
				)
			),
		);
	}

	private function display( string $text, string $entrance, int|float $size, string $unit = 'px', float $delay = 0 ): array {
		return Builder::widget(
			'heading',
			array(
				'title'                     => $text,
				'header_size'               => 'h2',
				'title_color'               => self::INK,
				'typography_typography'     => 'custom',
				'typography_font_family'    => 'Bodoni Moda',
				'typography_font_weight'    => '400',
				'typography_font_size'      => Builder::size( $size, $unit ),
				'typography_line_height'    => Builder::size( 0.95, 'em' ),
				'typography_letter_spacing' => Builder::size( -0.03, 'em' ),
				'fm_entrance'               => $entrance,
				'fm_delay'                  => $delay ?: '',
			)
		);
	}

	private function label( string $text, string $entrance = '' ): array {
		return Builder::widget(
			'heading',
			array(
				'title'                      => $text,
				'header_size'                => 'p',
				'title_color'                => self::MUTED,
				'typography_typography'      => 'custom',
				'typography_font_family'     => 'Archivo',
				'typography_font_size'       => Builder::size( 12 ),
				'typography_font_weight'     => '500',
				'typography_text_transform'  => 'uppercase',
				'typography_letter_spacing'  => Builder::size( 0.12, 'em' ),
				'fm_entrance'                => $entrance,
			)
		);
	}

	private function page(): int {
		$ids = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- CLI only.
			)
		);
		$id  = (int) ( $ids[0] ?? 0 );

		if ( ! $id ) {
			$id = (int) wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_title'  => 'Widget lab',
					'post_name'   => 'widget-lab',
					'post_status' => 'private',
				)
			);
			update_post_meta( $id, self::META_KEY, 1 );
			update_post_meta( $id, '_elementor_page_settings', array() );
		}

		return $id;
	}
}
