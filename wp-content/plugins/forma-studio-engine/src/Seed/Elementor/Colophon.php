<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Images;

defined( 'ABSPATH' ) || exit;

/**
 * The Colophon (spec 5.2): how the site is made, as a page of plain text with no model.
 *
 * 1. Hero: an inset Panel with the H1 and an intro.
 * 2. Five blocks, each a title at the left and its text at the right under a hairline: the typeface (Archivo, with its
 *    licence), the code (Three.js, GSAP and Lenis, each with its licence), the photography, the privacy note and why the
 *    site exists (a fictional studio, designed and built by Rupash Das).
 *
 * The photography credits are generated when the page is built, from the `_forma_credit` meta of every attachment the
 * image seeder imported. They are grouped by the project or page the photograph belongs to, and each names the
 * photographer with a link to the photograph's page on Unsplash or Pexels.
 */
final class Colophon extends Page {

	public const KEY = 'colophon';

	/** The order of the pages' groups among the credits; the projects follow, in alphabetical order. */
	private const PAGES = array( 'home', 'studio', 'contact' );

	protected function key(): string {
		return self::KEY;
	}

	protected function summary(): string {
		return '2 sections, ' . count( $this->credits() ) . ' groups of photography credits';
	}

	protected function sections(): array {
		$page = $this->site['colophon'];

		return array(
			$this->masthead( $page['title'], $page['intro'] ),
			$this->band(
				array(
					$this->typefaces(),
					$this->code(),
					$this->photography(),
					$this->privacy(),
					$this->about(),
				),
				'clamp(24px, 3vw, 48px)',
				'clamp(48px, 6vw, 96px)',
				array( 'flex_gap' => Builder::gap( 0 ) )
			),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Blocks.
	// ---------------------------------------------------------------------------------------------------------------

	/** The typeface: two short paragraphs and its licence. */
	private function typefaces(): array {
		$page    = $this->site['colophon']['type'];
		$licence = str_replace(
			'SIL Open Font License 1.1',
			'<a href="' . esc_url( $page['licence_url'] ) . '">SIL Open Font License 1.1</a>',
			esc_html( $page['licence'] )
		);

		$children = array();

		foreach ( $page['text'] as $paragraph ) {
			$children[] = $this->paragraph( $paragraph, 'ink', 56 );
		}

		$children[] = $this->html( '<p>' . $licence . '</p>', 'muted', 56 );

		return $this->block( $page['title'], $children );
	}

	/** The code: the three libraries, each with what it does and a link to its licence, as hairline rows. */
	private function code(): array {
		$page = $this->site['colophon']['code'];
		$rows = array();

		foreach ( $page['items'] as $item ) {
			$rows[] = $this->ruled_row(
				array(
					Style::cell( array( Style::display( $item['name'], 'clamp(22px, 2.2vw, 32px)', 'h3' ) ), 30, 30, 100 ),
					Style::cell( array( Style::text( '<p>' . esc_html( ucfirst( $item['what'] ) ) . '.</p>', 'body', 'muted' ) ), 44, 44, 100 ),
					Style::cell( array( Style::text_link( $item['licence'], $item['url'] ) ), 22, 22, 100 ),
				),
				array( 'padding' => Builder::box( 16, 0, 16, 0 ) )
			);
		}

		return $this->block(
			$page['title'],
			array(
				$this->paragraph( $page['text'], 'ink', 56 ),
				$this->ruled( $rows ),
				$this->paragraph( $page['note'], 'muted', 56 ),
			)
		);
	}

	/** The photography: an intro and one list of credits per project or page, two groups across from 1024px. */
	private function photography(): array {
		$page   = $this->site['colophon']['credits'];
		$groups = array();

		foreach ( $this->credits() as $title => $credits ) {
			$items = '';

			foreach ( $credits as $credit ) {
				$items .= sprintf(
					'<li><span class="forma-credit__what">%s</span><span class="forma-credit__by"><a href="%s">%s</a> on %s</span></li>',
					esc_html( $credit['what'] ),
					esc_url( $credit['url'] ),
					esc_html( $credit['name'] ),
					esc_html( $credit['source'] )
				);
			}

			$groups[] = Style::stack(
				array(
					Style::display( $title, 'clamp(20px, 1.8vw, 26px)', 'h3' ),
					Style::text(
						'<ul>' . $items . '</ul>',
						'body',
						'ink',
						array(
							'custom_css' => <<<'CSS'
							selector ul {
								margin: 0;
								padding: 0;
								list-style: none;
							}
							selector li {
								display: flex;
								flex-direction: column;
								gap: 2px;
								margin: 0;
								padding: 12px 0;
								border-top: 1px solid var(--forma-line);
								font-size: 15px;
								line-height: 1.4;
							}
							selector .forma-credit__by {
								color: var(--forma-muted);
								font-size: 13px;
							}
							selector a {
								text-decoration: underline;
								text-decoration-thickness: 1px;
								text-underline-offset: 0.25em;
							}
							selector a:hover,
							selector a:focus-visible {
								color: var(--forma-accent);
							}
							CSS,
						)
					),
				),
				array( 'flex_gap' => Builder::gap( 10 ) )
			);
		}

		return $this->block(
			$page['title'],
			array(
				$this->paragraph( $page['intro'], 'ink', 56 ),
				$this->grid( $groups, array( 2, 1, 1 ), array( 'grid_gaps' => Builder::gap( 'clamp(32px, 4vw, 56px)', 'clamp(24px, 3vw, 48px)', 'custom' ) ) ),
			)
		);
	}

	/** The privacy note: no tracking, and what the contact form keeps. */
	private function privacy(): array {
		$page     = $this->site['colophon']['privacy'];
		$children = array();

		foreach ( $page['text'] as $paragraph ) {
			$children[] = $this->paragraph( $paragraph, 'ink', 56 );
		}

		return $this->block( $page['title'], $children );
	}

	/** Why the site exists: a fictional studio, designed and built by Rupash Das, with a link to his site. */
	private function about(): array {
		$page = $this->site['colophon']['about'];

		return $this->block(
			$page['title'],
			array(
				$this->html(
					sprintf(
						'<p>%s<a href="%s">%s</a>%s</p>',
						esc_html( $page['before'] ),
						esc_url( $page['link'] ),
						esc_html( $page['name'] ),
						esc_html( $page['after'] )
					),
					'ink',
					56
				),
				$this->paragraph( $page['text'], 'muted', 56 ),
			),
			true
		);
	}

	/**
	 * One block of the page: the title at the left (32%) and its content at the right (62%) under a hairline; below
	 * 1024px they stack.
	 *
	 * @param array $children Elements of the content column.
	 * @param bool  $last     Whether it is the last block, which gets a closing hairline.
	 */
	private function block( string $title, array $children, bool $last = false ): array {
		return Style::row(
			array(
				Style::cell( array( Style::display( $title, 'clamp(26px, 2.8vw, 40px)', 'h2' ) ), 32, 100, 100 ),
				Style::cell( $children, 62, 100, 100, array( 'flex_gap' => Builder::gap( 'clamp(16px, 2vw, 24px)', null, 'custom' ) ) ),
			),
			array(
				'flex_wrap'               => 'nowrap',
				'flex_justify_content'    => 'space-between',
				'flex_align_items'        => 'flex-start',
				'flex_direction_tablet'   => 'column',
				'flex_align_items_tablet' => 'stretch',
				'flex_gap'                => Builder::gap( 'clamp(16px, 2.4vw, 32px)', null, 'custom' ),
				'padding'                 => Builder::box( 'clamp(28px, 3.4vw, 48px)', 0, 'clamp(28px, 3.4vw, 48px)', 0, 'custom' ),
				'border_border'           => 'solid',
				'border_width'            => Builder::box( 1, 0, $last ? 1 : 0, 0 ),
				'__globals__'             => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}

	/** A paragraph that holds HTML (a link), already escaped by the caller, with links underlined. */
	private function html( string $html, string $color, int $measure ): array {
		return Style::text(
			$html,
			'body',
			$color,
			array(
				'custom_css' => <<<CSS
				selector p {
					max-width: {$measure}ch;
					margin: 0;
				}
				selector a {
					text-decoration: underline;
					text-decoration-thickness: 1px;
					text-underline-offset: 0.25em;
					transition: color 0.3s var(--forma-ease);
				}
				selector a:hover,
				selector a:focus-visible {
					color: var(--forma-accent);
				}
				CSS,
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Credits.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The photography credits, from the media library: every attachment with a `_forma_credit`, grouped by the title of
	 * the project or page it belongs to. The pages come first (Home, Studio, Contact) and the projects follow in
	 * alphabetical order; within a group the photographs keep the order the gallery has.
	 *
	 * @return array<string,list<array{what:string,name:string,url:string,source:string}>> Credits by group title.
	 */
	private function credits(): array {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'meta_key'       => Images::CREDIT_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- build time only.
			)
		);

		$groups = array();

		foreach ( $ids as $id ) {
			$credit = (array) get_post_meta( $id, Images::CREDIT_KEY, true );

			if ( empty( $credit['name'] ) || empty( $credit['url'] ) ) {
				continue;
			}

			$parent = (int) wp_get_post_parent_id( $id );
			$title  = $parent ? (string) get_the_title( $parent ) : 'The site';
			$page   = $parent && 'page' === get_post_type( $parent ) ? (string) get_post_field( 'post_name', $parent ) : '';

			$groups[ $title ]['order']   = '' !== $page && in_array( $page, self::PAGES, true ) ? (int) array_search( $page, self::PAGES, true ) : 100;
			$groups[ $title ]['items'][] = array(
				'what'   => (string) get_post_field( 'post_excerpt', $id ) ?: (string) get_the_title( $id ),
				'name'   => (string) $credit['name'],
				'url'    => (string) $credit['url'],
				'source' => (string) ( $credit['source'] ?? '' ),
			);
		}

		uksort(
			$groups,
			static fn( string $a, string $b ): int => array( $groups[ $a ]['order'], $a ) <=> array( $groups[ $b ]['order'], $b )
		);

		return array_map( static fn( array $group ): array => $group['items'], $groups );
	}
}
