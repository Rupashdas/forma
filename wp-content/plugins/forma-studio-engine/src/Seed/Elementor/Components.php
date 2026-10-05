<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The saved components the pages are built from: the two project cards for the Loop Grid, the Closing CTA (an inset
 * Panel with the empty plot as a study model) and the Recognition ledger. Each is an Elementor library document, so it is edited once and updates wherever the
 * Template widget or the Loop Grid shows it.
 */
final class Components {

	/** Template key => Elementor document type and library title. */
	public const TEMPLATES = array(
		'project-card'      => array( 'loop-item', 'Project card' ),
		'project-card-wide' => array( 'loop-item', 'Project card wide' ),
		'closing-cta'       => array( 'container', 'Closing CTA' ),
		'recognition'       => array( 'container', 'Recognition ledger' ),
	);

	private array $site;

	private array $projects;

	public function __construct( private \Closure $log ) {
		$this->site     = require FORMA_ENGINE_PATH . 'data/site.php';
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
	}

	/**
	 * @return array<string,int> Post id by template key.
	 */
	public function build(): array {
		$ids = array();

		foreach ( self::TEMPLATES as $key => [ $type, $title ] ) {
			$ids[ $key ] = Templates::upsert( $key, $type, $title, $this->elements( $key ), $this->settings( $key ) );

			( $this->log )( "Component {$key}: " . ( Templates::saved() ? "{$title} (#{$ids[ $key ]})." : Builder::SKIPPED ) );
		}

		return $ids;
	}

	/**
	 * @throws \InvalidArgumentException For a key that is not in {@see self::TEMPLATES}.
	 */
	public function elements( string $key ): array {
		Builder::reset( "component:{$key}" );

		return match ( $key ) {
			'project-card'      => array( $this->card() ),
			'project-card-wide' => array( $this->card_wide() ),
			'closing-cta'       => array( $this->closing_cta() ),
			'recognition'       => array( $this->recognition() ),
			default             => throw new \InvalidArgumentException( esc_html( "Unknown component '{$key}'." ) ),
		};
	}

	/** Document settings: a loop item previews itself with a real project. */
	public function settings( string $key ): array {
		if ( 'loop-item' !== ( self::TEMPLATES[ $key ][0] ?? '' ) ) {
			return array();
		}

		return array(
			'preview_type' => 'single/forma_project',
			'preview_id'   => (string) ( new Content( $this->log ) )->project_id( 'casa-nera' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Project cards.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Portrait card: a rounded tile holding a 4:5 image (with the excerpt floating over it on hover), the sheet number
	 * as a chip, the title, and the location and the year as separate Meta elements.
	 */
	private function card(): array {
		return $this->card_shell(
			'forma-card',
			array(
				$this->card_media( 'forma-960' ),
				$this->card_text( 'subheading' ),
			),
			$this->card_css( '4 / 5', '4 / 5' ),
			Builder::gap( 14 )
		);
	}

	/**
	 * Landscape card for a wide feature: a 16:9 image (3:2 on mobile) over the same text as the portrait card, with
	 * the Heading type for the title.
	 */
	private function card_wide(): array {
		return $this->card_shell(
			'forma-card forma-card--wide',
			array(
				$this->card_media( 'large' ),
				$this->card_text( 'heading' ),
			),
			$this->card_css( '16 / 9', '3 / 2' ),
			Builder::gap( 18 )
		);
	}

	/**
	 * The card itself is one link to the project (a dynamic Post URL on a container rendered as `a`), so nothing
	 * inside it may be a link of its own. It is a rounded tile: a Panel fill (an Ink tile inside the Ink band, where
	 * the theme re-points the Panel global), the tile radius and 12px of padding. It carries the project's id in
	 * `data-model-swap` (Forma Motion), so a study model that swaps follows the card.
	 */
	private function card_shell( string $classes, array $children, string $css, array $gap ): array {
		return Builder::container(
			array(
				'content_width'         => 'full',
				'html_tag'              => 'a',
				'link'                  => Style::link(),
				'flex_direction'        => 'column',
				'flex_gap'              => $gap,
				'padding'               => Builder::box( 12 ),
				'border_radius'         => Builder::box( 'var(--forma-r-tile)', null, null, null, 'custom' ),
				'background_background' => 'classic',
				'css_classes'           => $classes,
				'fm_cursor'             => 'View',
				'fm_model_swap'         => 'yes',
				'custom_css'            => $css,
				'__dynamic__'           => array( 'link' => Builder::tag( 'post-url' ) ),
				'__globals__'           => array( 'background_color' => Style::color( 'raised' ) ),
			),
			$children
		);
	}

	/** The image and, over it, the excerpt: the excerpt shows as a glass card on hover (see {@see self::card_css()}). */
	private function card_media( string $size ): array {
		return Style::stack(
			array(
				$this->card_image( $size ),
				$this->card_excerpt(),
			),
			array( 'css_classes' => 'forma-card__media' )
		);
	}

	/**
	 * The project's featured image, carrying the view-transition name that lets it morph into the project hero.
	 *
	 * The Theme Builder widgets only fall back to their dynamic tag in the editor's panel; a saved document has to
	 * bind it explicitly, as the editor writes it when the widget is dropped in.
	 */
	private function card_image( string $size ): array {
		return Builder::widget(
			'theme-post-featured-image',
			array(
				'image_size'   => $size,
				'fm_shared'    => 'yes',
				'_css_classes' => 'forma-card__image',
				'__dynamic__'  => array( 'image' => Builder::tag( 'post-featured-image' ) ),
			)
		);
	}

	/** "No. 08": the project's sheet number, from the Forma dynamic tag, as a chip. */
	private function card_number(): array {
		return Style::chip(
			'No. 00',
			array( '__dynamic__' => array( 'title' => Builder::tag( 'forma-project-number', array( 'prefix' => 'No. ' ) ) ) )
		);
	}

	/** The title on a Kit font: Subheading on the portrait card, Heading on the wide one. */
	private function card_title( string $font ): array {
		return Builder::widget(
			'theme-post-title',
			array(
				'header_size' => 'h3',
				'__dynamic__' => array( 'title' => Builder::tag( 'post-title' ) ),
				'__globals__' => array(
					'typography_typography' => Style::font( $font ),
					'title_color'           => Style::color( 'ink' ),
				),
			)
		);
	}

	/** The chip, the title, and a row with the location and the year as separate Meta elements. */
	private function card_text( string $font ): array {
		return Style::stack(
			array(
				$this->card_number(),
				$this->card_title( $font ),
				$this->meta_row(),
			),
			array(
				'flex_gap' => Builder::gap( 8 ),
				'padding'  => Builder::box( 0, 6, 6, 6 ),
			)
		);
	}

	/**
	 * The location on the left and the year on the right, on one baseline (the control only offers start, center,
	 * end and stretch, so the alignment is set in the row's Custom CSS). The location may wrap under a year that never
	 * does.
	 */
	private function meta_row(): array {
		return Style::row(
			array(
				Style::cell( array( $this->card_location() ), 'auto', 'auto', 'auto' ),
				Style::cell( array( $this->card_year() ), 'auto', 'auto', 'auto' ),
			),
			array(
				'flex_wrap'            => 'nowrap',
				'flex_justify_content' => 'space-between',
				'flex_gap'             => Builder::gap( 16 ),
				'custom_css'           => 'selector { align-items: baseline; }',
			)
		);
	}

	/** The project's location, as plain text: the card is already a link, so the term must not be one. */
	private function card_location(): array {
		return Style::heading(
			'Location',
			'meta',
			'p',
			'muted',
			array(
				'__dynamic__' => array(
					'title' => Builder::tag(
						'post-terms',
						array(
							'taxonomy'  => 'project_location',
							'separator' => ', ',
							'link'      => '',
						)
					),
				),
			)
		);
	}

	/** The year the project was published, from the post date. */
	private function card_year(): array {
		return Style::heading(
			'2024',
			'meta',
			'p',
			'muted',
			array(
				'__dynamic__' => array(
					'title' => Builder::tag(
						'post-date',
						array(
							'format'        => 'custom',
							'custom_format' => 'Y',
						)
					),
				),
			)
		);
	}

	private function card_excerpt(): array {
		return Builder::widget(
			'theme-post-excerpt',
			array(
				'_css_classes' => 'forma-card__excerpt',
				'__dynamic__'  => array( 'excerpt' => Builder::tag( 'post-excerpt' ) ),
				'__globals__'  => array( 'typography_typography' => Style::font( 'meta' ) ),
			)
		);
	}

	/**
	 * The card's behaviour, kept on the card so it stays editable in Elementor: the image crops to a fixed ratio inside
	 * its rounded frame and grows 3% on hover; on devices that can hover, the excerpt floats over the bottom of the
	 * image as a glass card (Paper at 78% over a blur, always dark text, so it reads on a Panel tile and on an Ink
	 * tile alike) and fades in on hover or keyboard focus. Touch devices never show it.
	 *
	 * @param string $ratio        Image aspect ratio, e.g. `4 / 5`.
	 * @param string $mobile_ratio Image aspect ratio on mobile.
	 */
	private function card_css( string $ratio, string $mobile_ratio ): string {
		return <<<CSS
		selector .forma-card__media {
			position: relative;
			overflow: hidden;
			border-radius: var(--forma-r-img);
		}
		selector .forma-card__image img {
			display: block;
			width: 100%;
			aspect-ratio: {$ratio};
			object-fit: cover;
			transition: scale 0.9s var(--forma-ease);
		}
		@media (max-width: 767px) {
			selector .forma-card__image img {
				aspect-ratio: {$mobile_ratio};
			}
		}
		/*
		 * No photograph yet: Elementor leaves the image widget out of the page altogether, so the media frame holds only
		 * the excerpt. It becomes a quiet block (a tint of the text colour, which reads on a Panel tile and on an Ink tile
		 * alike) with the chip "Study model" in its middle.
		 */
		selector .forma-card__media:not(:has(img)) {
			aspect-ratio: {$ratio};
			background-color: rgba(20, 20, 20, 0.06);
			background-color: color-mix(in srgb, var(--forma-ink) 7%, transparent);
			display: grid;
			place-items: center;
		}
		selector .forma-card__media:not(:has(img))::after {
			background-color: var(--forma-chip);
			border-radius: 999px;
			color: var(--forma-chip-ink);
			content: "Study model";
			font: 600 12px/1 var(--forma-font-sans);
			padding: 5px 10px;
		}
		@media (max-width: 767px) {
			selector .forma-card__media:not(:has(img)) {
				aspect-ratio: {$mobile_ratio};
			}
		}
		selector .forma-card__excerpt {
			position: absolute;
			inset: auto 10px 10px;
			padding: 12px 14px;
			border-radius: calc(var(--forma-r-img) - 2px);
			background-color: rgba(246, 245, 241, 0.78);
			background-color: color-mix(in srgb, var(--forma-page) 78%, transparent);
			-webkit-backdrop-filter: blur(12px);
			backdrop-filter: blur(12px);
			color: var(--forma-deep);
		}
		@media (hover: hover) {
			selector:hover .forma-card__image img,
			selector:focus-visible .forma-card__image img {
				scale: 1.03;
			}
			selector .forma-card__excerpt {
				opacity: 0;
				transform: translateY(10px);
				transition: opacity 0.5s var(--forma-ease), transform 0.7s var(--forma-ease);
			}
			selector:hover .forma-card__excerpt,
			selector:focus-visible .forma-card__excerpt {
				opacity: 1;
				transform: none;
			}
		}
		@media (hover: none) {
			selector .forma-card__excerpt {
				display: none;
			}
		}
		CSS;
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Closing CTA.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The invitation that closes Home and the inner pages: an inset Panel whose content sits in the boxed 1320px
	 * column, in two columns from 1024px. On the left a
	 * Display L headline, an Ink pill to Contact (magnetic) and the email as a text link; on the right the empty plot,
	 * a study model with its dashed blue volume that the visitor can turn. The columns stack on tablet and mobile.
	 */
	private function closing_cta(): array {
		$content = new Content( $this->log );
		$contact = $content->page_id( 'contact' );
		$url     = $contact ? (string) get_permalink( $contact ) : home_url( '/contact/' );
		$email   = $this->site['studio']['email'];

		return Style::section(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( 'Your project goes here.', 'display-l', 'h2' ),
								Style::button(
									'Start a conversation',
									$url,
									array(
										'link' => array( 'custom_attributes' => 'data-magnetic|true' ) + Style::link( $url ),
									)
								),
								Style::text_link( $email, 'mailto:' . $email ),
							),
							44,
							100,
							100,
							array(
								'flex_gap'         => Builder::gap( 'clamp(20px, 2.4vw, 32px)', null, 'custom' ),
								'flex_align_items' => 'flex-start',
							)
						),
						Style::cell(
							array(
								Builder::widget(
									'forma-study-model',
									array(
										'source'             => 'recipe',
										'recipe'             => 'plot',
										'camera'             => 'three-quarter',
										'view_height'        => Builder::size( 70, 'vh' ),
										'view_height_tablet' => Builder::size( 52, 'vh' ),
										'view_height_mobile' => Builder::size( 46, 'vh' ),
										'drag'               => 'yes',
									)
								),
							),
							56,
							100,
							100
						),
					),
					array(
						'flex_wrap'              => 'nowrap',
						'flex_align_items'       => 'center',
						'flex_direction_tablet'  => 'column',
						'flex_gap'               => Builder::gap( 'clamp(24px, 4vw, 64px)', null, 'custom' ),
					)
				),
			),
			'raised',
			array( 'padding' => Builder::box( 'clamp(48px, 6vw, 96px)', Style::PANEL_GUTTER, 'clamp(24px, 3vw, 48px)', Style::PANEL_GUTTER, 'custom' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Recognition ledger.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Awards, newest first: an inset Panel with the heading and a Line grid of rows, one per recognised project, the
	 * year as a chip, the award, then the project. Home shows the awards as a ticker instead; this is for the Studio page.
	 */
	private function recognition(): array {
		$rows = array();

		foreach ( $this->awards() as $award ) {
			$rows[] = $this->ledger_row( $award );
		}

		return Style::section(
			array(
				Style::heading( 'Recognition', 'heading', 'h2', 'ink' ),
				Style::stack(
					$rows,
					array(
						'margin'        => Builder::box( 'clamp(24px, 3vw, 48px)', 0, 0, 0, 'custom' ),
						'border_border' => 'solid',
						'border_width'  => Builder::box( 0, 0, 1, 0 ),
						'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
					)
				),
			),
			'raised',
			array(
				'flex_gap'   => Builder::gap( 16 ),
				'custom_css' => <<<'CSS'
				/* The cells are ruled off from each other from tablet up, so the rows read as a grid. */
				@media (min-width: 768px) {
					selector .forma-ledger__row > .e-con + .e-con {
						border-left: 1px solid var(--forma-line);
						padding-left: clamp(16px, 2vw, 32px);
					}
				}
				/* A row nudges in on hover or focus, like a line being picked out on a drawing register. */
				selector .forma-ledger__row {
					transition: padding-inline-start 0.6s var(--forma-ease);
				}
				selector .forma-ledger__row:hover,
				selector .forma-ledger__row:focus-visible {
					padding-inline-start: 16px;
				}
				CSS,
			)
		);
	}

	/**
	 * Projects with a recognition line, newest first. The line ends in its year ("Winner, Hospitality Design Award
	 * 2025"); the award is the line without it.
	 *
	 * @return array<int,array{year:string,award:string,title:string,url:string}>
	 */
	public function awards(): array {
		$content = new Content( $this->log );
		$awards  = array();

		// The data lists projects oldest first; reversed, a stable sort by year keeps later projects first within a year.
		foreach ( array_reverse( $this->projects ) as $project ) {
			if ( ! preg_match( '/^(.*\S)\s+(\d{4})$/u', trim( (string) $project['recognition'] ), $match ) ) {
				continue;
			}

			$id = $content->project_id( $project['slug'] );

			$awards[] = array(
				'year'  => $match[2],
				'award' => $match[1],
				'title' => $project['title'],
				'url'   => $id ? (string) get_permalink( $id ) : home_url( '/projects/' . $project['slug'] . '/' ),
			);
		}

		usort( $awards, static fn( array $a, array $b ): int => strcmp( $b['year'], $a['year'] ) );

		return $awards;
	}

	/**
	 * One ledger row: year, award and project in three columns. On mobile the year and project share the first line
	 * and the award drops below them.
	 */
	private function ledger_row( array $award ): array {
		return Style::row(
			array(
				Style::cell( array( Style::chip( $award['year'] ) ), 12, 14, 'auto' ),
				Style::cell(
					array( Style::heading( $award['award'], 'subheading', 'p', 'ink' ) ),
					54,
					52,
					100,
					array( '_flex_order_mobile' => 'end' )
				),
				Style::cell(
					array( Style::heading( $award['title'], 'meta', 'p', 'muted', array( 'align' => 'right' ) ) ),
					30,
					30,
					'auto'
				),
			),
			array(
				'html_tag'             => 'a',
				'link'                 => Style::link( $award['url'] ),
				'css_classes'          => 'forma-ledger__row',
				'flex_wrap'            => 'nowrap',
				'flex_wrap_mobile'     => 'wrap',
				'flex_justify_content' => 'space-between',
				'flex_align_items'     => 'center',
				'flex_gap'             => Builder::gap( 8, 24 ),
				'padding'              => Builder::box( 22, 0, 22, 0 ),
				'border_border'        => 'solid',
				'border_width'         => Builder::box( 1, 0, 0, 0 ),
				'__globals__'          => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}
}
