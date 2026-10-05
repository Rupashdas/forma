<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;
use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The single project template (spec 5.2), a Theme Builder template for every project, in five parts:
 *
 * 1. Model hero: an inset Panel with the copy at the left (the project number as a chip, the title as the page's H1,
 *    the excerpt and a short Type, Location and Year list) and the project's own study model at the right, which
 *    assembles on load and can be turned, opened up and read by its callouts.
 * 2. Built: the featured photograph in an inset rounded panel, with a "Built {year}" chip. A project without a photograph
 *    prints nothing and the panel hides itself.
 * 3. The project's own Elementor body through Post Content (model, facts, story, photographs, result).
 * 4. Next project: an inset Ink band with one rounded Ink tile, the Forma Next Project widget.
 * 5. The Closing CTA component.
 *
 * Every value is a dynamic tag, so one template serves all projects. The Built image carries the Forma Motion
 * transition name, so it morphs from the project card, and a wipe for when the page is opened directly.
 */
final class Single {

	public const KEY = 'single-project';

	private Content $content;

	public function __construct( private \Closure $log ) {
		$this->content = new Content( $log );
	}

	/**
	 * @return int The single template's post id.
	 * @throws \RuntimeException When the Closing CTA component has not been seeded yet.
	 */
	public function build(): int {
		if ( ! Templates::id( 'closing-cta' ) ) {
			throw new \RuntimeException( 'The closing-cta component does not exist: run `wp forma design --only=components` first.' );
		}

		$id = Templates::upsert( self::KEY, 'single-post', 'Single project', $this->elements(), $this->settings(), array( array( 'include', 'singular', Projects::POST_TYPE ) ) );

		( $this->log )( 'Single project: ' . ( Templates::saved() ? "all projects (#{$id})." : Builder::SKIPPED ) );

		return $id;
	}

	/** Document settings: the editor previews the template on Casa Nera. */
	public function settings(): array {
		return array(
			'preview_type' => 'single/' . Projects::POST_TYPE,
			'preview_id'   => (string) $this->content->project_id( 'casa-nera' ),
		);
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			$this->hero(),
			$this->built(),
			$this->body(),
			$this->next(),
			$this->component( 'closing-cta' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Model hero.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Panel at least 92vh tall whose content sits in the boxed 1320px column, so on a wide screen it lines up
	 * with the header's pills. From 1024px the copy is the left 44% of the column and the model the right 56% (80vh
	 * tall), centred on each other; below that they stack, copy first. The header floats over the panel, so the copy's
	 * top padding clears it (the panel itself has none).
	 */
	private function hero(): array {
		return Style::section(
			array(
				$this->hero_copy(),
				$this->hero_model(),
			),
			'raised',
			array(
				'min_height'                => Builder::size( 92, 'vh' ),
				'min_height_tablet'         => Builder::size( 0 ),
				'flex_direction'            => 'row',
				'flex_direction_tablet'     => 'column',
				'flex_wrap'                 => 'nowrap',
				'flex_justify_content'      => 'center',
				'flex_align_items'          => 'center',
				'flex_align_items_tablet'   => 'stretch',
				'flex_gap'                  => Builder::gap( 'clamp(8px, 2vw, 32px)', null, 'custom' ),
				'padding'                   => Builder::box( 0, Style::PANEL_GUTTER, 0, Style::PANEL_GUTTER, 'custom' ),
				'css_classes'               => 'forma-hero',
			)
		);
	}

	/**
	 * The project number as a chip, the title (the page's one H1, Display L), the excerpt and the Type, Location and
	 * Year list.
	 */
	private function hero_copy(): array {
		return Style::stack(
			array(
				Style::chip(
					'No. 00',
					array( '__dynamic__' => array( 'title' => Builder::tag( 'forma-project-number', array( 'prefix' => 'No. ' ) ) ) )
				),
				Builder::widget(
					'theme-post-title',
					array(
						'header_size' => 'h1',
						'fm_entrance' => 'lines',
						'__dynamic__' => array( 'title' => Builder::tag( 'post-title' ) ),
						'__globals__' => array(
							'typography_typography' => Style::font( 'display-l' ),
							'title_color'           => Style::color( 'ink' ),
						),
					)
				),
				Builder::widget(
					'theme-post-excerpt',
					array(
						'custom_css'  => 'selector p { max-width: 40ch; margin: 0; }',
						'__dynamic__' => array( 'excerpt' => Builder::tag( 'post-excerpt' ) ),
						'__globals__' => array(
							'typography_typography' => Style::font( 'body' ),
							'title_color'           => Style::color( 'muted' ),
						),
					)
				),
				$this->meta_list(),
			),
			array(
				'width'          => Builder::size( 44, '%' ),
				'width_tablet'   => Builder::size( 100, '%' ),
				'width_mobile'   => Builder::size( 100, '%' ),
				'flex_gap'       => Builder::gap( 'clamp(16px, 2vw, 24px)', null, 'custom' ),
				'padding'        => Builder::box( 'clamp(104px, 11vw, 150px)', 0, 'clamp(48px, 6vw, 88px)', 0, 'custom' ),
				'padding_tablet' => Builder::box( 104, 0, 16, 0 ),
				'css_classes'    => 'forma-hero__copy',
			)
		);
	}

	/**
	 * Type, Location and Year as three rows of a Label (Graphite) and its value (Body), each under a hairline. Every
	 * value is a dynamic tag.
	 */
	private function meta_list(): array {
		$rows = array(
			'Type'     => array(
				'post-terms',
				array(
					'taxonomy'  => Projects::TYPE_TAX,
					'separator' => ', ',
					'link'      => 'yes',
				),
			),
			'Location' => array(
				'post-terms',
				array(
					'taxonomy'  => Projects::LOCATION_TAX,
					'separator' => ', ',
					'link'      => '',
				),
			),
			'Year'     => array(
				'post-date',
				array(
					'format'        => 'custom',
					'custom_format' => 'Y',
				),
			),
		);

		$list = array();

		foreach ( $rows as $label => [ $tag, $settings ] ) {
			$list[] = Style::row(
				array(
					Style::cell( array( Style::label( $label ) ), 28, 28, 30 ),
					Style::cell(
						array(
							Style::heading(
								'—',
								'body',
								'p',
								'ink',
								array( '__dynamic__' => array( 'title' => Builder::tag( $tag, $settings ) ) )
							),
						),
						72,
						72,
						70
					),
				),
				array(
					'flex_wrap'        => 'nowrap',
					'flex_align_items' => 'baseline',
					'flex_gap'         => Builder::gap( 12 ),
					'padding'          => Builder::box( 10, 0, 10, 0 ),
					'border_border'    => 'solid',
					'border_width'     => Builder::box( 1, 0, 0, 0 ),
					'__globals__'      => array( 'border_color' => Style::color( 'line' ) ),
					'custom_css'       => 'selector { align-items: baseline; }',
				)
			);
		}

		return Style::stack(
			$list,
			array(
				'css_classes' => 'forma-hero__meta',
				'custom_css'  => 'selector { max-width: 380px; }',
			)
		);
	}

	/**
	 * This project's study model: it assembles on load, turns by dragging or with the arrow keys, opens up with the
	 * Exploded view button, and names its parts as callouts. 80vh tall from 1024px, shorter when stacked. The model is
	 * the one in the body's own widget, which is where it is edited.
	 */
	private function hero_model(): array {
		return Builder::widget(
			'forma-study-model',
			array(
				'source'             => 'current',
				'camera'             => 'hero',
				'view_height'        => Builder::size( 80, 'vh' ),
				'view_height_tablet' => Builder::size( 56, 'vh' ),
				'view_height_mobile' => Builder::size( 52, 'vh' ),
				'assemble'           => 'yes',
				'drag'               => 'yes',
				'keyboard'           => 'yes',
				'explode_toggle'     => 'yes',
				'callouts'           => 'yes',
				'custom_css'         => <<<'CSS'
				@media (min-width: 1024px) {
					selector.elementor-widget {
						flex: none;
						width: 56%;
					}
				}
				CSS,
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. Built.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The featured image as an inset rounded panel, running the width of the page inside the inset: 80vh tall on
	 * desktop, 3:2 on tablet, 4:5 on mobile, with a "Built {year}" chip over its bottom left corner. `fm_shared` gives
	 * the image the view-transition name that matches the project card, so it morphs into place; the wipe plays when the
	 * page is opened directly. A project without a photograph prints nothing in the panel, and the panel hides itself.
	 */
	private function built(): array {
		return Style::section(
			array(
				Builder::widget(
					'theme-post-featured-image',
					array(
						'image_size'  => 'full',
						'fm_shared'   => 'yes',
						'fm_entrance' => 'clip-up',
						'custom_css'  => <<<'CSS'
						selector img {
							display: block;
							width: 100%;
							height: 80vh;
							min-height: 480px;
							object-fit: cover;
						}
						@media (max-width: 1023px) {
							selector img {
								height: auto;
								min-height: 0;
								aspect-ratio: 3 / 2;
							}
						}
						@media (max-width: 767px) {
							selector img {
								aspect-ratio: 4 / 5;
							}
						}
						CSS,
						'__dynamic__' => array( 'image' => Builder::tag( 'post-featured-image' ) ),
					)
				),
				Style::chip(
					'Built 2024',
					array(
						'custom_css'  => <<<'CSS'
						selector {
							position: absolute;
							left: clamp(14px, 2vw, 28px);
							bottom: clamp(14px, 2vw, 28px);
							z-index: 2;
						}
						CSS,
						'__dynamic__' => array(
							'title' => Builder::tag(
								'post-date',
								array(
									'format'        => 'custom',
									'custom_format' => 'Y',
									'before'        => 'Built ',
								)
							),
						),
					)
				),
			),
			'raised',
			array(
				'padding'     => Builder::box( 0 ),
				'css_classes' => 'forma-built',
				'custom_css'  => <<<'CSS'
				selector {
					position: relative;
				}
				/* No photograph yet: the image widget prints nothing, so there is no panel to show. */
				selector:not(:has(img)) {
					display: none !important;
				}
				CSS,
			),
			'full'
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 3. Body, 4. next project, 5. closing.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The project's own Elementor body. It is made of its own sections (inset Panels and boxed content), so it sits in a
	 * plain full-width wrapper with no padding and lets those panels float on Paper.
	 */
	private function body(): array {
		return Builder::container(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => Builder::gap( 0 ),
				'padding'        => Builder::box( 0 ),
			),
			array(
				Builder::widget( 'theme-post-content', array() ),
			)
		);
	}

	/**
	 * The next project by completion date, as one rounded Ink tile in an inset Ink band, inside the content width. The
	 * widget carries the cursor label "Next" and the next project's transition name; the tile's look is its stylesheet's.
	 */
	private function next(): array {
		return Style::section(
			array(
				Builder::widget( 'forma-next-project', array() ),
			),
			'deep',
			array( 'padding' => Builder::box( 'clamp(24px, 4vw, 64px)', Style::PANEL_GUTTER, 'clamp(24px, 4vw, 64px)', Style::PANEL_GUTTER, 'custom' ) )
		);
	}

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
