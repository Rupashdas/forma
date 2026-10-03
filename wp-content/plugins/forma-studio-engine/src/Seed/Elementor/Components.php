<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The saved components Home (and later Studio) is built from: the two project cards for the Loop Grid, the Closing CTA
 * and the Recognition ledger. Each is an Elementor library document, so it is edited once and updates wherever the
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

	/** Portrait card: 4:5 image, number, title, meta, and an excerpt that appears on hover. */
	private function card(): array {
		return $this->card_shell(
			'forma-card',
			array(
				$this->card_image( 'forma-960', array( 'fm_entrance' => 'clip-up' ) ),
				$this->card_number(),
				$this->card_title( 'clamp(26px, 2.4vw, 36px)' ),
				$this->card_meta(),
				$this->card_excerpt(),
			),
			$this->card_css( '4 / 5', '4 / 5' ),
			Builder::gap( 12 )
		);
	}

	/**
	 * Landscape card for a wide feature: 16:9 image (3:2 on mobile) that drifts as it scrolls, with the number and
	 * title on the left of the text row and the meta and excerpt on the right (stacked on mobile).
	 */
	private function card_wide(): array {
		$text = Style::row(
			array(
				Style::cell(
					array( $this->card_number(), $this->card_title( 'clamp(32px, 4vw, 64px)' ) ),
					58,
					55,
					100,
					array( 'flex_gap' => Builder::gap( 10 ) )
				),
				Style::cell(
					array( $this->card_meta(), $this->card_excerpt() ),
					34,
					40,
					100,
					array( 'flex_gap' => Builder::gap( 10 ) )
				),
			),
			array(
				'flex_wrap'               => 'nowrap',
				'flex_justify_content'    => 'space-between',
				'flex_align_items'        => 'flex-start',
				'flex_gap'                => Builder::gap( 24 ),
				'flex_direction_mobile'   => 'column',
				'flex_align_items_mobile' => 'stretch',
			)
		);

		return $this->card_shell(
			'forma-card forma-card--wide',
			array(
				// The parallax effect drifts the photo inside its frame, so the frame crops at a fixed ratio.
				$this->card_image( 'large', array( 'fm_scroll' => 'parallax', 'fm_speed' => 10 ) ),
				$text,
			),
			$this->card_css( '16 / 9', '3 / 2' ),
			Builder::gap( 20 )
		);
	}

	/**
	 * The card itself is one link to the project (a dynamic Post URL on a container rendered as `a`), so nothing
	 * inside it may be a link of its own.
	 */
	private function card_shell( string $classes, array $children, string $css, array $gap ): array {
		return Builder::container(
			array(
				'content_width'  => 'full',
				'html_tag'       => 'a',
				'link'           => Style::link(),
				'flex_direction' => 'column',
				'flex_gap'       => $gap,
				'css_classes'    => $classes,
				'fm_cursor'      => 'View',
				'custom_css'     => $css,
				'__dynamic__'    => array( 'link' => Builder::tag( 'post-url' ) ),
			),
			$children
		);
	}

	/**
	 * The project's featured image, carrying the view-transition name that lets it morph into the project hero.
	 *
	 * The Theme Builder widgets only fall back to their dynamic tag in the editor's panel; a saved document has to
	 * bind it explicitly, as the editor writes it when the widget is dropped in.
	 */
	private function card_image( string $size, array $extra ): array {
		return Builder::widget(
			'theme-post-featured-image',
			$extra + array(
				'image_size'   => $size,
				'fm_shared'    => 'yes',
				'_css_classes' => 'forma-card__image',
				'__dynamic__'  => array( 'image' => Builder::tag( 'post-featured-image' ) ),
			)
		);
	}

	/** "No. 08": the project's sheet number, from the Forma dynamic tag. */
	private function card_number(): array {
		return Style::label(
			'No. 00',
			'accent',
			array( '__dynamic__' => array( 'title' => Builder::tag( 'forma-project-number', array( 'prefix' => 'No. ' ) ) ) )
		);
	}

	private function card_title( string $size ): array {
		return Builder::widget(
			'theme-post-title',
			Style::serif_type( $size ) + array(
				'header_size' => 'h3',
				'__dynamic__' => array( 'title' => Builder::tag( 'post-title' ) ),
				'__globals__' => array( 'title_color' => Style::color( 'ink' ) ),
			)
		);
	}

	/** Type · Location · Year, as plain text (the card is already a link). */
	private function card_meta(): array {
		$item = static fn( array $settings ): array => $settings + array(
			'_id'       => Builder::id(),
			'link'      => '',
			'show_icon' => 'none',
		);

		return Builder::widget(
			'post-info',
			array(
				'view'          => 'inline',
				'icon_list'     => array(
					$item(
						array(
							'type'     => 'terms',
							'taxonomy' => 'project_type',
						)
					),
					$item(
						array(
							'type'     => 'terms',
							'taxonomy' => 'project_location',
						)
					),
					$item(
						array(
							'type'               => 'date',
							'date_format'        => 'custom',
							'custom_date_format' => 'Y',
						)
					),
				),
				'space_between' => Builder::size( 0 ),
				'custom_css'    => <<<'CSS'
				/* The separator is a middle dot rather than Elementor's hairline; the widget's own divider is off. */
				selector .elementor-icon-list-item:not(:last-child)::after {
					content: "·";
					position: static;
					width: auto;
					height: auto;
					border: 0;
					margin-inline: 0.6em;
				}
				CSS,
				'__globals__'   => array(
					'icon_typography_typography' => Style::font( 'meta' ),
					'text_color'                 => Style::color( 'muted' ),
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
				'__globals__'  => array(
					'typography_typography' => Style::font( 'meta' ),
					'title_color'           => Style::color( 'muted' ),
				),
			)
		);
	}

	/**
	 * The card's behaviour, kept on the card so it stays editable in Elementor: the image crops to a fixed ratio and
	 * grows 3% on hover, and on devices that can hover the excerpt slides up and fades in on hover or keyboard focus.
	 * Touch devices never show it. The image grows with the individual `scale` property so it composes with the
	 * transform the parallax effect writes on the wide card.
	 *
	 * @param string $ratio        Image aspect ratio, e.g. `4 / 5`.
	 * @param string $mobile_ratio Image aspect ratio on mobile.
	 */
	private function card_css( string $ratio, string $mobile_ratio ): string {
		return <<<CSS
		selector .forma-card__image {
			overflow: hidden;
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
		@media (hover: hover) {
			selector:hover .forma-card__image img,
			selector:focus-visible .forma-card__image img {
				scale: 1.03;
			}
			selector .forma-card__excerpt {
				opacity: 0;
				transform: translateY(14px);
				transition: opacity 0.6s var(--forma-ease), transform 0.9s var(--forma-ease);
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

	/** The invitation that closes Home and the inner pages: label, large headline, email and a button to Contact. */
	private function closing_cta(): array {
		$content = new Content( $this->log );
		$contact = $content->page_id( 'contact' );
		$url     = $contact ? (string) get_permalink( $contact ) : home_url( '/contact/' );
		$email   = $this->site['studio']['email'];

		// The hairline above belongs to a stack inside the box, so it runs the width of the content, not of the viewport.
		return Style::section(
			array(
				Style::stack(
					array(
						Style::label( 'Start a project' ),
						Style::heading( 'Let’s build something <em>quiet</em>.', 'display-l', 'h2', 'ink', array( 'fm_entrance' => 'lines' ) ),
						Style::row(
							array(
								Style::heading( $email, 'statement', 'p', 'ink', array( 'link' => Style::link( 'mailto:' . $email ) ) ),
								Style::button( 'Start a conversation', $url ),
							),
							array(
								'flex_justify_content' => 'space-between',
								'flex_align_items'     => 'center',
								'flex_wrap'            => 'wrap',
								'flex_gap'             => Builder::gap( 24, 32 ),
							)
						),
					),
					array(
						'flex_gap'      => Builder::gap( 'clamp(24px, 3vw, 48px)', null, 'custom' ),
						'padding'       => Builder::box( 'var(--forma-section)', 0, 0, 0, 'custom' ),
						'border_border' => 'solid',
						'border_width'  => Builder::box( 1, 0, 0, 0 ),
						'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
					)
				),
			),
			'page',
			array( 'padding' => Builder::box( 0, 'var(--forma-gutter)', 'var(--forma-section)', 'var(--forma-gutter)', 'custom' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Recognition ledger.
	// ---------------------------------------------------------------------------------------------------------------

	/** Awards, newest first: a bottle green band with one hairline row per recognised project. */
	private function recognition(): array {
		$rows = array();

		foreach ( $this->awards() as $index => $award ) {
			$rows[] = $this->ledger_row( $award, $index );
		}

		return Style::section(
			array(
				Style::label( 'Awards, 2019–2025' ),
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
			'deep',
			array(
				'flex_gap'   => Builder::gap( 16 ),
				'custom_css' => <<<'CSS'
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
	private function awards(): array {
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
	private function ledger_row( array $award, int $index ): array {
		return Style::row(
			array(
				Style::cell( array( Style::label( $award['year'], 'muted' ) ), 12, 14, 'auto' ),
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
				'fm_entrance'          => 'fade-up',
				'fm_delay'             => round( 0.05 * $index, 2 ),
				'__globals__'          => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}
}
