<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The full-screen menu sheet the header's Menu button opens: a Paper ProElements popup holding one rounded Panel
 * inset from the screen, with the primary nav in Display L and the studio's contact details in Label. ProElements'
 * accessible navigation traps focus inside it, closes it on Esc and returns focus to the button.
 */
final class MenuPopup {

	public const KEY = 'menu';

	private array $studio;

	public function __construct( private \Closure $log ) {
		$this->studio = ( require FORMA_ENGINE_PATH . 'data/site.php' )['studio'];
	}

	/**
	 * @return int The popup's post id.
	 */
	public function build(): int {
		$id = Templates::upsert( self::KEY, 'popup', 'Menu', $this->elements(), $this->settings() );

		( $this->log )( 'Menu popup: ' . ( Templates::saved() ? get_the_title( $id ) . " (#{$id})." : Builder::SKIPPED ) );

		return $id;
	}

	/** Popup document settings: edge to edge, on Paper, no overlay or shadow, fades in and out. */
	public function settings(): array {
		return array(
			'width'                       => Builder::size( 100, 'vw' ),
			'height_type'                 => 'fit_to_screen',
			'horizontal_position'         => 'center',
			'vertical_position'           => 'center',
			'overlay'                     => '',
			'close_button'                => 'yes',
			'close_button_position'       => '',
			'entrance_animation'          => 'fadeIn',
			'exit_animation'              => 'fadeIn',
			'entrance_animation_duration' => Builder::size( 0.3 ),
			'prevent_scroll'              => 'yes',
			'a11y_navigation'             => 'yes',
			'background_background'       => 'classic',
			'box_shadow_box_shadow_type'  => '',
			'padding'                     => Builder::box( 0 ),
			'__globals__'                 => array(
				'background_color'         => Style::color( 'page' ),
				'close_button_color'       => Style::color( 'ink' ),
				'close_button_hover_color' => Style::color( 'accent' ),
			),
		);
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			// The Panel brings its own inset margin (the forma-panel class), so the sheet has no padding; the panel's
			// content is boxed to the Kit's container width.
			Style::section(
				array(
					Style::label( 'Menu' ),
					$this->nav(),
					$this->details(),
				),
				'raised',
				array(
					'padding'              => Builder::box( 'clamp(28px, 4vw, 56px)', 'var(--forma-gutter)', 'clamp(28px, 4vw, 56px)', 'var(--forma-gutter)', 'custom' ),
					'min_height'           => Builder::size( 'calc(100vh - 2 * var(--forma-inset))', 'custom' ),
					'flex_justify_content' => 'space-between',
					'flex_gap'             => Builder::gap( 48 ),
				)
			),
		);
	}

	/**
	 * The primary nav in Display L (Archivo 500), no pointer, no numbers. The items are Ink on the Panel and Signal
	 * blue on hover and when active.
	 */
	private function nav(): array {
		return Builder::widget(
			'nav-menu',
			array(
				'menu'                         => 'primary',
				'menu_name'                    => 'Menu',
				'layout'                       => 'vertical',
				'align_items'                  => 'start',
				'dropdown'                     => 'none',
				'pointer'                      => 'none',
				'padding_horizontal_menu_item' => Builder::size( 0 ),
				'padding_vertical_menu_item'   => Builder::size( 'clamp(2px, 0.6vw, 8px)', 'custom' ),
				'__globals__'                  => array(
					'menu_typography_typography' => Style::font( 'display-l' ),
					'color_menu_item'            => Style::color( 'ink' ),
					'color_menu_item_hover'      => Style::color( 'accent' ),
					'color_menu_item_active'     => Style::color( 'accent' ),
				),
			)
		);
	}

	/**
	 * Address, email and coordinates under a hairline, all in Label and Graphite: a row on tablet and up, a column on
	 * mobile.
	 */
	private function details(): array {
		$studio = $this->studio;

		return Style::row(
			array(
				Style::text( '<p>' . implode( '<br>', array_map( 'esc_html', $studio['address'] ) ) . '</p>', 'label', 'muted' ),
				Style::heading(
					$studio['email'],
					'label',
					'p',
					'muted',
					array(
						'link' => array(
							'url'               => 'mailto:' . $studio['email'],
							'is_external'       => '',
							'nofollow'          => '',
							'custom_attributes' => '',
						),
					)
				),
				Style::label( $studio['coordinates'], 'muted' ),
			),
			array(
				'flex_justify_content'    => 'space-between',
				'flex_align_items'        => 'flex-end',
				'flex_direction_mobile'   => 'column',
				'flex_align_items_mobile' => 'flex-start',
				'flex_gap'                => Builder::gap( 24 ),
				'padding'                 => Builder::box( 24, 0, 0, 0 ),
				'border_border'           => 'solid',
				'border_width'            => Builder::box( 1, 0, 0, 0 ),
				'__globals__'             => array( 'border_color' => Style::color( 'line' ) ),
			)
		);
	}
}
