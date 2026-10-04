<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;
use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The single project template (spec 7.3), a Theme Builder template for every project. A hairline title block (number,
 * type, location, year), the title as the H1 with the excerpt as its standfirst, the featured image as the hero, then
 * the project's own Elementor body through Post Content, the Next Project link and the Closing CTA. Every value is a
 * dynamic tag, so one template serves all projects.
 *
 * The hero is a full-width band of its own, edge to edge. It carries the Forma Motion transition name, so the image
 * morphs from the project card, and a wipe for when the page is opened directly. Everything else is boxed.
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
			$this->opening(),
			$this->hero(),
			$this->body(),
			$this->next(),
			$this->component( 'closing-cta' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Opening: title block, title, standfirst, hero.
	// ---------------------------------------------------------------------------------------------------------------

	/** The title block, title and standfirst under the fixed header, boxed. The hero follows as a band of its own. */
	private function opening(): array {
		return Style::section(
			array(
				$this->title_block(),
				$this->heading(),
			),
			'page',
			array(
				'padding'  => Builder::box( 'clamp(112px, 14vw, 168px)', 'var(--forma-gutter)', 'clamp(40px, 5vw, 72px)', 'var(--forma-gutter)', 'custom' ),
				'flex_gap' => Builder::gap( 'clamp(32px, 5vw, 72px)', null, 'custom' ),
			)
		);
	}

	/**
	 * No., Type, Location and Year as four hairline cells: a small label over the value in the display serif. Four
	 * across down to tablet; two by two on mobile.
	 */
	private function title_block(): array {
		$cells = array(
			'No.'      => array( 'forma-project-number', array( 'prefix' => '' ) ),
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

		$row   = array();
		$index = 0;

		foreach ( $cells as $label => [ $tag, $settings ] ) {
			$row[] = Style::cell(
				array(
					Style::label( $label ),
					Style::serif(
						'—',
						'clamp(22px, 2.4vw, 34px)',
						'p',
						'ink',
						array( '__dynamic__' => array( 'title' => Builder::tag( $tag, $settings ) ) )
					),
				),
				25,
				25,
				50,
				array(
					'flex_gap'       => Builder::gap( 6 ),
					'padding'        => Builder::box( 18, 20, 20, 0 === $index ? 0 : 20 ),
					'padding_mobile' => Builder::box( 14, 12, 16, 0 === $index % 2 ? 0 : 12 ),
					'border_border'  => 'solid',
					'border_width'   => Builder::box( 0, 0, 0, 0 === $index ? 0 : 1 ),
					// Two by two on mobile: every cell carries a top hairline, the right-hand ones a left one.
					'border_width_mobile' => Builder::box( 1, 0, 0, 0 === $index % 2 ? 0 : 1 ),
					'__globals__'    => array( 'border_color' => Style::color( 'line' ) ),
				)
			);
			++$index;
		}

		return Style::row(
			$row,
			array(
				'border_border'        => 'solid',
				'border_width'         => Builder::box( 1, 0, 1, 0 ),
				'border_width_mobile'  => Builder::box( 0, 0, 1, 0 ),
				'__globals__'          => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}

	/** The project name as the page's H1 in Display L, with the excerpt as the standfirst in the right two thirds. */
	private function heading(): array {
		return Style::stack(
			array(
				Builder::widget(
					'theme-post-title',
					array(
						'header_size' => 'h1',
						'__dynamic__' => array( 'title' => Builder::tag( 'post-title' ) ),
						'__globals__' => array(
							'typography_typography' => Style::font( 'display-l' ),
							'title_color'           => Style::color( 'ink' ),
						),
					)
				),
				Style::row(
					array(
						Style::cell(
							array(
								Builder::widget(
									'theme-post-excerpt',
									array(
										'__dynamic__' => array( 'excerpt' => Builder::tag( 'post-excerpt' ) ),
										'__globals__' => array(
											'typography_typography' => Style::font( 'statement' ),
											'title_color'           => Style::color( 'ink' ),
										),
									)
								),
							),
							66,
							100,
							100
						),
					),
					array( 'flex_justify_content' => 'flex-end' )
				),
			),
			array( 'flex_gap' => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ) )
		);
	}

	/**
	 * The featured image as a band of its own, edge to edge: 80vh tall on desktop, 3:2 on tablet, 4:5 on mobile.
	 * `fm_shared` gives it the view-transition name that matches the project card, so it morphs into place; the wipe
	 * plays when the page is opened directly. A project without a photograph prints nothing in the band.
	 */
	private function hero(): array {
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
			),
			'page',
			array( 'padding' => Builder::box( 0 ) ),
			'full'
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Body, next project, closing.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The project's own Elementor body. It is made of its own bands (some on Raised or bottle green), so it sits in a
	 * plain full-width wrapper with no padding and lets those bands run edge to edge behind their boxed content.
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

	/** The next project by completion date, as one large link inside the content width. */
	private function next(): array {
		return Style::section(
			array(
				Builder::widget(
					'forma-next-project',
					array(
						'custom_css' => <<<'CSS'
						/* The link's text is Chalk over a photograph. While the next project has no photograph yet, it sits on the bottle green instead. */
						selector .forma-next {
							background-color: var(--forma-deep);
						}
						selector .forma-next__media:not(:has(img))::after {
							background: none;
						}
						CSS,
					)
				),
			),
			'page',
			array( 'padding' => Builder::box( 'clamp(32px, 4vw, 64px)', 'var(--forma-gutter)', 'clamp(48px, 6vw, 96px)', 'var(--forma-gutter)', 'custom' ) )
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
