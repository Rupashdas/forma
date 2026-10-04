<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;
use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Drawings;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * The Home page (spec 7.1), built from Elementor containers and widgets plus the saved components: the hero, the
 * statement, selected work, the making of a space, practice, the index, recognition, a quote and the closing call to
 * action. Sections sit on Chalk, broken by Raised and bottle green (deep) bands; every colour and type style is a Kit
 * global. Content is boxed to the Kit's 1320px; only the Scroll Story runs the full width of its band.
 *
 * Motion is budgeted: the hero (wordmark and the drawing-to-photo wipe), the Scroll Story pin and the Project Index
 * preview. Everything else is still.
 */
final class Home {

	public const KEY = 'home';

	/** The saved components Home shows, which must exist before it is built. */
	private const COMPONENTS = array( 'project-card', 'project-card-wide', 'recognition', 'closing-cta' );

	/**
	 * Where the Scroll Story's panels start: the left edge of the 1320px box, or the gutter on narrower screens. The
	 * percentage is the width of the story's own band, so a classic scrollbar does not shift it (as `100vw` would).
	 */
	private const BOX_OFFSET = 'max(var(--forma-gutter), calc((100% - 1320px) / 2))';

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

		$elements = $this->elements();

		if ( Builder::save( $id, $elements, $this->settings() ) ) {
			( $this->log )( sprintf( 'Home: %d sections (#%d).', count( $elements ), $id ) );
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
	 * The wordmark, a line about the studio with its place and coordinates, and the one memorable moment: a drawing of
	 * Casa Nera that the photograph of the finished house wipes across. The section is boxed and sits on the page.
	 */
	private function hero(): array {
		return Style::section(
			array(
				Style::heading( 'FORMA', 'display-xl', 'h1', 'ink', array( 'fm_entrance' => 'chars' ) ),
				$this->hero_intro(),
				$this->hero_figure(),
			),
			'page',
			array(
				// The header is fixed over the page, so the top padding clears it.
				'padding'  => Builder::box( 'clamp(112px, 14vw, 168px)', 'var(--forma-gutter)', 'clamp(24px, 3vw, 48px)', 'var(--forma-gutter)', 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
			)
		);
	}

	/**
	 * Two columns from tablet up: the studio in one sentence on the left (about 7 of 12 columns), the place and
	 * coordinates at the bottom right. They stack on mobile.
	 */
	private function hero_intro(): array {
		$right = array( 'align' => 'right', 'align_mobile' => 'left' );

		return Style::row(
			array(
				Style::cell(
					array(
						Style::text(
							'<p>An architecture and interiors studio in Lisbon, designing houses, hotels, workplaces and the objects inside them.</p>',
							'subheading',
							'ink',
							$this->measure( 36 )
						),
					),
					58.33,
					58.33,
					100
				),
				Style::cell(
					array(
						Style::heading( 'Lisbon, since ' . $this->site['studio']['founded'], 'meta', 'p', 'muted', $right ),
						Style::heading( $this->site['studio']['coordinates'], 'meta', 'p', 'muted', $right ),
					),
					'auto',
					'auto',
					100,
					array( 'flex_gap' => Builder::gap( 2 ) )
				),
			),
			array(
				'flex_wrap'               => 'nowrap',
				'flex_justify_content'    => 'space-between',
				'flex_align_items'        => 'flex-end',
				'flex_direction_mobile'   => 'column',
				'flex_align_items_mobile' => 'stretch',
				'flex_gap'                => Builder::gap( 16, 24 ),
			)
		);
	}

	/** The frame and the caption under it. */
	private function hero_figure(): array {
		return Style::stack(
			array(
				$this->hero_frame(),
				Style::row(
					array(
						Style::heading( 'Casa Nera, Comporta. Drawn in 2022, built in 2024.', 'meta', 'p', 'muted' ),
						$this->text_link( 'See the project', $this->project_url( 'casa-nera' ) ),
					),
					array(
						'flex_justify_content' => 'space-between',
						'flex_align_items'     => 'flex-start',
						'flex_wrap'            => 'wrap',
						'flex_gap'             => Builder::gap( 8, 24 ),
					)
				),
			),
			array( 'flex_gap' => Builder::gap( 14 ) )
		);
	}

	/**
	 * A 16:9 frame (4:5 on mobile) holding two layers that fill it: the line drawing of Casa Nera underneath, and the
	 * photograph on top. The photograph wipes in from the left after the wordmark, so the drawing shows first and the
	 * house is built across it. The clip-left entrance reveals the photograph's wrapper left to right and leaves the
	 * drawing as it is, visible under the part not yet revealed.
	 *
	 * Both layers are absolutely positioned so the frame, not either image, sets the size. The photograph is the
	 * page's largest image and loads eagerly (see the Forma Engine's Elementor module).
	 */
	private function hero_frame(): array {
		return Style::stack(
			array(
				Builder::widget(
					'image',
					array(
						'image'        => Builder::image( Drawings::attachment_id( 'casa-nera' ) ),
						'image_size'   => 'full',
						'_css_classes' => 'forma-hero__layer forma-hero__drawing',
					)
				),
				Builder::widget(
					'image',
					array(
						'image'        => Builder::image( Images::attachment_id( 'casa-nera-01.jpg' ) ),
						'image_size'   => 'full',
						'_css_classes' => 'forma-hero__layer forma-hero__photo',
						'fm_entrance'  => 'clip-left',
						'fm_delay'     => 0.9,
					)
				),
			),
			array(
				'overflow'    => 'hidden',
				'css_classes' => 'forma-hero__frame',
				'custom_css'  => <<<'CSS'
				selector {
					position: relative;
					aspect-ratio: 16 / 9;
				}
				@media (max-width: 767px) {
					selector {
						aspect-ratio: 4 / 5;
					}
				}
				selector .forma-hero__layer {
					position: absolute;
					inset: 0;
					width: 100%;
					height: 100%;
				}
				selector .forma-hero__layer .elementor-widget-container {
					height: 100%;
				}
				selector .forma-hero__layer img {
					display: block;
					width: 100%;
					height: 100%;
					object-fit: cover;
				}
				CSS,
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. Statement.
	// ---------------------------------------------------------------------------------------------------------------

	/** One large paragraph on the bottle green, then the way on to the Studio page. */
	private function statement(): array {
		return Style::section(
			array(
				Style::text(
					'<p>FORMA is a studio of twenty-three architects, interior designers and makers in Lisbon. We design houses, hotels, workplaces and the objects inside them, for the slow parts of life. Every project starts on site, with a level and a notebook, long before the first drawing.</p>',
					'statement',
					'ink',
					$this->measure( 30 )
				),
				Style::button( 'About the studio', $this->permalink( 'studio' ) ),
			),
			'deep',
			array( 'flex_gap' => Builder::gap( 'clamp(32px, 4vw, 56px)', null, 'custom' ) )
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
				$this->section_head( 'Selected work', $this->text_link( 'All eleven projects', $archive ? (string) $archive : home_url( '/projects/' ) ) ),
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
				 * From 1024px the pair is staggered with open space between (columns 1-5 and 8-11 of 12, the second
				 * dropped), and the last project is pushed to the right (columns 7-11). Loop items are the grid's divs;
				 * Pro prints a style element before each template's first use, so count by type.
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
	// 4. The making of a space.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Six moments, told by a horizontal Scroll Story that pins on wide screens, on the Raised surface. The band is full
	 * width: its heading sits in the 1320px box, and the story runs to the right edge of the screen with its first panel
	 * starting at the box's left edge. The panels keep their numbers: this one is a sequence.
	 */
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

		$offset = self::BOX_OFFSET;

		return Style::section(
			array(
				// A boxed container inside the full-width band: the gutter is its padding, the 1320px box is its content.
				Style::stack(
					array(
						Style::heading( 'The making of a space', 'heading', 'h2' ),
						Style::text(
							'<p>Six moments every FORMA project passes through, from the first survey to the first morning light.</p>',
							'body',
							'muted',
							$this->measure( 52 )
						),
					),
					array(
						'content_width' => 'boxed',
						'flex_gap'      => Builder::gap( 16 ),
						'padding'       => Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' ),
					)
				),
				Builder::widget(
					'forma-scroll-story',
					array(
						'layout'     => 'horizontal',
						'surface'    => 'raised',
						'panels'     => $items,
						'fm_cursor'  => 'Scroll',
						'custom_css' => <<<CSS
						/*
						 * The story's own padding is the gutter. Here it is the offset of the 1320px box, so the first panel
						 * lines up with the heading above and the right side bleeds off the screen. Pinned (is-horizontal)
						 * the offset moves to the panel track and the progress rail; unpinned (a vertical list) the story
						 * is centred in the box.
						 */
						selector .forma-story:not(.is-horizontal) {
							padding-inline: {$offset};
						}
						selector .forma-story.is-horizontal .forma-story__panels {
							padding-left: {$offset};
						}
						selector .forma-story.is-horizontal .forma-story__rail {
							left: {$offset};
						}
						CSS,
					)
				),
			),
			'raised',
			array(
				// The story runs edge to edge, so the band has no side padding of its own.
				'padding'  => Builder::box( 'var(--forma-section)', 0, 'var(--forma-section)', 0, 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(40px, 5vw, 80px)', null, 'custom' ),
			),
			'full'
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 5. Practice.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Two columns from 1024px: a heading and one line on the left (4 of 12 columns), the six services on the right
	 * (8 of 12), each a full-row link to its place on the Services page. One column below that.
	 */
	private function practice(): array {
		$services = $this->permalink( 'services' );
		$rows     = array();

		foreach ( $this->site['services'] as $slug => $service ) {
			$rows[] = $this->service_row( $service, "{$services}#{$slug}" );
		}

		return Style::section(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( 'What we do', 'heading', 'h2' ),
								Style::text(
									'<p>Six disciplines, one studio. Most projects use three or four of them.</p>',
									'body',
									'muted',
									$this->measure( 32 )
								),
							),
							33.333,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 16 ) )
						),
						Style::cell(
							array(
								Style::stack(
									$rows,
									array(
										'border_border' => 'solid',
										'border_width'  => Builder::box( 0, 0, 1, 0 ),
										'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
									)
								),
							),
							66.667,
							100,
							100
						),
					),
					array(
						// Both columns shrink in proportion to their width, so the gap leaves exactly 4 and 8 of 12 columns.
						'flex_wrap'             => 'nowrap',
						'flex_direction_tablet' => 'column',
						'flex_gap'              => Builder::gap( 'clamp(32px, 5vw, 72px)', null, 'custom' ),
					)
				),
			),
			'page',
			array(
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
	 * A service as one hairline row: the name in the display face, and its line in the right half from tablet up (below
	 * the name on mobile).
	 *
	 * @param array{name:string,line:string} $service
	 */
	private function service_row( array $service, string $url ): array {
		return Style::row(
			array(
				Style::cell( array( Style::serif( $service['name'], 'clamp(26px, 2.6vw, 40px)', 'h3' ) ), 50, 50, 100 ),
				Style::cell(
					array( Style::text( '<p>' . esc_html( $service['line'] ) . '</p>', 'body', 'muted' ) ),
					50,
					50,
					100
				),
			),
			array(
				'html_tag'                => 'a',
				'link'                    => Style::link( $url ),
				'css_classes'             => 'forma-service',
				'flex_wrap'               => 'nowrap',
				'flex_direction_mobile'   => 'column',
				'flex_align_items'        => 'flex-start',
				'flex_align_items_mobile' => 'stretch',
				'flex_gap'                => Builder::gap( 8, 24 ),
				'padding'                 => Builder::box( 24, 0, 24, 0 ),
				'border_border'           => 'solid',
				'border_width'            => Builder::box( 1, 0, 0, 0 ),
				'__globals__'             => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 6. Index.
	// ---------------------------------------------------------------------------------------------------------------

	/** Every project in a register, with its image following the pointer (Forma Project Index defaults). */
	private function index(): array {
		return Style::section(
			array(
				$this->section_head( 'All projects', Style::heading( '2019–2026', 'meta', 'p', 'muted' ) ),
				Builder::widget( 'forma-project-index', array() ),
			),
			'page',
			array( 'flex_gap' => Builder::gap( 'clamp(32px, 5vw, 72px)', null, 'custom' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 7. Quote.
	// ---------------------------------------------------------------------------------------------------------------

	/** A client's words in the Statement face, all of it in italic, with the speaker below. */
	private function quote(): array {
		return Style::section(
			array(
				Style::text(
					'<p>“Guests tell us the first thing they notice is the silence.”</p>',
					'statement',
					'ink',
					array(
						// The Statement global sets the face and size; the italic cut is added here, for the whole quote.
						'custom_css' => 'selector, selector p { font-style: italic; } selector p { max-width: 28ch; }',
					)
				),
				Style::heading( 'Kenji Watanabe, General Manager, The Quiet Hotel', 'meta', 'p', 'muted' ),
			),
			'page',
			array( 'flex_gap' => Builder::gap( 24 ) )
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
	 * The opening of a section: its H2 on the left and something small on the right, on one baseline. The aside drops
	 * under the heading when the row is too narrow for both.
	 */
	private function section_head( string $title, array $aside ): array {
		return Style::row(
			array( Style::heading( $title, 'heading', 'h2' ), $aside ),
			array(
				'flex_justify_content' => 'space-between',
				'flex_wrap'            => 'wrap',
				'flex_gap'             => Builder::gap( 12, 24 ),
				// The control offers no baseline, so the alignment is set in the row's Custom CSS.
				'custom_css'           => 'selector { align-items: baseline; }',
			)
		);
	}

	/**
	 * A line length for a text editor widget, in characters of its own face. It goes on the paragraph: Elementor sets
	 * `max-width: 100%` on every widget in a container with a rule that outranks a Custom CSS `selector`.
	 */
	private function measure( int $ch ): array {
		return array( 'custom_css' => "selector p {\n\tmax-width: {$ch}ch;\n}" );
	}

	/** An underlined link in the Label type, on its own: a text link rather than a button. */
	private function text_link( string $label, string $url ): array {
		return Style::heading(
			$label,
			'label',
			'p',
			'ink',
			array(
				'link'       => Style::link( $url ),
				'custom_css' => <<<'CSS'
				selector .elementor-heading-title a {
					text-decoration: underline;
					text-decoration-thickness: 1px;
					text-underline-offset: 0.3em;
					transition: color 0.4s var(--forma-ease);
				}
				selector .elementor-heading-title a:hover,
				selector .elementor-heading-title a:focus-visible {
					color: var(--forma-accent);
				}
				CSS,
			)
		);
	}

	/** A page's permalink, or its conventional path before it has been seeded. */
	private function permalink( string $slug ): string {
		$id = $this->content->page_id( $slug );

		return $id ? (string) get_permalink( $id ) : home_url( "/{$slug}/" );
	}

	/** A project's permalink, or its conventional path before it has been seeded. */
	private function project_url( string $slug ): string {
		$id = $this->content->project_id( $slug );

		return $id ? (string) get_permalink( $id ) : home_url( "/projects/{$slug}/" );
	}
}
