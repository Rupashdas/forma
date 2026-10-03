<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

/**
 * The site-wide Theme Builder footer: a bottle green band with four columns (studio, contact, index, elsewhere),
 * the FORMA wordmark at display size and a credit row. Role colours (Ink, Slate, Brass) render as Chalk, Sage and
 * Brass light, because the band carries the `forma-deep` class.
 */
final class Footer {

	public const KEY = 'footer';

	private array $studio;

	public function __construct( private \Closure $log ) {
		$this->studio = ( require FORMA_ENGINE_PATH . 'data/site.php' )['studio'];
	}

	/**
	 * @return int The footer template's post id.
	 */
	public function build(): int {
		$id = Templates::upsert( self::KEY, 'footer', 'Footer', $this->elements(), array(), array( array( 'include', 'general' ) ) );

		( $this->log )( 'Footer: ' . ( Templates::saved() ? "site-wide (#{$id})." : Builder::SKIPPED ) );

		return $id;
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			Style::section(
				array(
					$this->columns(),
					Style::heading( 'FORMA', 'display-xl', 'div', 'ink', array( 'fm_entrance' => 'chars' ) ),
					$this->credits(),
				),
				'deep',
				array(
					'flex_gap' => Builder::gap( 'var(--forma-gutter)', null, 'custom' ),
					'padding'  => Builder::box( 'var(--forma-section)', 'var(--forma-gutter)', 'clamp(24px, 3vw, 40px)', 'var(--forma-gutter)', 'custom' ),
				)
			),
		);
	}

	/** Four columns: hairline dividers on desktop, two by two on tablet, stacked on mobile. */
	private function columns(): array {
		$studio = $this->studio;
		$line   = array( '__globals__' => array( 'border_color' => Style::color( 'line' ) ) );

		$address = '<p>' . implode( '<br>', array_map( 'esc_html', $studio['address'] ) ) . '</p><p>' . esc_html( $studio['hours'] ) . '</p>';
		$phone   = sprintf( '<p><a href="tel:%s">%s</a></p>', esc_attr( preg_replace( '/[^\d+]/', '', $studio['phone'] ) ), esc_html( $studio['phone'] ) );

		$social = array();

		foreach ( $studio['social'] as $label => $url ) {
			$social[] = array(
				'_id'           => Builder::id(),
				'text'          => $label,
				'selected_icon' => array(
					'value'   => '',
					'library' => '',
				),
				'link'          => array(
					'url'               => $url,
					'is_external'       => '',
					'nofollow'          => '',
					'custom_attributes' => '',
				),
			);
		}

		$columns = array(
			array(
				Style::label( 'Studio' ),
				Style::text( $address, 'meta', 'muted' ),
			),
			array(
				Style::label( 'Contact' ),
				Style::heading(
					$studio['email'],
					'statement',
					'p',
					'ink',
					array(
						'link' => array(
							'url'               => 'mailto:' . $studio['email'],
							'is_external'       => '',
							'nofollow'          => '',
							'custom_attributes' => '',
						),
						// The Statement face, sized down to fit a quarter-width column.
						'custom_css' => 'selector .elementor-heading-title { font-size: clamp(22px, 1.9vw, 30px); overflow-wrap: anywhere; }',
					)
				),
				Style::text( $phone, 'meta', 'muted' ),
			),
			array(
				Style::label( 'Index' ),
				Builder::widget(
					'nav-menu',
					array(
						'menu'                         => 'footer',
						'menu_name'                    => 'Footer',
						'layout'                       => 'vertical',
						'align_items'                  => 'start',
						'dropdown'                     => 'none',
						'pointer'                      => 'none',
						'padding_horizontal_menu_item' => Builder::size( 0 ),
						'padding_vertical_menu_item'   => Builder::size( 4 ),
						'__globals__'                  => array(
							'menu_typography_typography' => Style::font( 'body' ),
							'color_menu_item'            => Style::color( 'ink' ),
							'color_menu_item_hover'      => Style::color( 'accent' ),
							'color_menu_item_active'     => Style::color( 'accent' ),
						),
					)
				),
			),
			array(
				Style::label( 'Elsewhere' ),
				Builder::widget(
					'icon-list',
					array(
						'icon_list'     => $social,
						'space_between' => Builder::size( 8 ),
						'__globals__'   => array(
							'icon_typography_typography' => Style::font( 'body' ),
							'text_color'                 => Style::color( 'ink' ),
							'text_color_hover'           => Style::color( 'accent' ),
						),
					)
				),
			),
		);

		$stacks = array();

		foreach ( $columns as $index => $children ) {
			$first = 0 === $index;

			$stacks[] = Style::stack(
				$children,
				array(
					'width'               => Builder::size( 25, '%' ),
					'width_tablet'        => Builder::size( 50, '%' ),
					'width_mobile'        => Builder::size( 100, '%' ),
					'flex_gap'            => Builder::gap( 12 ),
					'padding'             => Builder::box( 0, 24, 0, $first ? 0 : 24 ),
					'padding_tablet'      => Builder::box( 0, 24, 0, 0 ),
					'padding_mobile'      => Builder::box( 0 ),
					'border_border'       => 'solid',
					'border_width'        => Builder::box( 0, 0, 0, $first ? 0 : 1 ),
					'border_width_tablet' => Builder::box( 0 ),
					'border_width_mobile' => Builder::box( 0 ),
				) + $line
			);
		}

		return Style::row(
			$stacks,
			array(
				'flex_wrap' => 'wrap',
				'flex_gap'  => Builder::gap( 48, 0 ),
			)
		);
	}

	/** Copyright line and the Colophon link, under a hairline. */
	private function credits(): array {
		$page     = ( new Content( $this->log ) )->page_id( 'colophon' );
		$colophon = $page ? get_permalink( $page ) : home_url( '/colophon/' );

		return Style::row(
			array(
				Style::text( '<p>© 2026 FORMA. A fictional studio, designed and built by <a href="https://devrupash.com">Rupash Das</a>.</p>', 'meta', 'muted' ),
				Style::text( '<p><a href="' . esc_url( $colophon ) . '">Colophon</a></p>', 'meta', 'muted' ),
			),
			array(
				'flex_justify_content' => 'space-between',
				'flex_align_items'     => 'center',
				'flex_wrap'            => 'wrap',
				'flex_gap'             => Builder::gap( 16 ),
				'padding'              => Builder::box( 24, 0, 0, 0 ),
				'border_border'        => 'solid',
				'border_width'         => Builder::box( 1, 0, 0, 0 ),
				'__globals__'          => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}
}
