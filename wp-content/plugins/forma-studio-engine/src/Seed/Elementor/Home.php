<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\ProjectNumber;
use Forma\Engine\Projects\Projects;
use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * The Home page (spec 5.1): one study model, shown seven ways, on rounded panels that float on Paper.
 *
 * 1. Hero: an inset Panel with the copy on the left and Casa Nera, assembling, with its named parts called out, on the
 *    right.
 * 2. Model tour: a sticky Panel stage inside a 300vh section; three glass notes scroll over it while the model turns
 *    and comes apart.
 * 3. Selected work: an inset Ink band that pins while a strip of five Ink tiles slides sideways; the model at its left
 *    swaps to the project in front.
 * 4. Practice: the six services in giant type, a project photo following the cursor.
 * 5. Index: every project in a list, with a sticky Panel stage whose model swaps to the hovered row.
 * 6. Recognition: the awards as a slow ticker in a Panel band.
 * 7. Closing call to action: a saved component, the empty plot as a model.
 *
 * Colours and type styles are Kit globals; sections inside the Ink band use the same tokens and the theme re-points
 * them. Layouts that need positioning (the hero, the sticky stages, the strip) say so in their Custom CSS.
 */
final class Home {

	public const KEY = 'home';

	/** The saved components Home shows, which must exist before it is built. */
	private const COMPONENTS = array( 'project-card', 'closing-cta' );

	/** What a service's name shows under the cursor on hover: a photograph of a project that fits it. */
	private const SERVICE_PHOTOS = array(
		'architecture'      => 'casa-nera-01.jpg',
		'interior-design'   => 'casa-nera-04.jpg',
		'hospitality'       => 'the-quiet-hotel-01.jpg',
		'workplace'         => 'atelier-27-01.jpg',
		'art-direction'     => 'monolith-house-01.jpg',
		'furniture-objects' => 'plinth-series-01.jpg',
	);

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
			$this->tour(),
			$this->selected_work(),
			$this->practice(),
			$this->index(),
			$this->recognition(),
			$this->component( 'closing-cta' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Hero.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Panel about 92vh tall whose content sits in the boxed 1320px column, so on a wide screen it lines up with
	 * the header's pills. From 1024px the copy sits at the left of the column (42%) and the model fills the right 58% of
	 * it, top to bottom of the panel; the caption sits at the bottom left. Below that everything stacks: copy, model,
	 * caption. The header floats over the panel, so the copy's top padding clears it (the panel itself has none, which
	 * lets the model run the panel's full height).
	 */
	private function hero(): array {
		$casa = $this->content->project_id( 'casa-nera' );

		return Style::section(
			array(
				$this->hero_copy(),
				$this->hero_model( $casa ),
				$this->hero_caption( $casa ),
			),
			'raised',
			array(
				'min_height'           => Builder::size( 92, 'vh' ),
				'min_height_tablet'    => Builder::size( 0 ),
				'flex_justify_content' => 'center',
				'padding'              => Builder::box( 0, Style::PANEL_GUTTER, 0, Style::PANEL_GUTTER, 'custom' ),
				'css_classes'          => 'forma-hero',
				// The boxed column is the positioning context of the model and the caption.
				'custom_css'           => 'selector > .e-con-inner { position: relative; }',
			)
		);
	}

	/** The one H1, a line about the studio, and the way on: an Ink pill to the projects and a link to the process. */
	private function hero_copy(): array {
		$archive = get_post_type_archive_link( Projects::POST_TYPE );

		return Style::stack(
			array(
				Style::heading( 'Buildings, first as models.', 'display-xxl', 'h1', 'ink', array( 'fm_entrance' => 'lines' ) ),
				Style::text(
					'<p>FORMA is an architecture and interiors studio in Lisbon. Every house, hotel and workplace starts as a model you can turn in your hands.</p>',
					'body',
					'muted',
					$this->measure( 38 )
				),
				Style::row(
					array(
						Style::button( 'View projects', $archive ? (string) $archive : home_url( '/projects/' ) ),
						Style::text_link( 'How we work', $this->permalink( 'process' ) ),
					),
					array(
						'flex_wrap'        => 'wrap',
						'flex_align_items' => 'center',
						'flex_gap'         => Builder::gap( 16, 28 ),
					)
				),
			),
			array(
				'width'          => Builder::size( 42, '%' ),
				'width_tablet'   => Builder::size( 100, '%' ),
				'width_mobile'   => Builder::size( 100, '%' ),
				'flex_gap'       => Builder::gap( 'clamp(18px, 2.2vw, 28px)', null, 'custom' ),
				'padding'        => Builder::box( 'clamp(104px, 11vw, 150px)', 0, 'clamp(72px, 8vw, 110px)', 0, 'custom' ),
				'padding_tablet' => Builder::box( 104, 0, 24, 0 ),
				'css_classes'    => 'forma-hero__copy',
			)
		);
	}

	/**
	 * Casa Nera, assembling, with its parts called out. On a wide screen the widget is absolute and fills the right of
	 * the boxed column (its view is 100% of that box); below 1024px it is a block 56vh (52vh on mobile) tall in the flow.
	 */
	private function hero_model( int $project ): array {
		return Builder::widget(
			'forma-study-model',
			array(
				'source'             => 'project',
				'project'            => (string) $project,
				'camera'             => 'hero',
				'view_height'        => Builder::size( '100%', 'custom' ),
				'view_height_tablet' => Builder::size( 56, 'vh' ),
				'view_height_mobile' => Builder::size( 52, 'vh' ),
				'assemble'           => 'yes',
				'drag'               => 'yes',
				'callouts'           => 'yes',
				'custom_css'         => <<<'CSS'
				@media (min-width: 1024px) {
					selector {
						position: absolute;
						top: 0;
						right: 0;
						bottom: 0;
						width: 58%;
					}
				}
				CSS,
			)
		);
	}

	/** The chip with the project's number, and where and when it was built. */
	private function hero_caption( int $project ): array {
		$data     = $this->project_data( 'casa-nera' );
		$place    = trim( (string) strtok( (string) $data['location'], ',' ) );
		$year     = substr( (string) $data['date'], 0, 4 );
		$number   = $project ? ProjectNumber::for_post( $project ) : '';
		$children = array();

		if ( '' !== $number ) {
			$children[] = Style::chip( 'No. ' . $number );
		}

		$children[] = Style::heading( "{$data['title']}, {$place}, {$year}", 'meta', 'p', 'muted' );

		return Style::row(
			$children,
			array(
				'flex_wrap'        => 'nowrap',
				'flex_align_items' => 'center',
				'flex_gap'         => Builder::gap( 10 ),
				'padding_tablet'   => Builder::box( 16, 0, 28, 0 ),
				'css_classes'      => 'forma-hero__caption',
				'custom_css'       => <<<'CSS'
				@media (min-width: 1024px) {
					selector {
						position: absolute;
						left: 0;
						bottom: var(--forma-gutter);
						width: auto;
					}
				}
				CSS,
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. Model tour.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * A section as tall as three screens. Its first child is the stage, a Panel pinned to the viewport with `sticky`
	 * (100vh less the inset); the notes come second and are pulled back up by 100vh, so they scroll over the stage. The
	 * model follows the section: it turns three quarters of a circle and comes apart into its named parts, and drag is
	 * off so the page keeps the wheel and the touch. From 1024px the model fills the right 62% of the stage and the
	 * notes sit on the left; below that the notes sit at the bottom of each screen, over the model.
	 */
	private function tour(): array {
		$casa  = $this->content->project_id( 'casa-nera' );
		$notes = array(
			array( 'Site first.', 'We walk the plot with a level and a notebook before anyone draws a line.' ),
			array( 'Models before drawings.', 'Every building starts as blocks of foam on a table, moved until the light falls right.' ),
			array( 'Built to last.', 'Lime, timber and stone, detailed to age well rather than stay new.' ),
		);

		$cards = array();

		foreach ( $notes as [ $title, $text ] ) {
			$cards[] = $this->tour_note( $title, $text );
		}

		return Style::section(
			array(
				Style::stack(
					array(
						Builder::widget(
							'forma-study-model',
							array(
								'source'             => 'project',
								'project'            => (string) $casa,
								'camera'             => 'three-quarter',
								'view_height'        => Builder::size( 'calc(100vh - 2 * var(--forma-inset))', 'custom' ),
								'assemble'           => '',
								'drag'               => '',
								'callouts'           => 'yes',
								'scroll_orbit'       => 'yes',
								'scroll_explode'     => 'yes',
								'scroll_trigger'     => 'section',
								'custom_css'         => <<<'CSS'
								@media (min-width: 1024px) {
									selector.elementor-widget {
										width: 62%;
										align-self: flex-end;
									}
								}
								CSS,
							)
						),
					),
					array(
						// Boxed: the model sits in the 1320px column, the panel behind it runs the width of the page.
						'content_width'         => 'boxed',
						'padding'               => Builder::box( 0, Style::PANEL_GUTTER, 0, Style::PANEL_GUTTER, 'custom' ),
						'css_classes'           => 'forma-panel forma-tour__stage',
						'background_background' => 'classic',
						'__globals__'           => array( 'background_color' => Style::color( 'raised' ) ),
						'custom_css'            => <<<'CSS'
						selector {
							position: sticky;
							top: var(--forma-inset);
							z-index: 1;
							flex: none;
							height: calc(100vh - 2 * var(--forma-inset));
						}
						CSS,
					)
				),
				Style::stack(
					$cards,
					array(
						// Boxed too, with the page gutter (the stage's inset plus its padding), so the cards sit on the column's left edge.
						'content_width' => 'boxed',
						'css_classes'   => 'forma-tour__notes',
						'padding'       => Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' ),
						'custom_css'  => <<<'CSS'
						/* The notes start where the stage does and scroll over it; only the cards take the pointer. */
						selector {
							position: relative;
							z-index: 2;
							margin-top: -100vh;
							pointer-events: none;
						}
						selector .forma-tour__card {
							pointer-events: auto;
						}
						CSS,
					)
				),
			),
			'page',
			array(
				'padding'     => Builder::box( 0 ),
				'min_height'  => Builder::size( 300, 'vh' ),
				'css_classes' => 'forma-tour',
			),
			'full'
		);
	}

	/**
	 * One note: a screen-tall slot with a glass card (Paper at 70% over a blur) at its left, or at its bottom on small
	 * screens, holding a Subheading and a Body line.
	 */
	private function tour_note( string $title, string $text ): array {
		return Style::stack(
			array(
				Style::stack(
					array(
						Style::heading( $title, 'subheading', 'h3' ),
						Style::text( '<p>' . esc_html( $text ) . '</p>', 'body', 'muted' ),
					),
					array(
						'flex_gap'      => Builder::gap( 8 ),
						'padding'       => Builder::box( 22, 24, 24, 24 ),
						'border_radius' => Builder::box( 'var(--forma-r-tile)', null, null, null, 'custom' ),
						'css_classes'   => 'forma-tour__card',
						'custom_css'    => <<<'CSS'
						selector {
							max-width: 380px;
							background-color: rgba(246, 245, 241, 0.7);
							background-color: color-mix(in srgb, var(--forma-page) 70%, transparent);
							-webkit-backdrop-filter: blur(12px);
							backdrop-filter: blur(12px);
							box-shadow: inset 0 0 0 1px var(--forma-line);
						}
						CSS,
					)
				),
			),
			array(
				'min_height'                => Builder::size( 100, 'vh' ),
				'flex_justify_content'      => 'center',
				'flex_justify_content_tablet' => 'flex-end',
				'flex_align_items'          => 'flex-start',
				'padding_tablet'            => Builder::box( 0, 0, 24, 0 ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 3. Selected work.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Ink band, a screen tall. From 1024px it pins while the strip of five Ink tiles slides sideways (Forma
	 * Motion "Horizontal scroll" on the Loop Grid): the model at its left, a study model that swaps by scroll, takes on
	 * the project in front. The heading, the model and the strip's first card sit in the boxed 1320px column; only the
	 * strip's track runs on past it and off the band's right edge. Below 1024px the model sits above a swipe row.
	 */
	private function selected_work(): array {
		$archive  = get_post_type_archive_link( Projects::POST_TYPE );
		$featured = $this->featured_ids();

		return Style::section(
			array(
				$this->section_head( 'Selected work', Style::text_link( 'All eleven projects', $archive ? (string) $archive : home_url( '/projects/' ) ) ),
				Style::row(
					array(
						Style::cell(
							array(
								Builder::widget(
									'forma-study-model',
									array(
										'source'             => 'project',
										'project'            => (string) ( $featured[0] ?? 0 ),
										'camera'             => 'three-quarter',
										'view_height'        => Builder::size( 'min(70vh, calc(100vh - 280px))', 'custom' ),
										'view_height_tablet' => Builder::size( 46, 'vh' ),
										'view_height_mobile' => Builder::size( 42, 'vh' ),
										'drag'               => 'yes',
										'swap'               => 'scroll',
									)
								),
							),
							38,
							100,
							100
						),
						Style::cell(
							array( $this->strip( $featured ) ),
							62,
							100,
							100,
							array(
								'css_classes' => 'forma-strip',
								'custom_css'  => <<<'CSS'
								/*
								 * The strip starts on the column's grid and bleeds off the band's right edge, past the boxed column and
								 * the band's padding (100cqw is the band's content width, see the band's Custom CSS); min-width lets
								 * it shrink to its column.
								 */
								selector {
									min-width: 0;
									margin-right: calc(-1 * (max(0px, (100cqw - 1320px) / 2) + var(--forma-gutter) - var(--forma-inset)));
								}
								/* Side by side the strip takes what is left of the row, so the negative margin widens it. */
								@media (min-width: 1024px) {
									selector {
										flex: 1 1 0;
										width: auto;
									}
								}
								CSS,
							)
						),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_align_items'        => 'center',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(20px, 3vw, 48px)', null, 'custom' ),
					)
				),
			),
			'deep',
			array(
				'flex_gap'             => Builder::gap( 'clamp(24px, 3vw, 40px)', null, 'custom' ),
				'flex_justify_content' => 'center',
				'padding'              => Builder::box( 'clamp(88px, 7vw, 96px)', Style::PANEL_GUTTER, 'clamp(32px, 3vw, 40px)', Style::PANEL_GUTTER, 'custom' ),
				'css_classes'          => 'forma-selected',
				'custom_css'           => <<<'CSS'
				/* The band is a size container for the strip's right bleed (cqw); from 1024px it is a screen tall. */
				selector {
					container-type: inline-size;
				}
				@media (min-width: 1024px) {
					selector {
						min-height: calc(100vh - 2 * var(--forma-inset));
					}
				}
				CSS,
			)
		);
	}

	/**
	 * The strip: the Loop Grid of the five featured projects (the project card, picked by id and ordered by their Order
	 * field), laid out as one row. Each card is as wide as the screen allows and as the band is tall, so a card fits
	 * the pinned band at any height; the swipe row on small screens uses narrower cards.
	 *
	 * @param int[] $ids Featured project ids, in order.
	 */
	private function strip( array $ids ): array {
		return Builder::widget(
			'loop-grid',
			array(
				'template_id'          => (string) Templates::id( 'project-card' ),
				'columns'              => '1',
				'posts_per_page'       => 5,
				'post_query_post_type' => 'by_id',
				'post_query_posts_ids' => $ids,
				'post_query_orderby'   => 'menu_order',
				'post_query_order'     => 'asc',
				'fm_scroll'            => 'hscroll',
				'custom_css'           => <<<'CSS'
				selector .elementor-loop-container {
					display: flex;
					flex-wrap: nowrap;
					width: max-content;
					gap: clamp(16px, 2vw, 32px);
					padding-inline-end: calc(var(--forma-gutter) * 2);
				}
				selector .elementor-loop-container > div {
					flex: none;
					width: min(78vw, 360px);
				}
				@media (min-width: 1024px) {
					selector .elementor-loop-container > div {
						width: clamp(300px, min(32vw, 80vh - 280px), 520px);
					}
				}
				CSS,
			)
		);
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
	// 4. Practice.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The six services as giant rows, each a link to its place on the Services page. The rows alternate: even rows are
	 * reversed, so the name and its line sit at the right, which gives the list an asymmetric rhythm. On hover a photo
	 * of a fitting project follows the cursor (`data-cursor-image`) and the other rows dim. Below 1024px every row is a
	 * left-aligned stack.
	 */
	private function practice(): array {
		$services = $this->permalink( 'services' );
		$rows     = array();
		$index    = 0;

		foreach ( $this->site['services'] as $slug => $service ) {
			$rows[] = $this->service_row( $slug, $service, "{$services}#{$slug}", 0 !== $index % 2 );
			++$index;
		}

		return Style::section(
			array(
				Style::stack(
					array(
						Style::heading( 'What we do', 'heading', 'h2' ),
						Style::text(
							'<p>Six disciplines, one studio. Most projects use three or four of them.</p>',
							'body',
							'muted',
							$this->measure( 40 )
						),
					),
					array( 'flex_gap' => Builder::gap( 12 ) )
				),
				Style::stack(
					$rows,
					array(
						'css_classes'   => 'forma-services',
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
				/*
				 * Where a pointer can hover, the row under it stays and the others step back. The row's own opacity belongs to
				 * its reveal, so it is the row's contents that dim.
				 */
				@media (hover: hover) and (pointer: fine) {
					selector .forma-service > .elementor-widget {
						transition: opacity 0.4s var(--forma-ease);
					}
					selector .forma-services:hover .forma-service > .elementor-widget {
						opacity: 0.32;
					}
					selector .forma-services:hover .forma-service:hover > .elementor-widget,
					selector .forma-services:hover .forma-service:focus-visible > .elementor-widget {
						opacity: 1;
					}
				}
				CSS,
			)
		);
	}

	/**
	 * A service as one hairline row: the name in display type and its line beside it, both revealed by lines. The row
	 * is a link; an even (odd-numbered) row runs right to left.
	 *
	 * @param array{name:string,line:string} $service
	 */
	private function service_row( string $slug, array $service, string $url, bool $reverse ): array {
		$photo = Images::attachment_id( self::SERVICE_PHOTOS[ $slug ] ?? '' );
		$image = $photo ? (string) wp_get_attachment_image_url( $photo, 'forma-960' ) : '';
		$align = $reverse ? 'right' : 'left';

		$settings = array(
			'html_tag'                  => 'a',
			'link'                      => Style::link( $url ),
			'css_classes'               => 'forma-service',
			'fm_entrance'               => 'lines',
			'flex_direction'            => $reverse ? 'row-reverse' : 'row',
			'flex_direction_tablet'     => 'column',
			'flex_wrap'                 => 'nowrap',
			'flex_justify_content'      => 'flex-start',
			'flex_align_items'          => 'flex-end',
			'flex_align_items_tablet'   => 'flex-start',
			'flex_gap'                  => Builder::gap( '8px', 'clamp(24px, 4vw, 64px)', 'custom' ),
			'padding'                   => Builder::box( 'clamp(14px, 1.6vw, 22px)', 0, 'clamp(14px, 1.6vw, 22px)', 0, 'custom' ),
			'border_border'             => 'solid',
			'border_width'              => Builder::box( 1, 0, 0, 0 ),
			'__globals__'               => array( 'border_color' => Style::color( 'line' ) ),
		);

		if ( '' !== $image ) {
			$settings['_attributes'] = 'data-cursor-image|' . $image;
		}

		return Style::row(
			array(
				Style::display(
					$service['name'],
					'clamp(40px, 6vw, 96px)',
					'h3',
					'ink',
					array(
						'align'        => $align,
						'align_tablet' => 'left',
					)
				),
				Style::text(
					'<p>' . esc_html( $service['line'] ) . '</p>',
					'body',
					'muted',
					array_merge(
						$this->measure( 30 ),
						array(
							'align'        => $align,
							'align_tablet' => 'left',
						)
					)
				),
			),
			$settings
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 5. Index.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Two columns from 1024px: the list (7 of 12) and, sticky beside it, a rounded Panel stage (5 of 12) whose model
	 * swaps to the project under the pointer or the keyboard. The list shows number, name and year; the stage is left
	 * out below 1024px, where there is no pointer to drive it.
	 */
	private function index(): array {
		$newest = get_posts(
			array(
				'post_type'      => Projects::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$title = Style::display_type( 'clamp(26px, 3vw, 44px)', 1.05, -0.03 );

		return Style::section(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( 'All projects', 'heading', 'h2' ),
								Builder::widget(
									'forma-project-index',
									array(
										'preview'       => 'none',
										'show_type'     => '',
										'show_location' => '',
										'show_year'     => 'yes',
										'custom_css'    => 'selector { --forma-index-meta-w: 4rem; }',
									) + $this->prefixed( $title, 'title_' )
								),
							),
							58.333,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 'clamp(24px, 3vw, 40px)', null, 'custom' ) )
						),
						Style::cell(
							array(
								Builder::widget(
									'forma-study-model',
									array(
										'source'      => 'project',
										'project'     => (string) ( $newest[0] ?? 0 ),
										'camera'      => 'three-quarter',
										'view_height' => Builder::size( 64, 'vh' ),
										'drag'        => 'yes',
										'swap'        => 'hover',
									)
								),
							),
							41.667,
							100,
							100,
							array(
								'css_classes'           => 'forma-index-stage',
								'background_background' => 'classic',
								'border_radius'         => Builder::box( 'var(--forma-r-panel)', null, null, null, 'custom' ),
								'overflow'              => 'hidden',
								'__globals__'           => array( 'background_color' => Style::color( 'raised' ) ),
								'custom_css'            => <<<'CSS'
								/* Only as tall as the model, so it can stick beside the list while the list scrolls. */
								@media (min-width: 1024px) {
									selector {
										position: sticky;
										top: 12vh;
										align-self: flex-start;
									}
								}
								@media (max-width: 1023px) {
									selector {
										display: none;
									}
								}
								CSS,
							)
						),
					),
					array(
						'flex_wrap'             => 'nowrap',
						'flex_direction_tablet' => 'column',
						'flex_gap'              => Builder::gap( 'clamp(24px, 4vw, 64px)', null, 'custom' ),
					)
				),
			),
			'page'
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 6. Recognition.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The awards as a slow ticker (Forma Marquee) in an inset Panel band that runs edge to edge: the year first, then the
	 * award and the project, newest first, with a slash between them. It pauses on hover.
	 */
	private function recognition(): array {
		$items = array();

		foreach ( ( new Components( $this->log ) )->awards() as $award ) {
			// "Winner, Hospitality Design Award" => the award; any other standing ("Shortlisted") stays in brackets.
			[ $standing, $name ] = array_pad( explode( ', ', $award['award'], 2 ), -2, '' );
			$suffix              = '' !== $standing && 'Winner' !== $standing ? ' (' . mb_strtolower( $standing ) . ')' : '';
			$items[]             = array( 'text' => "{$award['year']} {$name}{$suffix}, {$award['title']}" );
		}

		return Style::section(
			array(
				Builder::widget(
					'forma-marquee',
					array(
						'items'     => $items,
						'separator' => '/',
						'speed'     => 60,
						'direction' => 'left',
						'pause'     => 'yes',
					) + Style::display_type( 'clamp(28px, 3.4vw, 48px)', 1.1, -0.03 )
				),
			),
			'raised',
			array( 'padding' => Builder::box( 'clamp(28px, 4vw, 56px)', 0, 'clamp(28px, 4vw, 56px)', 0, 'custom' ) ),
			'full'
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

	/**
	 * A group control's settings under another name: the Typography group of the Project Index's titles is called
	 * `title_typography`, so {@see Style::display_type()}'s `typography_*` keys are renamed to `title_typography_*`.
	 *
	 * @param array  $settings Settings keyed `typography_*`.
	 * @param string $prefix   What goes in front of `typography`.
	 */
	private function prefixed( array $settings, string $prefix ): array {
		$out = array();

		foreach ( $settings as $key => $value ) {
			$out[ $prefix . $key ] = $value;
		}

		return $out;
	}

	/** One project's row of data/projects.php. */
	private function project_data( string $slug ): array {
		foreach ( $this->projects as $project ) {
			if ( $slug === $project['slug'] ) {
				return $project;
			}
		}

		throw new \InvalidArgumentException( esc_html( "Unknown project '{$slug}'." ) );
	}

	/** A page's permalink, or its conventional path before it has been seeded. */
	private function permalink( string $slug ): string {
		$id = $this->content->page_id( $slug );

		return $id ? (string) get_permalink( $id ) : home_url( "/{$slug}/" );
	}
}
