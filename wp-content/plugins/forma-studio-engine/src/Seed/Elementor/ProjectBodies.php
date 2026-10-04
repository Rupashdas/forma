<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Drawings;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * The Elementor body of every project (spec 7.3), saved into the project post and shown through the Single template's
 * Post Content widget. Each body is assembled from eight kinds of section, ordered and varied by the project's layout
 * (a, b or c in data/projects.php) so that projects do not repeat:
 *
 *   facts      Client, Area, Status, Team and Photography in a hairline table.
 *   narrative  The site and The idea in two columns.
 *   full       One photograph edge to edge, drifting as it scrolls: the only full-width band of the body.
 *   pair       Two photographs, offset.
 *   quote      A pull quote.
 *   gallery    The project's photographs as a justified Pro Gallery with a lightbox.
 *   compare    Drawing / Built, on the projects that have a drawing.
 *   result     The result and, where there is one, the recognition line.
 *
 * Photographs are found by name (`{slug}-02.jpg` to `-06.jpg`; 01 is the featured image the Single template shows), so
 * a project whose photographs are not imported yet gets a complete text-only body, and rerunning the build after
 * `wp forma images` picks them up. A section that needs photographs is left out when there are too few, never shown
 * empty. Pictures are chosen by shape: the widest for the full-width section, a portrait and a landscape for the pair.
 */
final class ProjectBodies {

	/** Section order per layout variant. Sections that need missing photographs or a drawing are dropped. */
	private const LAYOUTS = array(
		'a' => array( 'facts', 'narrative', 'full', 'pair', 'quote', 'gallery', 'compare', 'result' ),
		'b' => array( 'facts', 'pair', 'narrative', 'full', 'compare', 'quote', 'gallery', 'result' ),
		'c' => array( 'facts', 'narrative', 'quote', 'gallery', 'full', 'pair', 'compare', 'result' ),
	);

	/** Which surface the pull quote sits on, per layout. A body with no photographs always gets the bottle green. */
	private const QUOTE_SURFACE = array(
		'a' => 'page',
		'b' => 'raised',
		'c' => 'deep',
	);

	/** Vertical padding of a section on the page surface; bands use the full section padding. */
	private const MID = 'clamp(48px, 7vw, 112px)';

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
				'facts'     => $this->facts( $ctx ),
				'narrative' => $this->narrative( $ctx ),
				'full'      => $ctx['full'] ? $this->full( $ctx ) : null,
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

		// The widest picture goes across the page (unless there are exactly two, which make the pair); the pair is the
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
			'surface' => $photos ? self::QUOTE_SURFACE[ $project['layout'] ] : 'deep',
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

	/** The photographers of the project's pictures, for the facts table: one or two names, or "and others". */
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
			! $names          => 'To follow',
			count( $names ) > 2 => $names[0] . ', ' . $names[1] . ' and others',
			default           => implode( ' and ', $names ),
		};
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Sections.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Client, Area, Status, Team and Photography as a hairline table: five across on desktop, three by two on tablet,
	 * two by three on mobile (the last cell takes the full row).
	 */
	private function facts( array $ctx ): array {
		$facts = $ctx['project']['facts'] + array( 'Photography' => $ctx['credit'] );
		$last  = count( $facts ) - 1;
		$cells = array();
		$index = 0;

		foreach ( $facts as $label => $value ) {
			$cells[] = Style::cell(
				array(
					Style::label( $label ),
					Style::text( '<p>' . esc_html( $value ) . '</p>', 'body', 'ink', array( 'custom_css' => 'selector p { margin: 0; }' ) ),
				),
				100 / count( $facts ),
				100 / 3,
				$index === $last ? 100 : 50,
				array(
					'flex_gap'       => Builder::gap( 8 ),
					'padding'        => Builder::box( 22, 24, 26, 0 === $index ? 0 : 24 ),
					'padding_tablet' => Builder::box( 18, 20, 22, 0 ),
					'border_border'  => 'solid',
					'border_width'   => Builder::box( 0, 0, 0, 0 === $index ? 0 : 1 ),
					// Once the cells wrap there are no vertical rules, only a hairline above each cell.
					'border_width_tablet' => Builder::box( 1, 0, 0, 0 ),
					'__globals__'    => array( 'border_color' => Style::color( 'line' ) ),
				)
			);
			++$index;
		}

		return Style::section(
			array(
				Style::row(
					$cells,
					array(
						'border_border'       => 'solid',
						'border_width'        => Builder::box( 1, 0, 1, 0 ),
						'border_width_tablet' => Builder::box( 0, 0, 1, 0 ),
						'__globals__'         => array( 'border_color' => Style::color( 'line' ) ),
					)
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

	/** One narrative column: a heading and the paragraph. */
	private function column( string $label, string $text, int $width, array $extra ): array {
		return Style::cell(
			array(
				Style::serif( $label, 'clamp(32px, 3.6vw, 56px)', 'h2' ),
				Style::text( '<p>' . esc_html( $text ) . '</p>', 'body', 'ink', array( 'custom_css' => 'selector { max-width: 54ch; }' ) ),
			),
			$width,
			$width,
			100,
			array( 'flex_gap' => Builder::gap( 18 ) ) + $extra
		);
	}

	/**
	 * One photograph edge to edge, drifting as the page scrolls: the body's only full-width band. The frame crops at a
	 * fixed ratio so the drift never shows an edge: wide for a landscape photograph, 16:9 for a portrait. The caption
	 * sits below it inside the gutter.
	 */
	private function full( array $ctx ): array {
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
						'fm_speed'   => 10,
						'custom_css' => $this->frame_css( $photo['ratio'] >= 1.15 ? array( '21 / 9', '16 / 9', '4 / 3' ) : array( '16 / 9', '4 / 3', '4 / 5' ) ),
					)
				),
				Style::stack(
					array( $this->caption( $photo ) ),
					array( 'padding' => Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' ) )
				),
			),
			'page',
			array(
				'padding'  => Builder::box( self::MID, 0, self::MID, 0, 'custom' ),
				'flex_gap' => Builder::gap( 14 ),
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
							'custom_css' => $this->frame_css( $portrait[ $index ] ? array( '4 / 5', '4 / 5', '4 / 5' ) : array( '3 / 2', '3 / 2', '3 / 2' ) ),
						)
					),
					$this->caption( $photo ),
				),
				$widths[ $index ],
				$widths[ $index ],
				100,
				array( 'flex_gap' => Builder::gap( 14 ) ) + ( $dropped ? $drop : array() )
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

	/** A client's words in the display serif, italic, with the speaker below. */
	private function quote( array $ctx ): array {
		$quote   = $ctx['project']['quote'];
		$surface = $ctx['surface'];
		$padding = 'page' === $surface ? $this->pad( self::MID, self::MID ) : $this->pad( 'clamp(64px, 9vw, 144px)', 'clamp(64px, 9vw, 144px)' );

		return Style::section(
			array(
				Style::cell(
					array(
						Style::text(
							'<p>“' . esc_html( $quote['text'] ) . '”</p>',
							'heading',
							'ink',
							array(
								// The Heading global sets the face and size; the whole quote is set in the italic cut.
								'custom_css' => 'selector { font-style: italic; max-width: 20em; } selector p { margin: 0; }',
							)
						),
						Style::label( $quote['cite'] ),
					),
					80,
					100,
					100,
					array( 'flex_gap' => Builder::gap( 'clamp(20px, 2.5vw, 32px)', null, 'custom' ) )
				),
			),
			$surface,
			array( 'padding' => $padding )
		);
	}

	/** Every photograph in a justified Pro Gallery: hover captions, and a lightbox with the caption. */
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
						'gap'                           => Builder::size( 16 ),
						'link_to'                       => 'file',
						'open_lightbox'                 => 'yes',
						'thumbnail_image_size'          => 'forma-960',
						'overlay_background'            => 'yes',
						'overlay_title'                 => 'caption',
						'overlay_background_background' => 'classic',
						'overlay_background_color'      => 'rgba(22, 25, 23, 0.5)',
						'content_alignment'             => 'left',
						'content_vertical_position'     => 'bottom',
						'content_padding'               => Builder::size( 20 ),
						'__globals__'                   => array(
							'title_color'                       => Style::color( 'page' ),
							'title_typography_typography'       => Style::font( 'meta' ),
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
	 * made from the built photograph; the widget reveals one over the other from a native range input.
	 */
	private function compare( array $ctx ): array {
		return Style::section(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::serif( 'Drawn, then built', 'clamp(32px, 3.6vw, 56px)', 'h2' ),
								Style::text(
									'<p>Drag the handle, or use the arrow keys, to move from the line drawing to the finished building.</p>',
									'body',
									'muted',
									array( 'custom_css' => 'selector { max-width: 32ch; } selector p { margin: 0; }' )
								),
							),
							28,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 18 ) )
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
	 * The result: its heading on the left, the text set large on the right, with the recognition line under it when
	 * the project has one.
	 */
	private function result( array $ctx ): array {
		$project = $ctx['project'];
		$body    = array(
			Style::text(
				'<p>' . esc_html( $project['result'] ) . '</p>',
				'statement',
				'ink',
				array( 'custom_css' => 'selector { max-width: 32em; } selector p { margin: 0; }' )
			),
		);

		if ( '' !== trim( (string) $project['recognition'] ) ) {
			$body[] = Style::row(
				array(
					Style::label( 'Recognition', 'accent' ),
					Style::heading( $project['recognition'], 'subheading', 'p', 'ink' ),
				),
				array(
					'flex_align_items'     => 'center',
					'flex_gap'             => Builder::gap( 12, 32 ),
					'padding'              => Builder::box( 20, 0, 0, 0 ),
					'border_border'        => 'solid',
					'border_width'         => Builder::box( 1, 0, 0, 0 ),
					'__globals__'          => array( 'border_color' => Style::color( 'line' ) ),
				)
			);
		}

		return Style::section(
			array(
				Style::row(
					array(
						Style::cell( array( Style::serif( $project['labels'][2], 'clamp(32px, 3.6vw, 56px)', 'h2' ) ), 24, 100, 100 ),
						Style::cell( $body, 68, 100, 100, array( 'flex_gap' => Builder::gap( 'clamp(28px, 3.5vw, 48px)', null, 'custom' ) ) ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_gap'                => Builder::gap( 24 ),
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
					)
				),
			),
			'page',
			array(
				'padding' => $this->pad( self::MID, 'var(--forma-section)' ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Shared.
	// ---------------------------------------------------------------------------------------------------------------

	/** Section padding with the gutter at the sides. */
	private function pad( string $top, string $bottom ): array {
		return Builder::box( $top, 'var(--forma-gutter)', $bottom, 'var(--forma-gutter)', 'custom' );
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
	 */
	private function frame_css( array $ratios ): string {
		[ $desktop, $tablet, $mobile ] = $ratios;

		return <<<CSS
		selector img {
			display: block;
			width: 100%;
			aspect-ratio: {$desktop};
			object-fit: cover;
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
