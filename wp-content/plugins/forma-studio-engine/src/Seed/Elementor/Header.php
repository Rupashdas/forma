<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The site-wide Theme Builder header: the FORMA wordmark and coordinates on the left, the primary nav on large
 * screens and a Menu button (which opens the full-screen menu popup) on tablet and mobile. The `forma-header` class
 * is the hook for the theme: it is fixed to the top and condenses, hides and shows as the page scrolls (site.js).
 */
final class Header {

	public const KEY = 'header';

	private array $studio;

	public function __construct( private \Closure $log ) {
		$this->studio = ( require FORMA_ENGINE_PATH . 'data/site.php' )['studio'];
	}

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
			Builder::container(
				array(
					'content_width'        => 'full',
					'flex_direction'       => 'row',
					'flex_justify_content' => 'space-between',
					'flex_align_items'     => 'center',
					'flex_gap'             => Builder::gap( 24 ),
					'padding'              => Builder::box( '24px', 'var(--forma-gutter)', '24px', 'var(--forma-gutter)', 'custom' ),
					'css_classes'          => 'forma-header',
				),
				array(
					$this->brand(),
					$this->actions(),
				)
			),
		);
	}

	/** Wordmark and coordinates. */
	private function brand(): array {
		return Style::row(
			array(
				Builder::widget(
					'theme-site-title',
					array(
						'header_size'                => 'div',
						'link'                       => array(
							'url'               => home_url( '/' ),
							'is_external'       => '',
							'nofollow'          => '',
							'custom_attributes' => '',
						),
						'typography_typography'      => 'custom',
						'typography_font_family'     => 'Bodoni Moda',
						'typography_font_weight'     => '400',
						'typography_font_size'       => Builder::size( 26 ),
						'typography_letter_spacing'  => Builder::size( 0.06, 'em' ),
						'__dynamic__'                => array(
							'title' => Builder::tag( 'site-title' ),
						),
						'__globals__'                => array( 'title_color' => Style::color( 'ink' ) ),
					)
				),
				Style::label( $this->studio['coordinates'], 'muted', array( 'hide_mobile' => 'hidden-mobile' ) ),
			),
			array(
				'width'            => Builder::size( 'auto', 'custom' ),
				'flex_align_items' => 'center',
				'flex_gap'         => Builder::gap( 32 ),
			)
		);
	}

	/** Primary nav (large screens) and the Menu button (tablet and mobile). */
	private function actions(): array {
		return Style::row(
			array(
				Builder::widget(
					'nav-menu',
					array(
						'menu'                         => 'primary',
						'menu_name'                    => 'Primary',
						'layout'                       => 'horizontal',
						'dropdown'                     => 'none',
						'pointer'                      => 'underline',
						'animation_line'               => 'slide',
						'padding_horizontal_menu_item' => Builder::size( 0 ),
						'padding_vertical_menu_item'   => Builder::size( 8 ),
						'menu_space_between'           => Builder::size( 32 ),
						'hide_tablet'                  => 'hidden-tablet',
						'hide_mobile'                  => 'hidden-mobile',
						'__globals__'                  => array(
							'menu_typography_typography'    => Style::font( 'label' ),
							'color_menu_item'               => Style::color( 'ink' ),
							'color_menu_item_hover'         => Style::color( 'ink' ),
							'color_menu_item_active'        => Style::color( 'ink' ),
							'pointer_color_menu_item_hover' => Style::color( 'ink' ),
							'pointer_color_menu_item_active' => Style::color( 'accent' ),
						),
					)
				),
				$this->menu_button(),
			),
			array(
				'width'            => Builder::size( 'auto', 'custom' ),
				'flex_align_items' => 'center',
				'flex_gap'         => Builder::gap( 32 ),
			)
		);
	}

	/** A text-style button that opens the menu popup; it announces that it opens a dialog. */
	private function menu_button(): array {
		return Builder::widget(
			'button',
			array(
				'text'                              => 'Menu',
				'link'                              => array(
					'url'               => '',
					'is_external'       => '',
					'nofollow'          => '',
					'custom_attributes' => 'aria-haspopup|dialog',
				),
				'border_border'                     => 'none',
				'text_padding'                      => Builder::box( 14, 0, 14, 0 ),
				'button_background_hover_background' => 'classic',
				'button_background_hover_color'     => 'rgba(0,0,0,0)',
				'hide_desktop'                      => 'hidden-desktop',
				'hide_laptop'                       => 'hidden-laptop',
				'__dynamic__'                       => array(
					'link' => Builder::tag(
						'popup',
						array(
							'action' => 'open',
							'popup'  => (string) Templates::id( MenuPopup::KEY ),
						)
					),
				),
				'__globals__'                       => array(
					'typography_typography' => Style::font( 'label' ),
					'button_text_color'     => Style::color( 'ink' ),
					'hover_color'           => Style::color( 'accent' ),
				),
			)
		);
	}
}
