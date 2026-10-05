<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * The Projects archive (spec 5.2): a Theme Builder archive template for the project post type archive and the project
 * type term archives, in four parts:
 *
 * 1. Hero: an inset Panel with the archive title as the H1 (so on a term archive it is the term name), one line of
 *    copy and a chip with the number of projects (the type's own on a term archive).
 * 2. Filter: a Taxonomy Filter on the project type, styled as pills (the active type is an Ink pill).
 * 3. Grid and stage: the project cards as a Loop Grid on the current query (two columns, six at a time with a Load more
 *    button) beside a sticky rounded Panel whose study model swaps to the card under the pointer.
 * 4. The Closing CTA component.
 *
 * Text, the filter, the grid and the stage sit in the boxed 1320px column; only the hero's Panel and the stage's Panel
 * are backgrounds.
 */
final class Archive {

	public const KEY = 'projects-archive';

	/** Projects per page: the archive's main query decides it, and the Loop Grid follows the current query. */
	public const PER_PAGE = 6;

	/** The saved components the archive shows, which must exist before it is built. */
	private const COMPONENTS = array( 'project-card', 'closing-cta' );

	public function __construct( private \Closure $log ) {}

	/**
	 * @return int The archive template's post id.
	 * @throws \RuntimeException When a component it shows has not been seeded yet.
	 */
	public function build(): int {
		foreach ( self::COMPONENTS as $component ) {
			if ( ! Templates::id( $component ) ) {
				throw new \RuntimeException( esc_html( "The {$component} component does not exist: run `wp forma design --only=components` first." ) );
			}
		}

		$this->per_page();

		$id = Templates::upsert( self::KEY, 'archive', 'Projects archive', $this->elements(), $this->settings(), $this->conditions() );

		( $this->log )( 'Projects archive: ' . ( Templates::saved() ? "archive and type terms (#{$id})." : Builder::SKIPPED ) );

		return $id;
	}

	/** Theme Builder conditions: the project post type archive, and every project type term archive. */
	public function conditions(): array {
		return array(
			array( 'include', 'archive', Projects::POST_TYPE . '_archive' ),
			array( 'include', 'archive', Projects::TYPE_TAX ),
		);
	}

	/** Document settings: the editor previews the template on the projects archive. */
	public function settings(): array {
		return array( 'preview_type' => 'post_type_archive/' . Projects::POST_TYPE );
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			$this->hero(),
			$this->listing(),
			$this->component( 'closing-cta' ),
		);
	}

	/**
	 * A Loop Grid on the current query shows as many projects as WordPress's own "posts per page" reading setting
	 * says, so that setting is the six the archive loads at a time. The site has no blog, so nothing else reads it.
	 */
	private function per_page(): void {
		if ( self::PER_PAGE !== (int) get_option( 'posts_per_page' ) ) {
			update_option( 'posts_per_page', self::PER_PAGE );
			( $this->log )( 'Reading setting "posts per page": ' . self::PER_PAGE . '.' );
		}
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Hero.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Panel with generous padding (its top clears the floating header), auto height. From 1024px the H1 is at
	 * the left and the line of copy and the count chip at the right, level with the title's last line; below that they
	 * stack.
	 */
	private function hero(): array {
		return Style::section(
			array(
				Style::row(
					array(
						Style::cell( array( $this->title() ), 62, 100, 100 ),
						Style::cell(
							array(
								Style::text(
									'<p>Houses, hotels, workplaces, interiors and objects, 2019 to 2026.</p>',
									'body',
									'muted',
									array( 'custom_css' => 'selector p { max-width: 36ch; margin: 0; }' )
								),
								Style::chip(
									'11 projects',
									array( '__dynamic__' => array( 'title' => Builder::tag( 'forma-project-count' ) ) )
								),
							),
							32,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 16 ) )
						),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'flex-end',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(20px, 3vw, 40px)', null, 'custom' ),
					)
				),
			),
			'raised',
			array( 'padding' => Builder::box( 'clamp(136px, 15vw, 220px)', Style::PANEL_GUTTER, 'clamp(40px, 5vw, 72px)', Style::PANEL_GUTTER, 'custom' ) )
		);
	}

	/** The H1: "Projects" on the archive, the term's name on a term archive. */
	private function title(): array {
		return Builder::widget(
			'theme-archive-title',
			array(
				'header_size' => 'h1',
				'fm_entrance' => 'lines',
				'__dynamic__' => array( 'title' => Builder::tag( 'archive-title', array( 'include_context' => '' ) ) ),
				'__globals__' => array(
					'typography_typography' => Style::font( 'display-l' ),
					'title_color'           => Style::color( 'ink' ),
				),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. Filter and 3. grid with the stage.
	// ---------------------------------------------------------------------------------------------------------------

	/** The type filter over the project grid and the model stage, in one boxed section. */
	private function listing(): array {
		$grid   = $this->grid();
		$filter = $this->filter( $grid['id'] );

		return Style::section(
			array(
				Style::heading(
					'Project list',
					'heading',
					'h2',
					'ink',
					array( 'custom_css' => 'selector { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }' )
				),
				$filter,
				Style::row(
					array(
						Style::cell( array( $grid ), 66.667, 100, 100 ),
						$this->stage(),
					),
					array(
						'flex_wrap'             => 'nowrap',
						'flex_align_items'      => 'flex-start',
						'flex_direction_tablet' => 'column',
						'flex_gap'              => Builder::gap( 'clamp(16px, 2vw, 32px)', null, 'custom' ),
					)
				),
			),
			'page',
			array(
				'padding'  => Builder::box( 'clamp(24px, 3vw, 40px)', 'var(--forma-gutter)', 'clamp(48px, 6vw, 96px)', 'var(--forma-gutter)', 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(20px, 2.4vw, 32px)', null, 'custom' ),
			)
		);
	}

	/**
	 * The Taxonomy Filter on the project type, linked to the grid by its element id. Items are pills in Label 600: a
	 * Panel fill, a darker one on hover, and an Ink fill with Paper text for the active type.
	 */
	private function filter( string $grid_id ): array {
		return Builder::widget(
			'taxonomy-filter',
			array(
				'selected_element'                   => $grid_id,
				'taxonomy'                           => Projects::TYPE_TAX,
				'direction'                          => 'horizontal',
				'item_alignment_horizontal'          => 'start',
				'show_first_item'                    => 'yes',
				'first_item_title'                   => 'All',
				// On the current query Pro cannot work out which types the results use (it asks the query for every
				// matching id and gets one page of posts), and would then list no type at all. Every type has
				// projects, so all of them are listed.
				'show_empty_items'                   => 'yes',
				'taxonomy_filter_items_space_between' => Builder::size( 8 ),
				'taxonomy_filter_typography_typography'  => 'custom',
				'taxonomy_filter_typography_font_family' => Style::SANS,
				'taxonomy_filter_typography_font_weight' => '600',
				'taxonomy_filter_typography_font_size'   => Builder::size( 13 ),
				'taxonomy_filter_typography_line_height' => Builder::size( 1.3, 'em' ),
				'taxonomy_filter_normal_background_background' => 'classic',
				'taxonomy_filter_hover_background_background'  => 'classic',
				'taxonomy_filter_active_background_background' => 'classic',
				'taxonomy_filter_border_radius'      => Builder::box( 999 ),
				'taxonomy_filter_padding'            => Builder::box( 10, 16, 10, 16 ),
					// 45px tall where a finger is the pointer.
					'taxonomy_filter_padding_tablet'     => Builder::box( 14, 18, 14, 18 ),
				'custom_css'                         => <<<'CSS'
				/* Pro's items are buttons: no shadow, and the label stays on one line inside its pill. */
				selector .e-filter-item {
					box-shadow: none;
					white-space: nowrap;
				}
				CSS,
				'__globals__'                        => array(
					'taxonomy_filter_normal_text_color'        => Style::color( 'ink' ),
					'taxonomy_filter_normal_background_color'  => Style::color( 'raised' ),
					'taxonomy_filter_hover_text_color'         => Style::color( 'ink' ),
					'taxonomy_filter_hover_background_color'   => Style::color( 'line' ),
					'taxonomy_filter_active_text_color'        => Style::color( 'page' ),
					'taxonomy_filter_active_background_color'  => Style::color( 'ink' ),
				),
			)
		);
	}

	/**
	 * The Loop Grid on the current query: two columns (one on mobile) of project cards with a 16px gap, six at a time and
	 * a Load more button (an Ink pill, the Kit's button style).
	 */
	private function grid(): array {
		return Builder::widget(
			'loop-grid',
			array(
				'template_id'                         => (string) Templates::id( 'project-card' ),
				'columns'                             => '2',
				'columns_tablet'                      => '2',
				'columns_mobile'                      => '1',
				'posts_per_page'                      => self::PER_PAGE,
				'post_query_post_type'                => 'current_query',
				'pagination_type'                     => 'load_more_on_click',
				'pagination_load_type'                => 'ajax',
				'text'                                => 'Load more',
				'load_more_button_align'              => 'center',
				'load_more_no_posts_message_switcher' => 'yes',
				'load_more_no_posts_custom_message'   => 'That is every project in this selection.',
				'column_gap'                          => Builder::size( 16 ),
				'row_gap'                             => Builder::size( 16 ),
				// The Kit's pill has Ink fill and Paper text, but the Loop Grid does not pick the text colour up.
				'__globals__'                         => array(
					'button_text_color'            => Style::color( 'page' ),
					'button_background_hover_color' => Style::color( 'accent' ),
				),
			)
		);
	}

	/**
	 * The stage: a rounded Panel, sticky from 1024px (96px from the top, under the floating header) beside the grid, whose
	 * study model swaps to the project card under the pointer or the keyboard (the cards carry the swap in the project-card
	 * component). It is left out below 1024px, where there is no pointer to drive it.
	 */
	private function stage(): array {
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

		return Style::cell(
			array(
				Builder::widget(
					'forma-study-model',
					array(
						'source'      => 'project',
						'project'      => (string) ( $newest[0] ?? 0 ),
						'camera'      => 'three-quarter',
						'view_height' => Builder::size( 60, 'vh' ),
						'drag'        => 'yes',
						'swap'        => 'hover',
					)
				),
			),
			33.333,
			100,
			100,
			array(
				'css_classes'           => 'forma-archive-stage',
				'background_background' => 'classic',
				'border_radius'         => Builder::box( 'var(--forma-r-panel)', null, null, null, 'custom' ),
				'overflow'              => 'hidden',
				'__globals__'           => array( 'background_color' => Style::color( 'raised' ) ),
				'custom_css'            => <<<'CSS'
				@media (min-width: 1024px) {
					selector {
						position: sticky;
						top: 96px;
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
}
