<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * The Projects archive (spec 7.2): a Theme Builder archive template for the project post type archive and the
 * project type term archives. The H1 is the archive title, so on a term archive it is the term name, and the intro is
 * the archive description (the post type's description, or the term's). Under it sit a Taxonomy Filter on the project
 * type and a Loop Grid on the current query: two columns, an alternate wide card on every third project, six projects
 * at a time with a Load more button. The Closing CTA ends the page.
 */
final class Archive {

	public const KEY = 'projects-archive';

	/** Projects per page: the archive's main query decides it, and the Loop Grid follows the current query. */
	public const PER_PAGE = 6;

	/** The saved components the archive shows, which must exist before it is built. */
	private const COMPONENTS = array( 'project-card', 'project-card-wide', 'closing-cta' );

	private array $projects;

	public function __construct( private \Closure $log ) {
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
	}

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
			$this->masthead(),
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
	// Masthead.
	// ---------------------------------------------------------------------------------------------------------------

	/** The archive title and its intro, then a hairline title block, under the fixed header. */
	private function masthead(): array {
		return Style::section(
			array(
				Style::row(
					array(
						Style::cell( array( $this->title() ), 62, 100, 100 ),
						Style::cell( array( $this->intro() ), 30, 100, 100 ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'flex-end',
						'flex_gap'                => Builder::gap( 24 ),
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
					)
				),
				$this->title_block(),
			),
			'page',
			array(
				'padding'  => Builder::box( 'clamp(112px, 14vw, 168px)', 'var(--forma-gutter)', 'clamp(32px, 4vw, 56px)', 'var(--forma-gutter)', 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(32px, 4vw, 64px)', null, 'custom' ),
			)
		);
	}

	/** The H1: "Projects" on the archive, the term's name on a term archive. */
	private function title(): array {
		return Builder::widget(
			'theme-archive-title',
			array(
				'header_size' => 'h1',
				'__dynamic__' => array( 'title' => Builder::tag( 'archive-title', array( 'include_context' => '' ) ) ),
				'__globals__' => array(
					'typography_typography' => Style::font( 'display-l' ),
					'title_color'           => Style::color( 'ink' ),
				),
			)
		);
	}

	/** One line under the title: the post type's description, or the term's. */
	private function intro(): array {
		return Style::text(
			'',
			'body',
			'muted',
			array(
				'custom_css'  => 'selector { max-width: 40ch; }',
				'__dynamic__' => array( 'editor' => Builder::tag( 'archive-description' ) ),
			)
		);
	}

	/**
	 * What the index holds, in three hairline cells like the title block on a drawing sheet. The figures describe the
	 * whole index, so they stay true on a type archive too ("in total").
	 */
	private function title_block(): array {
		$years = array_map( static fn( array $project ): string => substr( $project['date'], 0, 4 ), $this->projects );
		$texts = array(
			'Complete index',
			sprintf( '%d projects in total', count( $this->projects ) ),
			sprintf( 'Completed %s–%s', min( $years ), max( $years ) ),
		);

		$cells = array();

		foreach ( $texts as $index => $text ) {
			$cells[] = Style::cell(
				array( Style::label( $text, 0 === $index ? 'ink' : 'muted' ) ),
				100 / 3,
				100 / 3,
				100 / 3,
				array(
					'padding'        => Builder::box( 14, 20, 14, 0 === $index ? 0 : 20 ),
					'padding_mobile' => Builder::box( 10, 8, 10, 0 === $index ? 0 : 12 ),
					'border_border'  => 'solid',
					'border_width'   => Builder::box( 0, 0, 0, 0 === $index ? 0 : 1 ),
					'__globals__'    => array( 'border_color' => Style::color( 'line' ) ),
				)
			);
		}

		return Style::row(
			$cells,
			array(
				'flex_wrap'     => 'nowrap',
				'border_border' => 'solid',
				'border_width'  => Builder::box( 1, 0, 1, 0 ),
				'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Filter and grid.
	// ---------------------------------------------------------------------------------------------------------------

	/** The type filter over the project grid. */
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
				Style::stack(
					array( $filter ),
					array(
						'padding'       => Builder::box( 0, 0, 12, 0 ),
						'border_border' => 'solid',
						'border_width'  => Builder::box( 0, 0, 1, 0 ),
						'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
					)
				),
				$grid,
			),
			'page',
			array(
				'padding'  => Builder::box( 'clamp(8px, 1vw, 16px)', 'var(--forma-gutter)', 'clamp(64px, 8vw, 120px)', 'var(--forma-gutter)', 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(32px, 4vw, 56px)', null, 'custom' ),
			)
		);
	}

	/**
	 * The Taxonomy Filter on the project type, linked to the grid by its element id. Items are Label type; the active
	 * type is underlined in the accent colour (every state keeps the same 2px border, transparent when inactive, so
	 * choosing a type never shifts the row).
	 */
	private function filter( string $grid_id ): array {
		$underline = static fn( string $state ): array => array(
			"taxonomy_filter_{$state}_border_border" => 'solid',
			"taxonomy_filter_{$state}_border_width"  => Builder::box( 0, 0, 2, 0 ),
		);

		return Builder::widget(
			'taxonomy-filter',
			array(
				'selected_element'                    => $grid_id,
				'taxonomy'                            => Projects::TYPE_TAX,
				'direction'                           => 'horizontal',
				'item_alignment_horizontal'           => 'start',
				'show_first_item'                     => 'yes',
				'first_item_title'                    => 'All',
				// On the current query Pro cannot work out which types the results use (it asks the query for every
				// matching id and gets one page of posts), and would then list no type at all. Every type has
				// projects, so all of them are listed.
				'show_empty_items'                    => 'yes',
				'taxonomy_filter_items_space_between' => Builder::size( 'clamp(20px, 3vw, 40px)', 'custom' ),
				'taxonomy_filter_padding'             => Builder::box( 8, 0, 8, 0 ),
				'taxonomy_filter_normal_border_color' => 'rgba(0,0,0,0)',
				'custom_css'                          => <<<'CSS'
				/* Pro's items are buttons; keep them square, flat and left-aligned like the rest of the page. */
				selector .e-filter-item {
					border-radius: 0;
					box-shadow: none;
					justify-content: flex-start;
				}
				CSS,
				'__globals__'                         => array(
					'taxonomy_filter_typography_typography' => Style::font( 'label' ),
					'taxonomy_filter_normal_text_color'     => Style::color( 'muted' ),
					'taxonomy_filter_hover_text_color'      => Style::color( 'ink' ),
					'taxonomy_filter_hover_border_color'    => Style::color( 'line' ),
					'taxonomy_filter_active_text_color'     => Style::color( 'ink' ),
					'taxonomy_filter_active_border_color'   => Style::color( 'accent' ),
				),
			) + $underline( 'normal' ) + $underline( 'hover' ) + $underline( 'active' )
		);
	}

	/**
	 * The Loop Grid on the current query: two columns (one on mobile) of portrait project cards, with the wide card
	 * on every third project spanning both. Pro prints a style element inside the grid, so the stagger below counts
	 * items by type, not by child position.
	 */
	private function grid(): array {
		return Builder::widget(
			'loop-grid',
			array(
				'template_id'                      => (string) Templates::id( 'project-card' ),
				'columns'                          => '2',
				'columns_tablet'                   => '2',
				'columns_mobile'                   => '1',
				'posts_per_page'                   => self::PER_PAGE,
				'post_query_post_type'             => 'current_query',
				'alternate_template'               => 'yes',
				'alternate_templates'              => array(
					array(
						'_id'             => Builder::id(),
						'template_id'     => (string) Templates::id( 'project-card-wide' ),
						'repeat_template' => 3,
						'show_once'       => '',
						'column_span'     => '2',
					),
				),
				'pagination_type'                  => 'load_more_on_click',
				'pagination_load_type'             => 'ajax',
				'text'                             => 'Load more',
				'load_more_button_align'           => 'center',
				'load_more_no_posts_message_switcher' => 'yes',
				'load_more_no_posts_custom_message' => 'That is every project in this selection.',
				'column_gap'                       => Builder::size( 'var(--forma-gutter)', 'custom' ),
				'row_gap'                          => Builder::size( 'clamp(48px, 7vw, 104px)', 'custom' ),
				'custom_css'                       => <<<'CSS'
				/*
				 * From 768px the right-hand card of each pair drops a little, so the two columns do not line up like
				 * a catalogue. The wide card on every third place spans both columns, which resets the rhythm.
				 */
				@media (min-width: 768px) {
					selector .elementor-loop-container > div:nth-of-type(3n+2) {
						margin-top: clamp(32px, 8vw, 120px);
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
