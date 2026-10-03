<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The design vocabulary for seeded elements: theme token names mapped to the Kit's Global Colors and Fonts, and small
 * element factories that reference those globals instead of raw values.
 *
 * Colour tokens are the theme's `--forma-{token}` names. Inside a `.forma-deep` band the theme re-points the
 * globals Ink, Slate, Brass and Line, so an element written with the "ink" token reads as Chalk on the bottle green.
 */
final class Style {

	/**
	 * The font families the seeders write, in one place so a family can be swapped without touching the documents'
	 * code. Each name must match an @font-face in the child theme's site.css and be listed in its font group.
	 */
	public const DISPLAY = 'Instrument Serif';
	public const SANS    = 'Archivo';

	/** Theme colour token => Kit Global Color id. */
	public const COLORS = array(
		'page'           => 'primary',
		'ink'            => 'secondary',
		'muted'          => 'text',
		'accent'         => 'accent',
		'raised'         => 'raised',
		'deep'           => 'deep',
		'muted-on-deep'  => 'sage',
		'accent-on-deep' => 'brasslight',
		'line'           => 'line',
		'line-on-deep'   => 'linedeep',
	);

	/** Type style token => Kit Global Font id. */
	public const FONTS = array(
		'display-xl' => 'primary',
		'display-l'  => 'secondary',
		'body'       => 'text',
		'label'      => 'accent',
		'heading'    => 'heading',
		'statement'  => 'statement',
		'subheading' => 'subheading',
		'meta'       => 'meta',
	);

	/** Surfaces a section can sit on. */
	private const SURFACES = array( 'page', 'raised', 'deep' );

	/**
	 * @throws \InvalidArgumentException For a token that is not in {@see self::COLORS}.
	 */
	public static function color( string $token ): string {
		if ( ! isset( self::COLORS[ $token ] ) ) {
			throw new \InvalidArgumentException( esc_html( "Unknown colour token '{$token}'." ) );
		}

		return 'globals/colors?id=' . self::COLORS[ $token ];
	}

	/**
	 * @throws \InvalidArgumentException For a token that is not in {@see self::FONTS}.
	 */
	public static function font( string $token ): string {
		if ( ! isset( self::FONTS[ $token ] ) ) {
			throw new \InvalidArgumentException( esc_html( "Unknown font token '{$token}'." ) );
		}

		return 'globals/typography?id=' . self::FONTS[ $token ];
	}

	/** A heading widget on a Kit font and colour. */
	public static function heading( string $title, string $font = 'heading', string $tag = 'h2', string $color = 'ink', array $extra = array() ): array {
		return Builder::widget(
			'heading',
			self::merge(
				array(
					'title'       => $title,
					'header_size' => $tag,
					'__globals__' => array(
						'typography_typography' => self::font( $font ),
						'title_color'           => self::color( $color ),
					),
				),
				$extra
			)
		);
	}

	/**
	 * Typography settings for the display serif at a size the Kit has no global for (card titles, service names).
	 * The display face only has a regular weight, so the weight is always 400. Merge them into a widget that does not
	 * also carry a typography global, because a global replaces every local typography value.
	 *
	 * @param string $size CSS font size, e.g. `clamp(26px, 2.4vw, 36px)`.
	 */
	public static function serif_type( string $size, float $line_height = 1.05, float $tracking = -0.01 ): array {
		return array(
			'typography_typography'     => 'custom',
			'typography_font_family'    => self::DISPLAY,
			'typography_font_weight'    => '400',
			'typography_font_size'      => Builder::size( $size, 'custom' ),
			'typography_line_height'    => Builder::size( $line_height, 'em' ),
			'typography_letter_spacing' => Builder::size( $tracking, 'em' ),
		);
	}

	/** A heading in the display serif at a custom size. */
	public static function serif( string $title, string $size, string $tag = 'h3', string $color = 'ink', array $extra = array() ): array {
		return Builder::widget(
			'heading',
			self::merge(
				self::serif_type( $size ) + array(
					'title'       => $title,
					'header_size' => $tag,
					'__globals__' => array( 'title_color' => self::color( $color ) ),
				),
				$extra
			)
		);
	}

	/** A small uppercase label: a paragraph set in the Label font. */
	public static function label( string $text, string $color = 'muted', array $extra = array() ): array {
		return self::heading( $text, 'label', 'p', $color, $extra );
	}

	/** A text editor widget on a Kit font and colour. */
	public static function text( string $html, string $font = 'body', string $color = 'ink', array $extra = array() ): array {
		return Builder::widget(
			'text-editor',
			self::merge(
				array(
					'editor'      => $html,
					'__globals__' => array(
						'typography_typography' => self::font( $font ),
						'text_color'            => self::color( $color ),
					),
				),
				$extra
			)
		);
	}

	/**
	 * A full-width top-level band: column, section padding above and below, gutter at the sides. A `deep` surface is
	 * the bottle green band and gets the `forma-deep` class.
	 *
	 * @param string $surface One of page, raised, deep.
	 */
	public static function section( array $children, string $surface = 'page', array $extra = array() ): array {
		if ( ! in_array( $surface, self::SURFACES, true ) ) {
			throw new \InvalidArgumentException( esc_html( "Unknown surface '{$surface}'." ) );
		}

		$settings = array(
			'content_width'         => 'full',
			'flex_direction'        => 'column',
			'flex_gap'              => Builder::gap( 0 ),
			'padding'               => Builder::box( 'var(--forma-section)', 'var(--forma-gutter)', 'var(--forma-section)', 'var(--forma-gutter)', 'custom' ),
			'background_background' => 'classic',
			'__globals__'           => array( 'background_color' => self::color( $surface ) ),
		);

		if ( 'deep' === $surface ) {
			$settings['css_classes'] = 'forma-deep';
		}

		return Builder::container( self::merge( $settings, $extra ), $children );
	}

	/** A full-width inner container laid out as a row. */
	public static function row( array $children, array $extra = array() ): array {
		return self::inner( 'row', $children, $extra );
	}

	/** A full-width inner container laid out as a column. */
	public static function stack( array $children, array $extra = array() ): array {
		return self::inner( 'column', $children, $extra );
	}

	/**
	 * A column of a row: an inner column container with its width on every device. Elementor makes child containers
	 * full width on tablet and mobile, so all three widths are always written.
	 *
	 * @param int|float|string      $width  Percent on desktop and laptop, or `auto`.
	 * @param int|float|string|null $tablet Percent on tablet, or `auto`; the desktop width when null.
	 * @param int|float|string      $mobile Percent on mobile, or `auto`.
	 */
	public static function cell( array $children, int|float|string $width, int|float|string|null $tablet = null, int|float|string $mobile = 100, array $extra = array() ): array {
		$size = static fn( int|float|string $value ): array => 'auto' === $value ? Builder::size( 'auto', 'custom' ) : Builder::size( $value, '%' );

		return self::stack(
			$children,
			self::merge(
				array(
					'width'        => $size( $width ),
					'width_tablet' => $size( $tablet ?? $width ),
					'width_mobile' => $size( $mobile ),
				),
				$extra
			)
		);
	}

	/** A URL control value. */
	public static function link( string $url = '' ): array {
		return array(
			'url'               => $url,
			'is_external'       => '',
			'nofollow'          => '',
			'custom_attributes' => '',
		);
	}

	/** A button in the Kit's button style (Label font, outlined in Ink, filled on hover). */
	public static function button( string $text, string $url, array $extra = array() ): array {
		return Builder::widget(
			'button',
			self::merge(
				array(
					'text' => $text,
					'link' => self::link( $url ),
				),
				$extra
			)
		);
	}

	private static function inner( string $direction, array $children, array $extra ): array {
		return Builder::container(
			self::merge(
				array(
					'content_width'  => 'full',
					'flex_direction' => $direction,
					'flex_gap'       => Builder::gap( 0 ),
				),
				$extra
			),
			$children,
			true
		);
	}

	/**
	 * `$extra` wins, except that its `__globals__` are merged into the factory's rather than replacing them, and
	 * class lists are joined, so a caller can add a class without losing `forma-deep`.
	 */
	private static function merge( array $base, array $extra ): array {
		$merged = array_merge( $base, $extra );

		if ( isset( $base['__globals__'] ) || isset( $extra['__globals__'] ) ) {
			$merged['__globals__'] = array_merge( $base['__globals__'] ?? array(), $extra['__globals__'] ?? array() );
		}

		foreach ( array( 'css_classes', '_css_classes' ) as $key ) {
			if ( isset( $base[ $key ], $extra[ $key ] ) ) {
				$merged[ $key ] = trim( $base[ $key ] . ' ' . $extra[ $key ] );
			}
		}

		return $merged;
	}
}
