<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;
use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * The Home page (spec 7.1), built from Elementor containers and widgets plus the saved components: hero, statement,
 * selected work, marquee, the making of a space, practice, index, recognition, quote and the closing call to action.
 * Sections sit on Chalk, broken by Raised and bottle green (deep) bands; every colour and type style is a Kit global.
 */
final class Home {

	public const KEY = 'home';

	/** The saved components Home shows, which must exist before it is built. */
	private const COMPONENTS = array( 'project-card', 'project-card-wide', 'recognition', 'closing-cta' );

	private array $site;

	private array $projects;

	private Content $content;

	public function __construct( private \Closure $log ) {
		$this->site     = require FORMA_ENGINE_PATH . 'data/site.php';
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
		$this->content  = new Content( $log );
	}

	/**
	 * @return int The Home page's post id.
	 * @throws \RuntimeException When the page or one of its components has not been seeded yet.
	 */
	public function build(): int {
		$id = $this->content->page_id( self::KEY );

		if ( ! $id ) {
			throw new \RuntimeException( 'The Home page does not exist: run `wp forma content` first.' );
		}

		foreach ( self::COMPONENTS as $component ) {
			if ( ! Templates::id( $component ) ) {
				throw new \RuntimeException( esc_html( "The {$component} component does not exist: run `wp forma design --only=components` first." ) );
			}
		}

		if ( Builder::save( $id, $this->elements(), $this->settings() ) ) {
			( $this->log )( "Home: 10 sections (#{$id})." );
		} else {
			( $this->log )( 'Home: ' . Builder::SKIPPED );
		}

		return $id;
	}

	/** Page settings: edge to edge under the Theme Builder header and footer, with no page title (the hero is the H1). */
	public function settings(): array {
		return array(
			'template'   => 'elementor_header_footer',
			'hide_title' => 'yes',
		);
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			$this->hero(),
			$this->statement(),
			$this->selected_work(),
			$this->marquee(),
			$this->process(),
			$this->practice(),
			$this->index(),
			$this->component( 'recognition' ),
			$this->quote(),
			$this->component( 'closing-cta' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Hero.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The wordmark, the discipline labels and a title block, then a photograph that starts inset and expands to the
	 * viewport edges as the page scrolls (wide screens only). The section has no side padding, so the photograph can
	 * reach the edges; the text blocks carry the gutter themselves.
	 */
	private function hero(): array {
		$gutter = Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' );

		return Style::section(
			array(
				Style::stack(
					array(
						Style::heading( 'FORMA', 'display-xl', 'h1', 'ink', array( 'fm_entrance' => 'chars' ) ),
						$this->hero_row(),
					),
					array(
						'padding'  => $gutter,
						'flex_gap' => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
					)
				),
				$this->hero_image(),
				Style::row(
					array(
						Style::label( 'Fig. 01: a doorway in Lisbon, morning light' ),
						Style::label( 'Scroll' ),
					),
					array(
						'padding'              => $gutter,
						'flex_justify_content' => 'space-between',
						'flex_align_items'     => 'center',
						'flex_gap'             => Builder::gap( 16 ),
					)
				),
			),
			'page',
			array(
				'min_height' => Builder::size( 100, 'vh' ),
				'padding'    => Builder::box( 'clamp(112px, 14vw, 168px)', 0, 'clamp(24px, 3vw, 48px)', 0, 'custom' ),
				'flex_gap'   => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
			)
		);
	}

	/** Disciplines stacked on the left (one row on mobile), the title block on the right. */
	private function hero_row(): array {
		$labels = array();

		foreach ( array( 'Architecture', 'Interiors', 'Objects' ) as $index => $label ) {
			$labels[] = Style::label(
				$label,
				'ink',
				array(
					'fm_entrance' => 'lines',
					'fm_delay'    => round( 0.2 + 0.08 * $index, 2 ),
				)
			);
		}

		return Style::row(
			array(
				Style::cell(
					$labels,
					'auto',
					'auto',
					100,
					array(
						'flex_gap'             => Builder::gap( 6 ),
						'flex_direction_mobile' => 'row',
						'flex_gap_mobile'      => Builder::gap( 6, 20 ),
					)
				),
				$this->title_block(),
			),
			array(
				'flex_wrap'               => 'nowrap',
				'flex_justify_content'    => 'space-between',
				'flex_align_items'        => 'flex-end',
				'flex_gap'                => Builder::gap( 24 ),
				'flex_direction_mobile'   => 'column',
				'flex_align_items_mobile' => 'stretch',
			)
		);
	}

	/** Place, founding year and coordinates in three hairline cells, like the title block on a drawing sheet. */
	private function title_block(): array {
		$cells = array();

		foreach ( array( 'Lisbon', 'Est. ' . $this->site['studio']['founded'], $this->site['studio']['coordinates'] ) as $index => $text ) {
			$cells[] = Style::label(
				$text,
				'muted',
				array(
					'_padding'        => Builder::box( 12, 20, 12, 20 ),
					'_padding_mobile' => Builder::box( 10, 12, 10, 12 ),
					'_border_border'  => 'solid',
					'_border_width'   => Builder::box( 0, 0, 0, 0 === $index ? 0 : 1 ),
					'__globals__'     => array( '_border_color' => Style::color( 'line' ) ),
				)
			);
		}

		return Style::row(
			$cells,
			array(
				'width'         => Builder::size( 'auto', 'custom' ),
				'width_tablet'  => Builder::size( 'auto', 'custom' ),
				'width_mobile'  => Builder::size( 100, '%' ),
				'border_border' => 'solid',
				'border_width'  => Builder::box( 1, 0, 1, 0 ),
				'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}

	/**
	 * The hero photograph. The wrapper wipes up on load; the photograph itself expands from inset to full bleed, which
	 * the motion runtime does from 1024px up. Below that it sits inside the gutters, cropped to 3:2 and then 4:5.
	 */
	private function hero_image(): array {
		$gutter = Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' );

		return Style::stack(
			array(
				Builder::widget(
					'image',
					array(
						'image'      => Builder::image( Images::attachment_id( 'home-01.jpg' ) ),
						'image_size' => 'full',
						'width'      => Builder::size( 100, '%' ),
						'fm_scroll'  => 'expand',
						'custom_css' => <<<'CSS'
						selector img {
							display: block;
							width: 100%;
							aspect-ratio: 16 / 9;
							object-fit: cover;
							object-position: 45% 50%;
						}
						@media (max-width: 1023px) {
							selector img {
								aspect-ratio: 3 / 2;
							}
						}
						@media (max-width: 767px) {
							selector img {
								aspect-ratio: 4 / 5;
							}
						}
						CSS,
					)
				),
			),
			array(
				'padding_tablet' => $gutter,
				'padding_mobile' => $gutter,
				'fm_entrance'    => 'clip-up',
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. Statement.
	// ---------------------------------------------------------------------------------------------------------------

	/** One large paragraph on the bottle green, with its label in a narrow left column. */
	private function statement(): array {
		return Style::section(
			array(
				Style::row(
					array(
						Style::cell( array( Style::label( 'The studio' ) ), 25, 100 ),
						Style::cell(
							array(
								Style::text(
									'<p>FORMA is a studio of twenty-three architects, interior designers and makers in Lisbon. We design houses, hotels, workplaces and the objects inside them, for the slow parts of life. Every project starts on site, with a level and a notebook, long before the first drawing.</p>',
									'statement',
									'ink',
									array( 'fm_entrance' => 'lines' )
								),
								Style::button( 'About the studio', $this->permalink( 'studio' ) ),
							),
							75,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 'clamp(32px, 4vw, 56px)', null, 'custom' ) )
						),
					),
					array(
						'flex_wrap'            => 'nowrap',
						'flex_direction_tablet' => 'column',
						'flex_gap'             => Builder::gap( 24 ),
					)
				),
			),
			'deep'
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 3. Selected work.
	// ---------------------------------------------------------------------------------------------------------------

	/** Five featured projects in an asymmetric grid: a wide feature, an offset pair, a second wide, a narrow close. */
	private function selected_work(): array {
		$archive = get_post_type_archive_link( Projects::POST_TYPE );

		return Style::section(
			array(
				Style::row(
					array(
						$this->intro( 'Selected work', 'Five recent projects' ),
						Style::button( 'All projects', $archive ? (string) $archive : home_url( '/projects/' ) ),
					),
					array(
						'flex_justify_content' => 'space-between',
						'flex_align_items'     => 'flex-end',
						'flex_wrap'            => 'wrap',
						'flex_gap'             => Builder::gap( 24 ),
					)
				),
				$this->showcase(),
			),
			'page',
			array( 'flex_gap' => Builder::gap( 'clamp(40px, 6vw, 96px)', null, 'custom' ) )
		);
	}

	/**
	 * The Loop Grid: twelve columns, the five featured projects picked by id and ordered by their Order field.
	 * Alternate templates give each position its template and column span; on tablet the grid has two columns and
	 * on mobile one, and `column_span` is capped to the grid's columns.
	 */
	private function showcase(): array {
		$alternate = array(
			$this->alternate( 'project-card-wide', 1, 12 ),
			$this->alternate( 'project-card', 2, 5, 1 ),
			$this->alternate( 'project-card', 3, 4, 1 ),
			$this->alternate( 'project-card-wide', 4, 12 ),
			$this->alternate( 'project-card', 5, 5, 2 ),
		);

		return Builder::widget(
			'loop-grid',
			array(
				'template_id'          => (string) Templates::id( 'project-card' ),
				'columns'              => '12',
				'columns_tablet'       => '2',
				'columns_mobile'       => '1',
				'posts_per_page'       => 5,
				'post_query_post_type' => 'by_id',
				'post_query_posts_ids' => $this->featured_ids(),
				'post_query_orderby'   => 'menu_order',
				'post_query_order'     => 'asc',
				'alternate_template'   => 'yes',
				'alternate_templates'  => $alternate,
				'column_gap'           => Builder::size( 'var(--forma-gutter)', 'custom' ),
				'row_gap'              => Builder::size( 'clamp(48px, 8vw, 120px)', 'custom' ),
				'custom_css'           => <<<'CSS'
				/*
				 * From 1024px the pair is staggered with open space between (5 + 4 of 12 columns, the second dropped),
				 * and the last project is pushed to the right. Loop items are the grid's divs; Pro prints a style
				 * element before each template's first use, so count by type.
				 */
				@media (min-width: 1024px) {
					selector .elementor-loop-container > div:nth-of-type(2) {
						grid-column: 1 / span 5;
					}
					selector .elementor-loop-container > div:nth-of-type(3) {
						grid-column: 8 / span 4;
						margin-top: 22vh;
					}
					selector .elementor-loop-container > div:nth-of-type(5) {
						grid-column: 7 / span 5;
					}
				}
				CSS,
			)
		);
	}

	/**
	 * One Loop Grid alternate template: the component shown at a grid position, once, spanning some columns.
	 *
	 * @param string   $component Component key.
	 * @param int      $position  1-based place in the grid.
	 * @param int      $span      Columns spanned on desktop.
	 * @param int|null $tablet    Columns spanned on tablet, when it differs from the desktop span.
	 */
	private function alternate( string $component, int $position, int $span, ?int $tablet = null ): array {
		$item = array(
			'_id'             => Builder::id(),
			'template_id'     => (string) Templates::id( $component ),
			'repeat_template' => $position,
			'show_once'       => 'yes',
			'column_span'     => (string) $span,
		);

		if ( null !== $tablet ) {
			$item['column_span_tablet'] = (string) $tablet;
		}

		return $item;
	}

	/**
	 * The featured projects' ids, in showcase order.
	 *
	 * @return int[]
	 */
	private function featured_ids(): array {
		$featured = array_filter( $this->projects, static fn( array $project ): bool => $project['featured'] > 0 );

		usort( $featured, static fn( array $a, array $b ): int => $a['featured'] <=> $b['featured'] );

		return array_values( array_filter( array_map( fn( array $project ): int => $this->content->project_id( $project['slug'] ), $featured ) ) );
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 4. Marquee.
	// ---------------------------------------------------------------------------------------------------------------

	/** The studio's three disciplines in large type, looping between two hairlines. */
	private function marquee(): array {
		$items = array();

		foreach ( array( 'Architecture', 'Interiors', 'Objects' ) as $text ) {
			$items[] = array(
				'_id'  => Builder::id(),
				'text' => $text,
			);
		}

		return Builder::container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => Builder::gap( 0 ),
				'padding'        => Builder::box( 'clamp(20px, 2.5vw, 40px)', 0, 'clamp(20px, 2.5vw, 40px)', 0, 'custom' ),
				'border_border'  => 'solid',
				'border_width'   => Builder::box( 1, 0, 1, 0 ),
				'__globals__'    => array( 'border_color' => Style::color( 'line' ) ),
			),
			array(
				Builder::widget(
					'forma-marquee',
					array(
						'items'       => $items,
						'separator'   => '—',
						'speed'       => 40,
						'__globals__' => array(
							'typography_typography' => Style::font( 'heading' ),
							'text_color'            => Style::color( 'ink' ),
							'separator_color'       => Style::color( 'accent' ),
						),
					)
				),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 5. The making of a space.
	// ---------------------------------------------------------------------------------------------------------------

	/** Six moments, told by a horizontal Scroll Story that pins on wide screens, on the Raised surface. */
	private function process(): array {
		$panels = array(
			'home-02.jpg' => array( 'Survey', 'We walk the site with a level and a notebook before anyone draws a line.' ),
			'home-03.jpg' => array( 'Sketch', 'Ideas start by hand, fast and loose, at the scale of a fingertip.' ),
			'home-04.jpg' => array( 'Model', 'Card and foam models test light, mass and the way in.' ),
			'home-05.jpg' => array( 'Drawing', 'Plans, sections and details: every decision written down.' ),
			'home-06.jpg' => array( 'Site', 'We are on site every week, from foundations to the last coat of lime.' ),
			'home-07.jpg' => array( 'Light', 'The moment we work towards: the first morning the sun comes in.' ),
		);

		$items = array();

		foreach ( $panels as $file => [ $title, $text ] ) {
			$items[] = array(
				'_id'   => Builder::id(),
				'image' => Builder::image( Images::attachment_id( $file ) ),
				'title' => $title,
				'text'  => $text,
			);
		}

		return Style::section(
			array(
				$this->intro(
					'Process',
					'The making of a space',
					array(
						'padding' => Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' ),
					),
					Style::text(
						'<p>Six moments every FORMA project passes through, from the first survey to the first morning light.</p>',
						'body',
						'muted',
						array( 'custom_css' => 'selector { max-width: 52ch; }' )
					)
				),
				Builder::widget(
					'forma-scroll-story',
					array(
						'layout'    => 'horizontal',
						'surface'   => 'raised',
						'panels'    => $items,
						'fm_cursor' => 'Scroll',
					)
				),
			),
			'raised',
			array(
				// Full bleed: the story runs edge to edge, so the section has no side padding.
				'padding'  => Builder::box( 'var(--forma-section)', 0, 'var(--forma-section)', 0, 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(40px, 5vw, 80px)', null, 'custom' ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 6. Practice.
	// ---------------------------------------------------------------------------------------------------------------

	/** The six services as a numbered typographic list; each row links to its place on the Services page. */
	private function practice(): array {
		$services = $this->permalink( 'services' );
		$rows     = array();
		$index    = 0;

		foreach ( $this->site['services'] as $slug => $service ) {
			$rows[] = $this->service_row( $index, $service, "{$services}#{$slug}" );
			++$index;
		}

		return Style::section(
			array(
				$this->intro( 'Practice', 'Six disciplines, one studio' ),
				Style::stack(
					$rows,
					array(
						'border_border' => 'solid',
						'border_width'  => Builder::box( 0, 0, 1, 0 ),
						'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
					)
				),
			),
			'page',
			array(
				'flex_gap'   => Builder::gap( 'clamp(32px, 5vw, 72px)', null, 'custom' ),
				'custom_css' => <<<'CSS'
				/* A row nudges in on hover or focus. */
				selector .forma-service {
					transition: padding-inline-start 0.6s var(--forma-ease);
				}
				selector .forma-service:hover,
				selector .forma-service:focus-visible {
					padding-inline-start: 16px;
				}
				CSS,
			)
		);
	}

	/**
	 * @param array{name:string,line:string} $service
	 */
	private function service_row( int $index, array $service, string $url ): array {
		return Style::row(
			array(
				Style::cell( array( Style::label( sprintf( '%02d', $index + 1 ), 'accent' ) ), 8, 10 ),
				Style::cell( array( Style::serif( $service['name'], 'clamp(28px, 3vw, 48px)', 'h3' ) ), 40, 40 ),
				Style::cell(
					array(
						Style::text(
							'<p>' . esc_html( $service['line'] ) . '</p>',
							'body',
							'muted',
							array( 'custom_css' => 'selector { max-width: 44ch; }' )
						),
					),
					40,
					40
				),
			),
			array(
				'html_tag'                => 'a',
				'link'                    => Style::link( $url ),
				'css_classes'             => 'forma-service',
				'flex_wrap'               => 'nowrap',
				'flex_direction_mobile'   => 'column',
				'flex_align_items'        => 'center',
				'flex_align_items_mobile' => 'stretch',
				'flex_gap'                => Builder::gap( 8, 24 ),
				'padding'                 => Builder::box( 28, 0, 28, 0 ),
				'border_border'           => 'solid',
				'border_width'            => Builder::box( 1, 0, 0, 0 ),
				'fm_entrance'             => 'fade-up',
				'fm_delay'                => round( 0.06 * $index, 2 ),
				'__globals__'             => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 7. Index.
	// ---------------------------------------------------------------------------------------------------------------

	/** Every project in a register, with its image following the pointer (Forma Project Index defaults). */
	private function index(): array {
		return Style::section(
			array(
				$this->intro( 'Index', 'Every project since 2019' ),
				Builder::widget( 'forma-project-index', array() ),
			),
			'page',
			array( 'flex_gap' => Builder::gap( 'clamp(32px, 5vw, 72px)', null, 'custom' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 9. Quote.
	// ---------------------------------------------------------------------------------------------------------------

	/** A client's words in the Statement face, set in italic, with the speaker below. */
	private function quote(): array {
		return Style::section(
			array(
				Style::cell(
					array(
						Style::text(
							'<p>“Guests tell us the first thing they notice is the silence.”</p>',
							'statement',
							'ink',
							array(
								'fm_entrance' => 'words',
								// The Statement global sets the face and size; the italic cut is added here.
								'custom_css'  => 'selector { font-style: italic; }',
							)
						),
						Style::label( 'Kenji Watanabe, General Manager, The Quiet Hotel' ),
					),
					70,
					100,
					100,
					array( 'flex_gap' => Builder::gap( 24 ) )
				),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Shared.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * A saved component shown through the Template widget. The widget cannot sit at the top level, so it gets a plain
	 * full-width wrapper with no padding, and the component's own band fills it.
	 */
	private function component( string $key ): array {
		return Builder::container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => Builder::gap( 0 ),
				'padding'        => Builder::box( 0 ),
			),
			array(
				Builder::widget( 'template', array( 'template_id' => (string) Templates::id( $key ) ) ),
			)
		);
	}

	/**
	 * The opening of a section: a label, an H2 and optionally a line of text.
	 *
	 * @param array      $extra Settings for the wrapping column.
	 * @param array|null $text  A text widget placed under the heading.
	 */
	private function intro( string $label, string $title, array $extra = array(), ?array $text = null ): array {
		$children = array( Style::label( $label ), Style::heading( $title, 'heading', 'h2' ) );

		if ( $text ) {
			$children[] = $text;
		}

		return Style::cell( $children, 'auto', 'auto', 100, array( 'flex_gap' => Builder::gap( 16 ) ) + $extra );
	}

	/** A page's permalink, or its conventional path before it has been seeded. */
	private function permalink( string $slug ): string {
		$id = $this->content->page_id( $slug );

		return $id ? (string) get_permalink( $id ) : home_url( "/{$slug}/" );
	}
}
