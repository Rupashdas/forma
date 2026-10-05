<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;
use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * What the inner pages (Studio, Services, Process, Contact, Colophon) have in common: they are existing WordPress
 * pages whose Elementor body is seeded, shown edge to edge under the Theme Builder header and footer with no page
 * title (the first section carries the H1). The base class finds the page, checks the saved components it shows
 * exist, saves through {@see Builder::save()} (so a page edited in Elementor is left alone unless forced) and offers
 * the building blocks the pages share, all in the v2 vocabulary: an inset Panel masthead, Panel tiles, grids, rounded
 * photographs, hairline rows and the sticky model stage with glass cards that scrolls over it.
 *
 * Text, grids, buttons and models sit in the boxed 1320px column; only a Panel's or a band's background runs wider.
 */
abstract class Page {

	/** The padding above and below a section on Paper that follows another section. */
	protected const MID = 'clamp(40px, 6vw, 96px)';

	protected array $site;

	protected Content $content;

	public function __construct( protected \Closure $log ) {
		$this->site    = require FORMA_ENGINE_PATH . 'data/site.php';
		$this->content = new Content( $log );
	}

	/** The page's slug, as seeded by {@see Content}. */
	abstract protected function key(): string;

	/** What the log says was built, e.g. `8 sections`. */
	abstract protected function summary(): string;

	/** The sections of the page, top to bottom. */
	abstract protected function sections(): array;

	/**
	 * The saved components this page shows, which must exist before it is built.
	 *
	 * @return string[]
	 */
	protected function components(): array {
		return array();
	}

	/**
	 * @return int The page's post id.
	 * @throws \RuntimeException When the page or one of its components has not been seeded yet.
	 */
	public function build(): int {
		$key = $this->key();
		$id  = $this->content->page_id( $key );

		if ( ! $id ) {
			throw new \RuntimeException( esc_html( "The {$key} page does not exist: run `wp forma content` first." ) );
		}

		foreach ( $this->components() as $component ) {
			if ( ! Templates::id( $component ) ) {
				throw new \RuntimeException( esc_html( "The {$component} component does not exist: run `wp forma design --only=components` first." ) );
			}
		}

		if ( Builder::save( $id, $this->elements(), $this->settings() ) ) {
			( $this->log )( ucfirst( $key ) . ': ' . $this->summary() . " (#{$id})." );
		} else {
			( $this->log )( ucfirst( $key ) . ': ' . Builder::SKIPPED );
		}

		return $id;
	}

	/** Page settings: edge to edge under the Theme Builder header and footer, with no page title. */
	public function settings(): array {
		return array(
			'template'   => 'elementor_header_footer',
			'hide_title' => 'yes',
		);
	}

	public function elements(): array {
		Builder::reset( $this->key() );

		return $this->sections();
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Sections.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * A section of boxed content on Paper. Its sides carry the page gutter; `$top` and `$bottom` are CSS lengths.
	 *
	 * @param array  $children Elements of the section.
	 * @param string $top      Padding above.
	 * @param string $bottom   Padding below.
	 * @param array  $extra    Settings for the section's container.
	 */
	protected function band( array $children, string $top = self::MID, string $bottom = self::MID, array $extra = array() ): array {
		return Style::section(
			$children,
			'page',
			self::merge(
				array(
					'padding'  => Builder::box( $top, 'var(--forma-gutter)', $bottom, 'var(--forma-gutter)', 'custom' ),
					'flex_gap' => Builder::gap( 'clamp(28px, 4vw, 56px)', null, 'custom' ),
				),
				$extra
			)
		);
	}

	/**
	 * An inset Panel (or other surface) section. Its top padding clears the floating header.
	 *
	 * @param string $surface `raised` or `deep`.
	 */
	protected function panel( array $children, string $top = 'clamp(112px, 12vw, 168px)', string $bottom = 'clamp(40px, 5vw, 72px)', array $extra = array(), string $surface = 'raised' ): array {
		return Style::section(
			$children,
			$surface,
			self::merge(
				array(
					'padding'  => Builder::box( $top, Style::PANEL_GUTTER, $bottom, Style::PANEL_GUTTER, 'custom' ),
					'flex_gap' => Builder::gap( 'clamp(28px, 4vw, 56px)', null, 'custom' ),
				),
				$extra
			)
		);
	}

	/**
	 * The opening of an inner page: an inset Panel with the H1 in Display L at the left (the page's one entrance) and
	 * an intro beside it, level with the title's last line; below 1024px they stack. Further blocks go under them.
	 *
	 * @param array  $after       Elements placed under the title row.
	 * @param array  $title_extra Settings for the H1 widget.
	 */
	protected function masthead( string $title, string $intro = '', array $after = array(), array $title_extra = array() ): array {
		$cells = array(
			Style::cell(
				array( Style::heading( $title, 'display-l', 'h1', 'ink', self::merge( array( 'fm_entrance' => 'lines' ), $title_extra ) ) ),
				$intro ? 62 : 100,
				100,
				100
			),
		);

		if ( $intro ) {
			$cells[] = Style::cell( array( $this->paragraph( $intro, 'muted', 40 ) ), 32, 100, 100 );
		}

		return $this->panel(
			array_merge(
				array(
					Style::row(
						$cells,
						array(
							'flex_wrap'               => 'nowrap',
							'flex_justify_content'    => 'space-between',
							'flex_align_items'        => 'flex-end',
							'flex_gap'                => Builder::gap( 'clamp(20px, 3vw, 40px)', null, 'custom' ),
							'flex_direction_tablet'   => 'column',
							'flex_align_items_tablet' => 'stretch',
						)
					),
				),
				$after
			),
			'clamp(136px, 15vw, 220px)',
			'clamp(40px, 5vw, 72px)'
		);
	}

	/**
	 * A section's opening: its H2 at the left and a line of text beside it, level with the heading's baseline; below
	 * 1024px the line drops under the heading.
	 */
	protected function section_head( string $title, string $text = '' ): array {
		$cells = array( Style::cell( array( $this->heading( $title ) ), $text ? 50 : 100, 100, 100 ) );

		if ( $text ) {
			$cells[] = Style::cell( array( $this->paragraph( $text, 'muted', 44 ) ), 42, 100, 100 );
		}

		return Style::row(
			$cells,
			array(
				'flex_wrap'               => 'nowrap',
				'flex_justify_content'    => 'space-between',
				'flex_align_items'        => 'flex-end',
				'flex_gap'                => Builder::gap( 'clamp(16px, 2.4vw, 32px)', null, 'custom' ),
				'flex_direction_tablet'   => 'column',
				'flex_align_items_tablet' => 'stretch',
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Elements.
	// ---------------------------------------------------------------------------------------------------------------

	/** A paragraph of body text, escaped, at a reading measure. */
	protected function paragraph( string $text, string $color = 'ink', int $measure = 60, array $extra = array() ): array {
		return Style::text(
			'<p>' . esc_html( $text ) . '</p>',
			'body',
			$color,
			self::merge( array( 'custom_css' => "selector p {\n\tmax-width: {$measure}ch;\n\tmargin: 0;\n}" ), $extra )
		);
	}

	/** A section heading (H2) that stands alone. */
	protected function heading( string $title, array $extra = array() ): array {
		return Style::heading( $title, 'heading', 'h2', 'ink', $extra );
	}

	/**
	 * A Panel tile: a rounded block on the Panel colour (an Ink tile inside an Ink band) with padding all round.
	 *
	 * @param array $children Elements of the tile.
	 * @param array $extra    Settings for the tile's container.
	 */
	protected function tile( array $children, array $extra = array() ): array {
		return Style::stack(
			$children,
			self::merge(
				array(
					'flex_gap'              => Builder::gap( 12 ),
					'padding'               => Builder::box( 'clamp(22px, 2.4vw, 32px)', 'clamp(22px, 2.4vw, 32px)', 'clamp(22px, 2.4vw, 32px)', 'clamp(22px, 2.4vw, 32px)', 'custom' ),
					'border_radius'         => Builder::box( 'var(--forma-r-tile)', null, null, null, 'custom' ),
					'background_background' => 'classic',
					'__globals__'           => array( 'background_color' => Style::color( 'raised' ) ),
				),
				$extra
			)
		);
	}

	/**
	 * A grid container: `$columns` equal columns from the desktop width, `$tablet` on tablet and `$mobile` on mobile,
	 * with every row as tall as its own cells.
	 *
	 * @param array $cells   The grid items.
	 * @param array $columns Columns on desktop, tablet and mobile, e.g. `array( 3, 1, 1 )`.
	 * @param array $extra   Settings for the grid container.
	 */
	protected function grid( array $cells, array $columns, array $extra = array() ): array {
		[ $desktop, $tablet, $mobile ] = $columns;

		return Builder::container(
			self::merge(
				array(
					'content_width'            => 'full',
					'container_type'           => 'grid',
					'grid_columns_grid'        => $this->fr( $desktop ),
					'grid_columns_grid_tablet' => $this->fr( $tablet ),
					'grid_columns_grid_mobile' => $this->fr( $mobile ),
					'grid_gaps'                => Builder::gap( 'clamp(12px, 1.6vw, 24px)', null, 'custom' ),
					// The control only offers equal rows (repeat(n, 1fr)); here each row is as tall as its own cells.
					'custom_css'               => 'selector { --e-con-grid-template-rows: auto; }',
				),
				$extra
			),
			$cells,
			true
		);
	}

	/** A grid container's column count, as the `fr` slider stores it. */
	protected function fr( int $columns ): array {
		return array(
			'unit'  => 'fr',
			'size'  => $columns,
			'sizes' => array(),
		);
	}

	/**
	 * A photograph from the media library in a rounded frame cropped to a fixed ratio.
	 *
	 * @param string $file     File name in data/images.php, e.g. `studio-01.jpg`.
	 * @param array  $ratios   Aspect ratios on desktop, tablet and mobile, e.g. `array( '4 / 3', '4 / 3', '4 / 3' )`.
	 * @param string $position CSS object-position of the crop.
	 * @param string $radius   CSS border radius of the photograph: the image radius, or `var(--forma-r-tile)` in a Panel.
	 * @param array  $extra    Settings for the image widget.
	 * @throws \RuntimeException When the photograph has not been imported.
	 */
	protected function photo( string $file, array $ratios, string $position = '50% 50%', string $radius = 'var(--forma-r-img)', array $extra = array() ): array {
		[ $desktop, $tablet, $mobile ] = $ratios;

		return Builder::widget(
			'image',
			self::merge(
				array(
					'image'      => Builder::image( $this->image_id( $file ) ),
					'image_size' => 'large',
					'width'      => Builder::size( 100, '%' ),
					'custom_css' => <<<CSS
					selector img {
						display: block;
						width: 100%;
						aspect-ratio: {$desktop};
						object-fit: cover;
						object-position: {$position};
						border-radius: {$radius};
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
					CSS,
				),
				$extra
			)
		);
	}

	/** The photograph's attachment id.
	 *
	 * @throws \RuntimeException When the photograph has not been imported.
	 */
	protected function image_id( string $file ): int {
		$id = Images::attachment_id( $file );

		if ( ! $id ) {
			throw new \RuntimeException( esc_html( "The photograph {$file} is not in the media library: run `wp forma images` first." ) );
		}

		return $id;
	}

	/** A hairline-ruled list of rows: the rows' top rules plus one closing rule under the last. */
	protected function ruled( array $rows, array $extra = array() ): array {
		return Style::stack(
			$rows,
			self::merge(
				array(
					'border_border' => 'solid',
					'border_width'  => Builder::box( 0, 0, 1, 0 ),
					'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
				),
				$extra
			)
		);
	}

	/**
	 * One row of a ruled list: a top hairline and the cells side by side, level on their first baseline. Cells wrap
	 * below the mobile width, so give every cell its mobile width.
	 */
	protected function ruled_row( array $cells, array $extra = array() ): array {
		return Style::row(
			$cells,
			self::merge(
				array(
					'flex_wrap'            => 'nowrap',
					'flex_wrap_mobile'     => 'wrap',
					'flex_justify_content' => 'space-between',
					'flex_gap'             => Builder::gap( 8, 24 ),
					'padding'              => Builder::box( 20, 0, 20, 0 ),
					'border_border'        => 'solid',
					'border_width'         => Builder::box( 1, 0, 0, 0 ),
					'__globals__'          => array( 'border_color' => Style::color( 'line' ) ),
					'custom_css'           => 'selector { align-items: baseline; }',
				),
				$extra
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// The model stage.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * A tour: a section several screens tall whose first child is the stage, a Panel pinned to the viewport with
	 * `sticky` (100vh less the inset), holding one study model that follows the section's scroll. The notes come
	 * second and are pulled back up by 100vh, so they scroll over the stage; each is a slot as tall as its share of
	 * the section, and its glass card sits at the left of the boxed column. From 1024px the model fills the right 62%
	 * of the stage; the stage's drag is off so the page keeps the wheel and the touch. Below 1024px the theme's
	 * stylesheet (site.css) moves the stage to the foot of the screen and the cards above it, so they never cover the
	 * model.
	 *
	 * @param array   $model   Settings of the study model widget: its source and the scroll behaviours.
	 * @param array[] $notes   One list of elements per slot (what goes in its glass card).
	 * @param int     $height  The section's height in vh.
	 * @param array   $overlay  Elements laid over the stage (they are positioned by their own Custom CSS).
	 * @param string  $modifier A class for the section: forma-tour--long gives the cards more of the screen on a phone.
	 */
	protected function tour( array $model, array $notes, int $height, array $overlay = array(), string $modifier = '' ): array {
		$slot  = round( $height / max( 1, count( $notes ) ), 2 );
		$slots = array();

		foreach ( $notes as $children ) {
			$slots[] = $this->slot( $children, $slot );
		}

		$widget = Builder::widget(
			'forma-study-model',
			$model + array(
				'camera'         => 'three-quarter',
				'view_height'    => Builder::size( 'calc(100vh - 2 * var(--forma-inset))', 'custom' ),
				'assemble'       => '',
				'drag'           => '',
				'callouts'       => 'yes',
				'scroll_trigger' => 'section',
				'custom_css'     => <<<'CSS'
				@media (min-width: 1024px) {
					selector.elementor-widget {
						width: 62%;
						align-self: flex-end;
					}
				}
				CSS,
			)
		);

		return Style::section(
			array(
				Style::stack(
					array_merge( array( $widget ), $overlay ),
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
						/* The column is the positioning context of anything laid over the model. */
						selector > .e-con-inner {
							position: relative;
						}
						CSS,
					)
				),
				Style::stack(
					$slots,
					array(
						// Boxed too, with the page gutter (the stage's inset plus its padding), so the cards sit on the column's left edge.
						'content_width' => 'boxed',
						'css_classes'   => 'forma-tour__notes',
						'padding'       => Builder::box( 0, 'var(--forma-gutter)', 0, 'var(--forma-gutter)', 'custom' ),
						'custom_css'    => <<<'CSS'
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
				'min_height'  => Builder::size( $height, 'vh' ),
				'css_classes' => trim( 'forma-tour ' . $modifier ),
			),
			'full'
		);
	}

	/**
	 * One slot of a tour: a box `$vh` tall with a glass card (Paper at 70% over a blur) at its left, centred in the
	 * slot, or at its bottom on small screens.
	 *
	 * @param array $children Elements of the card.
	 * @param float $vh       The slot's height in vh.
	 * @param array $card     Settings for the card's container.
	 */
	protected function slot( array $children, float $vh, array $card = array() ): array {
		return Style::stack(
			array(
				Style::stack(
					$children,
					self::merge(
						array(
							'flex_gap'      => Builder::gap( 10 ),
							'padding'       => Builder::box( 22, 24, 24, 24 ),
							'border_radius' => Builder::box( 'var(--forma-r-tile)', null, null, null, 'custom' ),
							'css_classes'   => 'forma-tour__card',
							'custom_css'    => <<<'CSS'
							selector {
								max-width: 400px;
								background-color: rgba(246, 245, 241, 0.7);
								background-color: color-mix(in srgb, var(--forma-page) 70%, transparent);
								-webkit-backdrop-filter: blur(12px);
								backdrop-filter: blur(12px);
								box-shadow: inset 0 0 0 1px var(--forma-line);
							}
							CSS,
						),
						$card
					)
				),
			),
			array(
				'min_height'                  => Builder::size( $vh, 'vh' ),
				'flex_justify_content'        => 'center',
				'flex_justify_content_tablet' => 'flex-end',
				'flex_align_items'            => 'flex-start',
				'padding_tablet'              => Builder::box( 0, 0, 24, 0 ),
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
	protected function component( string $key ): array {
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

	/** A page's permalink, or its conventional path before it has been seeded. */
	protected function permalink( string $slug ): string {
		$id = $this->content->page_id( $slug );

		return $id ? (string) get_permalink( $id ) : home_url( "/{$slug}/" );
	}

	/**
	 * `$extra` wins, except that its `__globals__` are merged into the base's rather than replacing them, and class
	 * lists are joined.
	 */
	protected static function merge( array $base, array $extra ): array {
		$merged = array_merge( $base, $extra );

		if ( isset( $base['__globals__'] ) || isset( $extra['__globals__'] ) ) {
			$merged['__globals__'] = array_merge( $base['__globals__'] ?? array(), $extra['__globals__'] ?? array() );
		}

		foreach ( array( 'css_classes', '_css_classes' ) as $key ) {
			if ( isset( $base[ $key ], $extra[ $key ] ) ) {
				$merged[ $key ] = trim( $base[ $key ] . ' ' . $extra[ $key ] );
			}
		}

		return $merged;
	}
}
