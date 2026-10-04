<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The full-screen menu sheet the header's Menu button opens: a bottle green ProElements popup with the
 * primary nav and the studio's contact details. ProElements' accessible navigation traps focus inside it, closes it
 * on Esc and returns focus to the button.
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

	/** Popup document settings: edge to edge, green, no overlay or shadow, fades in and out. */
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
			'padding'                     => Builder::box( 'var(--forma-gutter)', null, null, null, 'custom' ),
			'__globals__'                 => array(
				'background_color'         => Style::color( 'deep' ),
				'close_button_color'       => Style::color( 'page' ),
				'close_button_hover_color' => Style::color( 'accent-on-deep' ),
			),
		);
	}

	public function elements(): array {
		Builder::reset( self::KEY );

		return array(
			Style::section(
				array(
					Style::label( 'Menu' ),
					$this->nav(),
					$this->details(),
				),
				'deep',
				array(
					// The popup already pads the sheet by the gutter; the container fills what is left, and its content
					// is boxed to the Kit's container width (the sheet itself stays full screen).
					'padding'              => Builder::box( 0 ),
					'min_height'           => Builder::size( 'calc(100vh - 2 * var(--forma-gutter))', 'custom' ),
					'flex_justify_content' => 'space-between',
					'flex_gap'             => Builder::gap( 48 ),
				)
			),
		);
	}

	/** The primary nav: large display serif, no pointer, no numbers. */
	private function nav(): array {
		return Builder::widget(
			'nav-menu',
			array(
				'menu'                           => 'primary',
				'menu_name'                      => 'Menu',
				'layout'                         => 'vertical',
				'align_items'                    => 'start',
				'dropdown'                       => 'none',
				'pointer'                        => 'none',
				'menu_typography_typography'     => 'custom',
				'menu_typography_font_family'    => Style::DISPLAY,
				'menu_typography_font_weight'    => '400',
				'menu_typography_font_size'      => Builder::size( 'clamp(44px, 10vw, 128px)', 'custom' ),
				'menu_typography_line_height'    => Builder::size( 0.95, 'em' ),
				'menu_typography_letter_spacing' => Builder::size( -0.02, 'em' ),
				'padding_horizontal_menu_item'   => Builder::size( 0 ),
				'padding_vertical_menu_item'     => Builder::size( 'clamp(2px, 0.8vw, 12px)', 'custom' ),
				'__globals__'                    => array(
					'color_menu_item'        => Style::color( 'ink' ),
					'color_menu_item_hover'  => Style::color( 'accent' ),
					'color_menu_item_active' => Style::color( 'accent' ),
				),
			)
		);
	}

	/** Address, email and coordinates under a hairline: a row on tablet and up, a column on mobile. */
	private function details(): array {
		$studio = $this->studio;

		return Style::row(
			array(
				Style::text( '<p>' . implode( '<br>', array_map( 'esc_html', $studio['address'] ) ) . '</p>', 'meta', 'muted' ),
				Style::heading(
					$studio['email'],
					'label',
					'p',
					'ink',
					array(
						'link' => array(
							'url'               => 'mailto:' . $studio['email'],
							'is_external'       => '',
							'nofollow'          => '',
							'custom_attributes' => '',
						),
					)
				),
				Style::label( $studio['coordinates'] ),
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
