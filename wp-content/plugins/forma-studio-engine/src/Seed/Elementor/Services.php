<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The Services page (spec 5.2): what the studio does, with a model that follows the list.
 *
 * 1. Hero: an inset Panel with the H1 and an intro.
 * 2. The six services as a Nested Accordion beside a sticky rounded Panel with a study model, from 1024px. Each service
 *    opens on its deliverables, shown as chips, and a typical timeline; hovering or focusing an item swaps the model to
 *    a project typical of that service. Each item's id is its slug, so Home's links (`/services/#architecture`) land
 *    on it and open it.
 * 3. Ways to work: three Panel tiles.
 * 4. Frequently asked questions in a second Nested Accordion; the Seo module turns the same questions into FAQPage
 *    JSON-LD.
 * 5. The saved Closing CTA.
 */
final class Services extends Page {

	public const KEY = 'services';

	protected function key(): string {
		return self::KEY;
	}

	protected function summary(): string {
		return '5 sections, 1 model';
	}

	protected function components(): array {
		return array( 'closing-cta' );
	}

	protected function sections(): array {
		return array(
			$this->hero(),
			$this->services(),
			$this->ways(),
			$this->faq(),
			$this->component( 'closing-cta' ),
		);
	}

	private function hero(): array {
		$page = $this->site['services_page'];

		return $this->masthead( $page['title'], $page['intro'] );
	}

	// ---------------------------------------------------------------------------------------------------------------
	// The six services and the model.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The accordion at the left (58%), and from 1024px the stage at the right (38%); below that the stage is left out,
	 * because the swap needs a pointer and each panel names its typical project in a link.
	 */
	private function services(): array {
		$items = array();

		foreach ( $this->site['services'] as $slug => $service ) {
			$project = $this->content->project_id( $service['project'] );

			$items[] = array(
				'title'    => esc_html( $service['name'] ) . '<span class="forma-acc__line">' . esc_html( $service['line'] ) . '</span>',
				'id'       => $slug,
				'children' => array( $this->panel_of( $service, $project ) ),
			);
		}

		$accordion = $this->accordion(
			$items,
			array(
				'title_tag'   => 'h2',
				'custom_css'  => $this->services_css(),
				'__globals__' => array( 'title_typography_typography' => Style::font( 'heading' ) ),
			)
		);

		return $this->band(
			array(
				Style::row(
					array(
						Style::cell( array( $accordion ), 58, 100, 100 ),
						$this->stage(),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'flex-start',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
					)
				),
			),
			'clamp(24px, 3vw, 48px)',
			self::MID
		);
	}

	/**
	 * The stage: a rounded Panel, sticky from 1024px (96px from the top, under the floating header), whose study model
	 * swaps to a typical project of the service under the pointer or the keyboard. It starts on the first service's.
	 */
	private function stage(): array {
		$services = $this->site['services'];
		$first    = (string) $this->content->project_id( (string) ( reset( $services )['project'] ?? 'casa-nera' ) );

		return Style::cell(
			array(
				Builder::widget(
					'forma-study-model',
					array(
						'source'      => 'project',
						'project'     => $first,
						'camera'      => 'three-quarter',
						'view_height' => Builder::size( 60, 'vh' ),
						'drag'        => 'yes',
						'swap'        => 'hover',
					)
				),
			),
			38,
			100,
			100,
			array(
				'css_classes'           => 'forma-services-stage',
				'background_background' => 'classic',
				'border_radius'         => Builder::box( 'var(--forma-r-panel)', null, null, null, 'custom' ),
				'overflow'              => 'hidden',
				'__globals__'           => array( 'background_color' => Style::color( 'raised' ) ),
				'custom_css'            => <<<'CSS'
				/* Only as tall as the model, so it can stick beside the list while the list scrolls. */
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

	/**
	 * The panel of one service: what you receive, as chips, and how long it takes, and a link to the typical project.
	 * The panel names that project for the study model (Forma Motion "Swap the page's study model to this project"),
	 * and the runtime treats the whole accordion item, header included, as the source of the swap.
	 */
	private function panel_of( array $service, int $project ): array {
		$chips = '<ul>' . implode( '', array_map( static fn( string $item ): string => '<li>' . esc_html( $item ) . '</li>', $service['deliverables'] ) ) . '</ul>';

		$settings = array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'flex_gap'       => Builder::gap( 'clamp(20px, 2.4vw, 28px)', null, 'custom' ),
			'padding'        => Builder::box( 0, 0, 4, 0 ),
		);

		if ( $project ) {
			$settings['fm_model_swap']    = 'yes';
			$settings['fm_model_swap_to'] = (string) $project;
		}

		$children = array(
			Style::stack(
				array(
					Style::label( 'Deliverables' ),
					Style::text(
						$chips,
						'body',
						'ink',
						array(
							'custom_css' => <<<'CSS'
							selector ul {
								display: flex;
								flex-wrap: wrap;
								gap: 8px;
								margin: 0;
								padding: 0;
								list-style: none;
							}
							/* A chip: the Chip colours at a size a phrase can be read at. */
							selector li {
								margin: 0;
								padding: 8px 14px;
								border-radius: var(--forma-r-pill);
								background-color: var(--forma-chip);
								color: var(--forma-chip-ink);
								font-size: 14px;
								font-weight: 500;
								line-height: 1.3;
							}
							CSS,
						)
					),
				),
				array( 'flex_gap' => Builder::gap( 12 ) )
			),
			Style::stack(
				array(
					Style::label( 'Typical timeline' ),
					$this->paragraph( $service['timeline'], 'ink', 48 ),
				),
				array( 'flex_gap' => Builder::gap( 8 ) )
			),
		);

		if ( $project ) {
			$children[] = Style::stack(
				array(
					Style::label( 'A typical project' ),
					Style::text_link( (string) get_the_title( $project ), (string) get_permalink( $project ) ),
				),
				array( 'flex_gap' => Builder::gap( 8 ) )
			);
		}

		return Builder::container( $settings, $children, true );
	}

	/** An item's header: the name in Heading, its line under it in Body. The plus turns to a minus when the item is open. */
	private function services_css(): string {
		return <<<'CSS'
		selector .e-n-accordion-item-title-text {
			display: flex;
			flex-direction: column;
			align-items: flex-start;
			gap: 10px;
		}
		selector .forma-acc__line {
			display: block;
			max-width: 52ch;
			color: var(--forma-muted);
			font-family: var(--forma-font-sans);
			font-size: 16px;
			font-weight: 400;
			letter-spacing: 0;
			line-height: 1.5;
		}
		CSS . $this->accordion_css();
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Ways to work with us.
	// ---------------------------------------------------------------------------------------------------------------

	/** Three ways to start, as Panel tiles: a Subheading-sized name and a paragraph. */
	private function ways(): array {
		$ways  = $this->site['ways'];
		$tiles = array();

		foreach ( $ways['items'] as $way ) {
			$tiles[] = $this->tile(
				array(
					Style::display( $way['name'], 'clamp(24px, 2.4vw, 34px)', 'h3' ),
					$this->paragraph( $way['text'], 'muted', 44 ),
				),
				array(
					'flex_gap' => Builder::gap( 'clamp(40px, 6vw, 96px)', null, 'custom' ),
					'padding'  => Builder::box( 'clamp(24px, 2.6vw, 36px)', 'clamp(24px, 2.6vw, 36px)', 'clamp(24px, 2.6vw, 36px)', 'clamp(24px, 2.6vw, 36px)', 'custom' ),
				)
			);
		}

		return $this->band(
			array(
				$this->heading( $ways['title'] ),
				$this->grid( $tiles, array( 3, 1, 1 ) ),
			),
			'clamp(8px, 1vw, 16px)',
			self::MID,
			array( 'flex_gap' => Builder::gap( 'clamp(24px, 3vw, 40px)', null, 'custom' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// FAQ.
	// ---------------------------------------------------------------------------------------------------------------

	/** The title at the left and the questions at the right, closed to begin with. */
	private function faq(): array {
		$items = array();

		foreach ( $this->site['faq'] as $entry ) {
			$items[] = array(
				'title'    => esc_html( $entry['q'] ),
				'id'       => '',
				'children' => array( $this->answer( $entry['a'] ) ),
			);
		}

		$accordion = $this->accordion(
			$items,
			array(
				'title_tag'   => 'h3',
				'custom_css'  => $this->accordion_css( 'center' ),
				'__globals__' => array( 'title_typography_typography' => Style::font( 'subheading' ) ),
			)
		);

		return $this->band(
			array(
				Style::row(
					array(
						Style::cell( array( $this->heading( 'Questions we hear often' ) ), 32, 100, 100 ),
						Style::cell( array( $accordion ), 62, 100, 100 ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'flex-start',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
					)
				),
			),
			'clamp(8px, 1vw, 16px)',
			'clamp(48px, 6vw, 96px)'
		);
	}

	private function answer( string $text ): array {
		return Builder::container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => Builder::gap( 0 ),
			),
			array( $this->paragraph( $text, 'muted', 62 ) ),
			true
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Shared accordion.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * A Nested Accordion: every item closed, one open at a time, a hairline above each item and under the last. Each
	 * item's children are its panel container. The widget is rendered live (no element cache), so the script that opens
	 * the item the address points at is always enqueued with it.
	 *
	 * @param array<int,array{title:string,id:string,children:array}> $items    The title is HTML that is already escaped.
	 * @param array                                                    $settings Settings for the widget.
	 */
	private function accordion( array $items, array $settings ): array {
		$repeater = array();
		$panels   = array();

		foreach ( $items as $item ) {
			$repeater[] = array(
				'_id'            => Builder::id(),
				'item_title'     => $item['title'],
				'element_css_id' => $item['id'],
			);

			array_push( $panels, ...$item['children'] );
		}

		$widget = Builder::widget(
			'nested-accordion',
			self::merge(
				array(
					'items'                                    => $repeater,
					'default_state'                            => 'all_collapsed',
					'max_items_expended'                       => 'one',
					'_element_cache'                           => 'disable',
					'accordion_item_title_position_horizontal' => 'stretch',
					'accordion_item_title_icon'                => array(
						'value'   => '',
						'library' => '',
					),
					'accordion_border_normal_border'           => 'solid',
					'accordion_border_normal_width'            => Builder::box( 1, 0, 0, 0 ),
					'accordion_padding'                        => Builder::box( 26, 0, 26, 0 ),
					'content_padding'                          => Builder::box( 0, 0, 26, 0 ),
					'__globals__'                              => array(
						'accordion_border_normal_color' => Style::color( 'line' ),
						'normal_title_color'            => Style::color( 'ink' ),
						'hover_title_color'             => Style::color( 'accent' ),
						'active_title_color'            => Style::color( 'ink' ),
					),
				),
				$settings
			)
		);

		$widget['elements'] = $panels;

		return $widget;
	}

	/**
	 * The plus in a Panel-coloured disc that turns to a minus when the item is open, the closing hairline, and room for
	 * the floating header when an item is the link target.
	 *
	 * @param string $align How the disc sits against the title: `flex-start` for a title with a line under it (it is level
	 *                      with the name), `center` for a one-line question.
	 */
	private function accordion_css( string $align = 'flex-start' ): string {
		return <<<CSS
		selector .e-n-accordion-item {
			scroll-margin-top: 120px;
		}
		selector .e-n-accordion-item:last-child {
			border-bottom: 1px solid var(--forma-line);
		}
		selector .e-n-accordion-item > .e-con {
			border: 0;
		}
		selector .e-n-accordion-item-title {
			align-items: {$align};
			gap: 24px;
			list-style: none;
		}
		selector .e-n-accordion-item-title::-webkit-details-marker {
			display: none;
		}
		selector .e-n-accordion-item-title::after {
			content: "+";
			display: grid;
			flex: none;
			place-items: center;
			width: 36px;
			height: 36px;
			border-radius: var(--forma-r-pill);
			background-color: var(--forma-raised);
			color: var(--forma-ink);
			font-family: var(--forma-font-sans);
			font-size: 22px;
			font-weight: 400;
			line-height: 1;
			transition: background-color 0.3s var(--forma-ease), color 0.3s var(--forma-ease);
		}
		selector .e-n-accordion-item-title:hover::after {
			background-color: var(--forma-ink);
			color: var(--forma-page);
		}
		selector .e-n-accordion-item[open] > .e-n-accordion-item-title::after {
			content: "\\2212";
		}
		CSS;
	}
}
