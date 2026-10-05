<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The site-wide Theme Builder header: two floating glass pills. On the left, the FORMA wordmark and the links to
 * Projects, Studio and Process, which a Menu button (opening the full-screen menu popup) replaces below 1024px; on the
 * right, an Ink "Start a project" pill to Contact (hidden on mobile, where the menu carries Contact). The
 * `forma-header` class is the hook for the theme: it is fixed 12px from the top and sides, click-through between the
 * pills, and hides and shows as the page scrolls (site.js); `forma-pill` gives the left pill its glass (Paper at 72%
 * over a blur). The links and buttons carry `data-magnetic` for the pointer pull in cursor.js.
 *
 * The links are plain link widgets rather than a Nav Menu widget because the pill shows three of the primary menu's
 * items; site.js marks the one for the current page with `aria-current`.
 */
final class Header {

	public const KEY = 'header';

	public function __construct( private \Closure $log ) {}

	/**
	 * @return int The header template's post id.
	 */
	public function build(): int {
		$id = Templates::upsert( self::KEY, 'header', 'Header', $this->elements(), array(), array( array( 'include', 'general' ) ) );

		( $this->log )( 'Header: ' . ( Templates::saved() ? "site-wide (#{$id})." : Builder::SKIPPED ) );

		return $id;
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			// A fixed row with no background of its own, boxed like every section's content (1320px, centred, 12px of
			// padding on small screens): the pills sit at its two ends, so on a wide screen they line up with the page.
			Builder::container(
				array(
					'content_width'        => 'boxed',
					'flex_direction'       => 'row',
					'flex_justify_content' => 'space-between',
					'flex_align_items'     => 'center',
					'flex_wrap'            => 'nowrap',
					'flex_gap'             => Builder::gap( 12 ),
					'padding'              => Builder::box( 0, 12, 0, 12 ),
					'css_classes'          => 'forma-header',
				),
				array(
					$this->nav_pill(),
					$this->start_button(),
				)
			),
		);
	}

	/** The glass pill: wordmark, then the links (large screens) or the Menu button (tablet and mobile). */
	private function nav_pill(): array {
		return Style::row(
			array(
				$this->wordmark(),
				$this->links(),
				$this->menu_button(),
			),
			array(
				'width'            => Builder::size( 'auto', 'custom' ),
				'width_tablet'     => Builder::size( 'auto', 'custom' ),
				'width_mobile'     => Builder::size( 'auto', 'custom' ),
				'flex_align_items' => 'center',
				'flex_wrap'        => 'nowrap',
				'flex_gap'         => Builder::gap( 20 ),
				'padding'          => Builder::box( 6, 6, 6, 20 ),
				'css_classes'      => 'forma-pill',
			)
		);
	}

	/** The wordmark: the site title in Archivo Expanded 800, 18px, linked to Home. */
	private function wordmark(): array {
		return Builder::widget(
			'theme-site-title',
			array(
				'header_size'               => 'div',
				'link'                      => Style::link( home_url( '/' ) ),
				'typography_typography'     => 'custom',
				'typography_font_family'    => Style::WORDMARK,
				'typography_font_weight'    => '800',
				'typography_font_size'      => Builder::size( 18 ),
				'typography_line_height'    => Builder::size( 1, 'em' ),
				'typography_letter_spacing' => Builder::size( -0.01, 'em' ),
				'__dynamic__'               => array( 'title' => Builder::tag( 'site-title' ) ),
				'__globals__'               => array( 'title_color' => Style::color( 'ink' ) ),
			)
		);
	}

	/** Projects, Studio and Process as pill links in a nav landmark; hidden on tablet and mobile. */
	private function links(): array {
		$content = new Content( $this->log );
		$page    = static function ( string $slug ) use ( $content ): string {
			$id = $content->page_id( $slug );

			return $id ? (string) get_permalink( $id ) : home_url( "/{$slug}/" );
		};
		$links   = array(
			'Projects' => (string) ( get_post_type_archive_link( 'forma_project' ) ?: home_url( '/projects/' ) ),
			'Studio'   => $page( 'studio' ),
			'Process'  => $page( 'process' ),
		);

		$items = array();

		foreach ( $links as $label => $url ) {
			$items[] = Style::heading( $label, 'label', 'p', 'ink', array( 'link' => $this->magnetic( $url ) ) );
		}

		return Style::row(
			$items,
			array(
				'html_tag'         => 'nav',
				'_attributes'      => 'aria-label|Primary',
				'css_classes'      => 'forma-nav',
				'width'            => Builder::size( 'auto', 'custom' ),
				'flex_align_items' => 'center',
				'flex_wrap'        => 'nowrap',
				'flex_gap'         => Builder::gap( 2 ),
				'hide_tablet'      => 'hidden-tablet',
				'hide_mobile'      => 'hidden-mobile',
			)
		);
	}

	/**
	 * The Menu button that opens the menu popup, in the pill in place of the links; it announces that it opens a
	 * dialog and is magnetic. It is the Kit's Ink pill button, trimmed to the height of the links.
	 */
	private function menu_button(): array {
		$link                      = $this->magnetic( '' );
		$link['custom_attributes'] = 'aria-haspopup|dialog,data-magnetic|true';

		return Builder::widget(
			'button',
			array(
				'text'         => 'Menu',
				'link'         => $link,
				'text_padding' => Builder::box( 9, 16 ),
				'hide_desktop' => 'hidden-desktop',
				'hide_laptop'  => 'hidden-laptop',
				'__dynamic__'  => array(
					'link' => Builder::tag(
						'popup',
						array(
							'action' => 'open',
							'popup'  => (string) Templates::id( MenuPopup::KEY ),
						)
					),
				),
			)
		);
	}

	/** The Ink pill on the right: "Start a project" to Contact, sized to match the glass pill's height. */
	private function start_button(): array {
		$content = new Content( $this->log );
		$id      = $content->page_id( 'contact' );
		$url     = $id ? (string) get_permalink( $id ) : home_url( '/contact/' );

		return Style::button(
			'Start a project',
			$url,
			array(
				'link'         => $this->magnetic( $url ),
				'text_padding' => Builder::box( 15, 24 ),
				'hide_mobile'  => 'hidden-mobile',
			)
		);
	}

	/** A link that cursor.js pulls toward the pointer. */
	private function magnetic( string $url ): array {
		$link                      = Style::link( $url );
		$link['custom_attributes'] = 'data-magnetic|true';

		return $link;
	}
}
