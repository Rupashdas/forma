<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Model\Models;
use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Drawings;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * The Elementor body of every project (spec 4.1, 5.2), saved into the project post and shown through the Single
 * template's Post Content widget. Each body is assembled from nine kinds of section, the first two always first and the
 * last always last; the project's layout (a, b or c in data/projects.php) orders the ones between, so that projects do
 * not repeat:
 *
 *   model      An inset Panel with the project's study model: its recipe, written into the widget's volumes so the model
 *              is editable in Elementor, and found again by {@see Models::for_project()} from there.
 *   facts      Client, Area, Status, Team and Photography as label and value pairs, in a grid.
 *   narrative  The site and The idea in two columns.
 *   band       One landscape photograph in an inset rounded panel, drifting as it scrolls.
 *   pair       Two photographs, offset.
 *   quote      A pull quote.
 *   gallery    The project's photographs as a justified Pro Gallery with a lightbox.
 *   compare    Drawing / Built, on the projects that have a drawing.
 *   result     The result and, where there is one, the recognition as a chip.
 *
 * Text, grids and the model sit in the boxed 1320px column; only the model Panel and the photograph band run on to the
 * viewport's inset.
 *
 * Photographs are found by name (`{slug}-02.jpg` to `-06.jpg`; 01 is the featured image the Single template shows), so
 * a project whose photographs are not imported yet gets a complete text-only body (model, facts, narrative, quote and
 * result), and rerunning the build after `wp forma images` picks them up. A section that needs photographs is left out
 * when there are too few, never shown empty. Pictures are chosen by shape: the widest for the band, a portrait and a
 * landscape for the pair.
 */
final class ProjectBodies {

	/** Section order per layout variant. Sections that need missing photographs or a drawing are dropped. */
	private const LAYOUTS = array(
		'a' => array( 'model', 'facts', 'narrative', 'band', 'pair', 'quote', 'gallery', 'compare', 'result' ),
		'b' => array( 'model', 'facts', 'pair', 'narrative', 'band', 'compare', 'quote', 'gallery', 'result' ),
		'c' => array( 'model', 'facts', 'narrative', 'quote', 'gallery', 'band', 'pair', 'compare', 'result' ),
	);

	/** Vertical padding of a boxed section. */
	private const MID = 'clamp(32px, 4.4vw, 72px)';

	/** Photographs below this width-to-height ratio count as portrait. */
	private const PORTRAIT = 0.9;

	private array $projects;

	private Content $content;

	public function __construct( private \Closure $log ) {
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
		$this->content  = new Content( $log );
	}

	/**
	 * Build and save every project's body.
	 *
	 * @return array<string,int> Project post id by slug.
	 * @throws \RuntimeException When a project has not been seeded yet.
	 */
	public function build(): array {
		$ids    = array();
		$saved  = 0;
		$photos = 0;

		foreach ( $this->projects as $project ) {
			$id = $this->content->project_id( $project['slug'] );

			if ( ! $id ) {
				throw new \RuntimeException( esc_html( "The project {$project['slug']} does not exist: run `wp forma content` first." ) );
			}

			$elements = $this->elements( $project['slug'] );
			$ok       = Builder::save( $id, $elements, $this->settings() );
			$count    = count( $this->photos( $project['slug'] ) );

			$ids[ $project['slug'] ] = $id;
			$saved                  += $ok ? 1 : 0;
			$photos                 += $count > 0 ? 1 : 0;

			( $this->log )(
				sprintf(
					'Project body %s: layout %s, %d photos%s, %d sections (#%d)%s.',
					$project['slug'],
					$project['layout'],
					$count,
					$project['drawing'] && Drawings::attachment_id( $project['slug'] ) ? ', drawing' : '',
					count( $elements ),
					$id,
					$ok ? '' : ' ' . Builder::SKIPPED
				)
			);
		}

		( $this->log )( sprintf( 'Project bodies: %d built, %d with photographs, %d text only.', $saved, $photos, count( $ids ) - $photos ) );

		return $ids;
	}

	/**
	 * Document settings: the page layout is "Theme". Left on Default, Elementor applies the Kit's default layout
	 * (Header and footer) to any post built with it, which prints the body alone and bypasses the Single template.
	 * With Theme the Theme Builder single template prints the page and this body through its Post Content widget.
	 */
	public function settings(): array {
		return array( 'template' => 'elementor_theme' );
	}

	/**
	 * @param string $slug A project's slug.
	 * @throws \InvalidArgumentException For a slug that is not in the project data.
	 */
	public function elements( string $slug ): array {
		$project = null;

		foreach ( $this->projects as $candidate ) {
			if ( $slug === $candidate['slug'] ) {
				$project = $candidate;
			}
		}

		if ( ! $project ) {
			throw new \InvalidArgumentException( esc_html( "Unknown project '{$slug}'." ) );
		}

		Builder::reset( "project:{$slug}" );

		$ctx      = $this->context( $project );
		$elements = array();

		foreach ( self::LAYOUTS[ $project['layout'] ] as $section ) {
			$built = match ( $section ) {
				'model'     => $this->model( $ctx ),
				'facts'     => $this->facts( $ctx ),
				'narrative' => $this->narrative( $ctx ),
				'band'      => $ctx['full'] ? $this->band( $ctx ) : null,
				'pair'      => $ctx['pair'] ? $this->pair( $ctx ) : null,
				'quote'     => $this->quote( $ctx ),
				'gallery'   => count( $ctx['photos'] ) >= 3 ? $this->gallery( $ctx ) : null,
				'compare'   => $ctx['drawing'] && $ctx['built'] ? $this->compare( $ctx ) : null,
				'result'    => $this->result( $ctx ),
			};

			if ( $built ) {
				$elements[] = $built;
			}
		}

		return $elements;
	}

	// ---------------------------------------------------------------------------------------------------------------
	// What the body is made from.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The project's photographs 02 to 06 that exist in the media library, in order.
	 *
	 * @return array<int,array{n:int,id:int,ratio:float,caption:string}>
	 */
	private function photos( string $slug ): array {
		$photos = array();

		for ( $n = 2; $n <= 6; $n++ ) {
			$id = Images::attachment_id( sprintf( '%s-%02d.jpg', $slug, $n ) );

			if ( ! $id ) {
				continue;
			}

			$meta = wp_get_attachment_metadata( $id );
			$high = (int) ( $meta['height'] ?? 0 );

			$photos[] = array(
				'n'       => $n,
				'id'      => $id,
				'ratio'   => $high > 0 ? (int) $meta['width'] / $high : 1.5,
				'caption' => (string) ( wp_get_attachment_caption( $id ) ?: get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			);
		}

		return $photos;
	}

	/**
	 * Everything the sections draw on: the photographs and which one goes where, the drawing and the built photograph
	 * for the comparison, and the photography credit.
	 */
	private function context( array $project ): array {
		$slug   = $project['slug'];
		$photos = $this->photos( $slug );
		$pool   = $photos;
		$full   = null;
		$pair   = array();

		// The widest picture goes in the band (unless there are exactly two, which make the pair); the pair is the
		// narrowest and the widest of what is left.
		if ( $pool && 2 !== count( $pool ) ) {
			$full = $this->take( $pool, true );
		}

		if ( count( $pool ) >= 2 ) {
			$pair = array( $this->take( $pool, false ), $this->take( $pool, true ) );
		}

		$post_id = $this->content->project_id( $slug );
		$drawing = $project['drawing'] ? Drawings::attachment_id( $slug ) : 0;

		return array(
			'project' => $project,
			'layout'  => $project['layout'],
			'photos'  => $photos,
			'full'    => $full,
			'pair'    => $pair,
			'drawing' => $drawing,
			'built'   => (int) get_post_thumbnail_id( $post_id ),
			'credit'  => $this->credit( $slug ),
		);
	}

	/**
	 * Remove and return the photograph with the widest (or narrowest) shape.
	 *
	 * @param array<int,array> $pool Photographs still free; the chosen one is removed.
	 */
	private function take( array &$pool, bool $widest ): array {
		$best = null;

		foreach ( $pool as $key => $photo ) {
			if ( null === $best || ( $widest ? $photo['ratio'] > $pool[ $best ]['ratio'] : $photo['ratio'] < $pool[ $best ]['ratio'] ) ) {
				$best = $key;
			}
		}

		$photo = $pool[ $best ];
		unset( $pool[ $best ] );

		return $photo;
	}

	/** The photographers of the project's pictures, for the facts: one or two names, or "and others". */
	private function credit( string $slug ): string {
		$names = array();

		for ( $n = 1; $n <= 6; $n++ ) {
			$id = Images::attachment_id( sprintf( '%s-%02d.jpg', $slug, $n ) );

			if ( $id ) {
				$credit = get_post_meta( $id, Images::CREDIT_KEY, true );

				if ( ! empty( $credit['name'] ) ) {
					$names[ $credit['name'] ] = true;
				}
			}
		}

		$names = array_keys( $names );

		return match ( true ) {
			! $names            => 'To follow',
			count( $names ) > 2 => $names[0] . ', ' . $names[1] . ' and others',
			default             => implode( ' and ', $names ),
		};
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Sections.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The model: an inset Panel. From 1024px the heading and a line of copy sit in a narrow column at the left of the
	 * boxed 1320px column and the study model fills the rest; below that they stack. The model is the project's recipe,
	 * written into the widget's volumes (source "custom") so it can be edited in Elementor, with the recipe's camera
	 * distance; it can be turned, turned with the arrow keys, opened up (Exploded view) and shows its parts' labels.
	 */
	private function model( array $ctx ): array {
		$slug   = $ctx['project']['slug'];
		$recipe = Models::recipe( $slug );

		if ( ! $recipe ) {
			throw new \RuntimeException( esc_html( "There is no model recipe for '{$slug}' in data/models.php." ) );
		}

		$volumes = array();

		foreach ( $recipe['volumes'] as $volume ) {
			$volumes[] = array( '_id' => Builder::id() ) + $volume;
		}

		$settings = array(
			'source'             => 'custom',
			'volumes'            => $volumes,
			'camera'             => 'three-quarter',
			'view_height'        => Builder::size( 70, 'vh' ),
			'view_height_tablet' => Builder::size( 52, 'vh' ),
			'view_height_mobile' => Builder::size( 46, 'vh' ),
			'drag'               => 'yes',
			'keyboard'           => 'yes',
			'explode_toggle'     => 'yes',
			'callouts'           => 'yes',
		);

		if ( ! empty( $recipe['camera']['distance'] ) ) {
			$settings['distance'] = $recipe['camera']['distance'];
		}

		return Style::section(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( 'The model', 'heading', 'h2' ),
								Style::text(
									'<p>A study model of the scheme. Turn it, or open it up to see how it&#8217;s made.</p>',
									'body',
									'muted',
									array( 'custom_css' => 'selector p { max-width: 30ch; margin: 0; }' )
								),
							),
							30,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 14 ) )
						),
						Style::cell( array( Builder::widget( 'forma-study-model', $settings ) ), 70, 100, 100 ),
					),
					array(
						'flex_wrap'             => 'nowrap',
						'flex_align_items'      => 'center',
						'flex_direction_tablet' => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'              => Builder::gap( 'clamp(20px, 3vw, 48px)', null, 'custom' ),
					)
				),
			),
			'raised',
			array(
				'padding' => Builder::box( 'clamp(32px, 4vw, 64px)', Style::PANEL_GUTTER, 'clamp(16px, 2vw, 32px)', Style::PANEL_GUTTER, 'custom' ),
			)
		);
	}

	/**
	 * Client, Area, Status, Team and Photography as a label (Graphite) over its value (Body), in a grid container: five
	 * columns on desktop, three on tablet, two on mobile. No rules; the rows are set apart by space alone.
	 */
	private function facts( array $ctx ): array {
		$facts = $ctx['project']['facts'] + array( 'Photography' => $ctx['credit'] );
		$cells = array();

		foreach ( $facts as $label => $value ) {
			$cells[] = Style::stack(
				array(
					Style::label( $label ),
					Style::text( '<p>' . esc_html( $value ) . '</p>', 'body', 'ink', array( 'custom_css' => 'selector p { margin: 0; }' ) ),
				),
				array( 'flex_gap' => Builder::gap( 6 ) )
			);
		}

		return Style::section(
			array(
				Builder::container(
					array(
						'content_width'            => 'full',
						'container_type'           => 'grid',
						'grid_columns_grid'        => $this->fr( 5 ),
						'grid_columns_grid_tablet' => $this->fr( 3 ),
						'grid_columns_grid_mobile' => $this->fr( 2 ),
						'grid_gaps'                => Builder::gap( 'clamp(24px, 3vw, 40px)', 'clamp(16px, 2.4vw, 32px)', 'custom' ),
						// The control only offers equal rows (repeat(n, 1fr)); here each row is as tall as its own cells.
						'custom_css'               => 'selector { --e-con-grid-template-rows: auto; }',
					),
					$cells,
					true
				),
			),
			'page',
			array( 'padding' => $this->pad( self::MID, self::MID ) )
		);
	}

	/**
	 * The site and the idea, side by side. The layouts vary the proportions and which column drops: a is level, b
	 * drops the right-hand column, c drops the left. Mobile stacks them.
	 */
	private function narrative( array $ctx ): array {
		$project = $ctx['project'];
		[ $first, $second ] = match ( $ctx['layout'] ) {
			'b'     => array( 44, 48 ),
			'c'     => array( 36, 56 ),
			default => array( 46, 46 ),
		};
		$drop = array(
			'margin'        => Builder::box( 'clamp(0px, 9vw, 136px)', 0, 0, 0, 'custom' ),
			'margin_tablet' => Builder::box( 0 ),
		);

		return Style::section(
			array(
				Style::row(
					array(
						$this->column( $project['labels'][0], $project['site'], $first, 'c' === $ctx['layout'] ? $drop : array() ),
						$this->column( $project['labels'][1], $project['idea'], $second, 'b' === $ctx['layout'] ? $drop : array() ),
					),
					array(
						'flex_wrap'             => 'nowrap',
						'flex_justify_content'  => 'space-between',
						'flex_gap'              => Builder::gap( 32 ),
						'flex_direction_mobile' => 'column',
					)
				),
			),
			'page',
			array( 'padding' => $this->pad( self::MID, self::MID ) )
		);
	}

	/** One narrative column: a Subheading and the paragraph. */
	private function column( string $label, string $text, int $width, array $extra ): array {
		return Style::cell(
			array(
				Style::heading( $label, 'subheading', 'h2' ),
				Style::text( '<p>' . esc_html( $text ) . '</p>', 'body', 'ink', array( 'custom_css' => 'selector p { max-width: 54ch; margin: 0; }' ) ),
			),
			$width,
			$width,
			100,
			array( 'flex_gap' => Builder::gap( 14 ) ) + $extra
		);
	}

	/**
	 * One landscape photograph in an inset rounded panel, running the width of the page inside the inset, drifting as the
	 * page scrolls (depth 8). The panel clips the drift and the frame crops at a fixed ratio, so the drift never shows
	 * an edge: 21:9 on desktop, 3:2 on tablet, 4:5 on mobile.
	 */
	private function band( array $ctx ): array {
		$photo = $ctx['full'];

		return Style::section(
			array(
				Builder::widget(
					'image',
					array(
						'image'      => Builder::image( $photo['id'] ),
						'image_size' => 'full',
						'width'      => Builder::size( 100, '%' ),
						'fm_scroll'  => 'parallax',
						'fm_speed'   => 8,
						'custom_css' => $this->frame_css( $photo['ratio'] >= 1.15 ? array( '21 / 9', '3 / 2', '4 / 5' ) : array( '16 / 9', '4 / 3', '4 / 5' ), false ),
					)
				),
			),
			'raised',
			array(
				'padding'     => Builder::box( 0 ),
				'css_classes' => 'forma-band',
			),
			'full'
		);
	}

	/**
	 * A narrow and a wide photograph, the second dropped (layout a), the order reversed (b), or the first dropped (c).
	 * A portrait takes 4:5 and a landscape 3:2; two of a kind share the width equally. Mobile stacks them.
	 */
	private function pair( array $ctx ): array {
		$pair = $ctx['pair'];

		if ( 'b' === $ctx['layout'] ) {
			$pair = array_reverse( $pair );
		}

		$portrait = array_map( static fn( array $photo ): bool => $photo['ratio'] < self::PORTRAIT, $pair );
		$widths   = $portrait[0] === $portrait[1] ? array( 48, 48 ) : array_map( static fn( bool $is ): int => $is ? 40 : 56, $portrait );
		$drop     = array(
			'margin'        => Builder::box( 'clamp(0px, 10vw, 160px)', 0, 0, 0, 'custom' ),
			'margin_tablet' => Builder::box( 0 ),
		);
		$cells    = array();

		foreach ( $pair as $index => $photo ) {
			$dropped = 'c' === $ctx['layout'] ? 0 === $index : 1 === $index;

			$cells[] = Style::cell(
				array(
					Builder::widget(
						'image',
						array(
							'image'      => Builder::image( $photo['id'] ),
							'image_size' => 'large',
							'width'      => Builder::size( 100, '%' ),
							'custom_css' => $this->frame_css( $portrait[ $index ] ? array( '4 / 5', '4 / 5', '4 / 5' ) : array( '3 / 2', '3 / 2', '3 / 2' ), true ),
						)
					),
					$this->caption( $photo ),
				),
				$widths[ $index ],
				$widths[ $index ],
				100,
				array( 'flex_gap' => Builder::gap( 12 ) ) + ( $dropped ? $drop : array() )
			);
		}

		return Style::section(
			array(
				Style::row(
					$cells,
					array(
						'flex_wrap'             => 'nowrap',
						'flex_justify_content'  => 'space-between',
						'flex_gap'              => Builder::gap( 24 ),
						'flex_direction_mobile' => 'column',
					)
				),
			),
			'page',
			array( 'padding' => $this->pad( self::MID, self::MID ) )
		);
	}

	/** A client's words in the Heading style (Archivo 500, upright), narrow, with the speaker in Meta below. */
	private function quote( array $ctx ): array {
		$quote = $ctx['project']['quote'];

		return Style::section(
			array(
				Style::cell(
					array(
						Style::text(
							'<p>&#8220;' . esc_html( $quote['text'] ) . '&#8221;</p>',
							'heading',
							'ink',
							array( 'custom_css' => 'selector p { max-width: 22em; margin: 0; }' )
						),
						Style::heading( $quote['cite'], 'meta', 'p', 'muted' ),
					),
					66,
					100,
					100,
					array( 'flex_gap' => Builder::gap( 'clamp(16px, 2vw, 24px)', null, 'custom' ) )
				),
			),
			'page',
			array( 'padding' => $this->pad( 'clamp(40px, 6vw, 96px)', 'clamp(40px, 6vw, 96px)' ) )
		);
	}

	/** Every photograph in a justified Pro Gallery: rounded 12px, captions over a dark veil on hover, and a lightbox. */
	private function gallery( array $ctx ): array {
		$items = array();

		foreach ( $ctx['photos'] as $photo ) {
			$items[] = array(
				'id'  => $photo['id'],
				'url' => (string) wp_get_attachment_image_url( $photo['id'], 'full' ),
			);
		}

		return Style::section(
			array(
				Builder::widget(
					'gallery',
					array(
						'gallery_type'                  => 'single',
						'gallery'                       => $items,
						'gallery_layout'                => 'justified',
						'ideal_row_height'              => Builder::size( 340 ),
						'gap'                           => Builder::size( 12 ),
						'link_to'                       => 'file',
						'open_lightbox'                 => 'yes',
						'thumbnail_image_size'          => 'forma-960',
						'image_border_radius'           => Builder::size( 12 ),
						'overlay_background'            => 'yes',
						'overlay_title'                 => 'caption',
						// Clear at rest, a dark veil with the caption on hover or focus.
						'overlay_background_background' => 'classic',
						'overlay_background_color'      => 'rgba(20, 20, 20, 0)',
						'overlay_background_hover_color' => 'rgba(20, 20, 20, 0.5)',
						'content_alignment'             => 'left',
						'content_vertical_position'     => 'bottom',
						'content_padding'               => Builder::size( 20 ),
						'__globals__'                   => array(
							'title_color'                 => Style::color( 'page' ),
							'title_typography_typography' => Style::font( 'meta' ),
						),
					)
				),
			),
			'page',
			array( 'padding' => $this->pad( self::MID, self::MID ) )
		);
	}

	/**
	 * Drawing / Built: a short note on the left and the comparison on the right. The drawing is the line rendering
	 * made from the built photograph; the widget (rounded 18px, its labels as chips) reveals one over the other from a
	 * native range input.
	 */
	private function compare( array $ctx ): array {
		return Style::section(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( 'Drawn, then built', 'heading', 'h2' ),
								Style::text(
									'<p>Drag the handle, or use the arrow keys, to move from the line drawing to the finished building.</p>',
									'body',
									'muted',
									array( 'custom_css' => 'selector p { max-width: 32ch; margin: 0; }' )
								),
							),
							28,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 14 ) )
						),
						Style::cell(
							array(
								Builder::widget(
									'forma-before-after',
									array(
										'before_image' => Builder::image( $ctx['drawing'] ),
										'before_label' => 'Drawing',
										'after_image'  => Builder::image( $ctx['built'] ),
										'after_label'  => 'Built',
										'start'        => Builder::size( 50, '%' ),
										'image_size'   => 'large',
									)
								),
							),
							66,
							100,
							100
						),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'flex-start',
						'flex_gap'                => Builder::gap( 32 ),
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
					)
				),
			),
			'page',
			array( 'padding' => $this->pad( self::MID, self::MID ) )
		);
	}

	/**
	 * The result: its Subheading on the left and the text on the right, with the recognition under it as a chip when the
	 * project has one.
	 */
	private function result( array $ctx ): array {
		$project = $ctx['project'];
		$body    = array(
			Style::text(
				'<p>' . esc_html( $project['result'] ) . '</p>',
				'body',
				'ink',
				array( 'custom_css' => 'selector p { max-width: 58ch; margin: 0; }' )
			),
		);

		if ( '' !== trim( (string) $project['recognition'] ) ) {
			$body[] = Style::row(
				array(
					Style::label( 'Recognition' ),
					Style::chip( $project['recognition'] ),
				),
				array(
					'flex_wrap'        => 'wrap',
					'flex_align_items' => 'center',
					'flex_gap'         => Builder::gap( 10 ),
				)
			);
		}

		return Style::section(
			array(
				Style::row(
					array(
						Style::cell( array( Style::heading( $project['labels'][2], 'subheading', 'h2' ) ), 30, 100, 100 ),
						Style::cell( $body, 62, 100, 100, array( 'flex_gap' => Builder::gap( 'clamp(20px, 2.4vw, 32px)', null, 'custom' ) ) ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_gap'                => Builder::gap( 14 ),
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
					)
				),
			),
			'page',
			array( 'padding' => $this->pad( self::MID, 'clamp(56px, 7vw, 112px)' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Shared.
	// ---------------------------------------------------------------------------------------------------------------

	/** Section padding with the gutter at the sides. */
	private function pad( string $top, string $bottom ): array {
		return Builder::box( $top, 'var(--forma-gutter)', $bottom, 'var(--forma-gutter)', 'custom' );
	}

	/** A grid container's column count, as the `fr` slider stores it. */
	private function fr( int $columns ): array {
		return array(
			'unit'  => 'fr',
			'size'  => $columns,
			'sizes' => array(),
		);
	}

	/** The photograph's own caption ("The deck at the entrance"), in the Meta style under it. */
	private function caption( array $photo ): array {
		return Style::heading( esc_html( $photo['caption'] ), 'meta', 'p', 'muted' );
	}

	/**
	 * Crop an image widget's photograph to a fixed frame, so layouts do not shift with the photograph's shape and a
	 * parallax drift never shows an edge.
	 *
	 * @param string[] $ratios Aspect ratios for desktop, tablet and mobile, e.g. `21 / 9`.
	 * @param bool     $round  Round the picture's corners (the image radius); the band's panel does its own.
	 */
	private function frame_css( array $ratios, bool $round ): string {
		[ $desktop, $tablet, $mobile ] = $ratios;
		$radius                        = $round ? "\n\t\t\tborder-radius: var(--forma-r-img);" : '';

		return <<<CSS
		selector img {
			display: block;
			width: 100%;
			aspect-ratio: {$desktop};
			object-fit: cover;{$radius}
		}
		@media (max-width: 1023px) {
			selector img {
				aspect-ratio: {$tablet};
			}
		}
		@media (max-width: 767px) {
			selector img {
				aspect-ratio: {$mobile};
			}
		}
		CSS;
	}
}
