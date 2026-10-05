<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The design vocabulary for seeded elements: theme token names mapped to the Kit's Global Colors and Fonts, and small
 * element factories that reference those globals instead of raw values.
 *
 * Colour tokens are the theme's `--forma-{token}` names. Inside a `.forma-deep` band the theme re-points the
 * globals Ink, Graphite, Signal blue, Panel and Line, so an element written with the "ink" token reads as Paper on
 * the Ink band, and a "raised" Panel tile becomes an Ink tile.
 */
final class Style {

	/**
	 * The font families the seeders write, in one place so a family can be swapped without touching the documents'
	 * code. One variable Archivo file serves both, each declared at its own width: the names must match an @font-face
	 * in the child theme's site.css and be listed in its font group. Only the wordmark uses the expanded width.
	 */
	public const DISPLAY  = 'Archivo';
	public const WORDMARK = 'Archivo Expanded';
	public const SANS     = 'Archivo';

	/** Theme colour token => Kit Global Color id. */
	public const COLORS = array(
		'page'           => 'primary',
		'ink'            => 'secondary',
		'muted'          => 'text',
		'accent'         => 'accent',
		'raised'         => 'raised',
		'deep'           => 'deep',
		'muted-on-deep'  => 'ash',
		'accent-on-deep' => 'lightblue',
		'line'           => 'line',
		'line-on-deep'   => 'linedeep',
		'chip'           => 'chip',
		'chip-ink'       => 'chipink',
		'deep-raised'    => 'deepraised',
	);

	/** Type style token => Kit Global Font id. */
	public const FONTS = array(
		'display-xxl' => 'primary',
		'display-l'   => 'secondary',
		'body'        => 'text',
		'label'       => 'accent',
		'heading'     => 'heading',
		'subheading'  => 'subheading',
		'meta'        => 'meta',
		'chip'        => 'chip',
	);

	/** Surfaces a section can sit on. */
	private const SURFACES = array( 'page', 'raised', 'deep' );

	/**
	 * The side padding of a panel: the page gutter less the inset the panel already has, so the content of a panel and
	 * of a section on Paper start at the same distance from the viewport edge, at every width.
	 */
	public const PANEL_GUTTER = 'calc(var(--forma-gutter) - var(--forma-inset))';

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
	 * Typography settings for display type at a size the Kit has no global for (a service name, a large figure). The
	 * default is Archivo 500, the medium weight the whole design speaks in, on a tight line; pass Style::WORDMARK and
	 * weight 800 for the wordmark. Merge them into a widget that does not also carry a typography global, because a
	 * global replaces every local typography value.
	 *
	 * @param string $size   CSS font size, e.g. `clamp(26px, 2.4vw, 36px)`.
	 * @param string $family One of {@see self::DISPLAY} or {@see self::WORDMARK}.
	 * @param string $weight CSS font weight, 100 to 900.
	 */
	public static function display_type( string $size, float $line_height = 1.05, float $tracking = -0.03, string $family = self::DISPLAY, string $weight = '500' ): array {
		return array(
			'typography_typography'     => 'custom',
			'typography_font_family'    => $family,
			'typography_font_weight'    => $weight,
			'typography_font_size'      => Builder::size( $size, 'custom' ),
			'typography_line_height'    => Builder::size( $line_height, 'em' ),
			'typography_letter_spacing' => Builder::size( $tracking, 'em' ),
		);
	}

	/** A heading in display type at a custom size; see {@see self::display_type()} for the defaults. */
	public static function display( string $title, string $size, string $tag = 'h3', string $color = 'ink', array $extra = array() ): array {
		return Builder::widget(
			'heading',
			self::merge(
				self::display_type( $size ) + array(
					'title'       => $title,
					'header_size' => $tag,
					'__globals__' => array( 'title_color' => self::color( $color ) ),
				),
				$extra
			)
		);
	}

	/** A small label: a paragraph set in the Label font (sentence case, no text transform). */
	public static function label( string $text, string $color = 'muted', array $extra = array() ): array {
		return self::heading( $text, 'label', 'p', $color, $extra );
	}

	/**
	 * A chip: a small pill for a project number or a badge. A paragraph in the Chip font and Chip ink, on a Chip
	 * background with a full radius. The `forma-chip` class (site.css) makes the widget shrink to its text.
	 */
	public static function chip( string $text, array $extra = array() ): array {
		return self::heading(
			$text,
			'chip',
			'p',
			'chip-ink',
			self::merge(
				array(
					'_css_classes'           => 'forma-chip',
					'_background_background' => 'classic',
					'_padding'               => Builder::box( 5, 10 ),
					'_border_radius'         => Builder::box( 999 ),
					'__globals__'            => array( '_background_color' => self::color( 'chip' ) ),
				),
				$extra
			)
		);
	}

	/**
	 * A text link: a paragraph in the Label font with a 1px underline, Signal blue on hover. The secondary action next
	 * to a pill button, or the quiet way on to a page.
	 */
	public static function text_link( string $label, string $url, array $extra = array() ): array {
		return self::heading(
			$label,
			'label',
			'p',
			'ink',
			self::merge(
				array(
					'link'       => self::link( $url ),
					'custom_css' => <<<'CSS'
					selector .elementor-heading-title a {
						text-decoration: underline;
						text-decoration-thickness: 1px;
						text-underline-offset: 0.3em;
						transition: color 0.4s var(--forma-ease);
					}
					selector .elementor-heading-title a:hover,
					selector .elementor-heading-title a:focus-visible {
						color: var(--forma-accent);
					}
					CSS,
				),
				$extra
			)
		);
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
	 * A top-level band whose content is boxed: the container itself (and so its background) spans the viewport, while
	 * its content sits in a centred column as wide as the Kit's container width (1320px), with the gutter at the sides
	 * on smaller screens. Elementor wraps the children of a boxed container in an inner element (`.e-con-inner`) that
	 * takes that width, so the section padding above and below applies inside the box and the gutter outside it. With
	 * no `boxed_width` of its own the box follows the Kit.
	 *
	 * A `raised` (Panel) or `deep` (Ink band) surface is a panel: the `forma-panel` class (site.css) insets it from the
	 * viewport and rounds its corners, and a `deep` surface also gets the `forma-deep` class. Pass `'panel' => false`
	 * in `$extra` to keep such a surface edge to edge (it is not an Elementor setting and is not written).
	 *
	 * A section whose content should run the full width of the viewport (inside the gutter) passes `full`.
	 *
	 * @param string $surface One of page, raised, deep.
	 * @param string $width   `boxed` (the default: content in the Kit's container width) or `full` (content edge to edge,
	 *                        inside the gutter).
	 */
	public static function section( array $children, string $surface = 'page', array $extra = array(), string $width = 'boxed' ): array {
		if ( ! in_array( $surface, self::SURFACES, true ) ) {
			throw new \InvalidArgumentException( esc_html( "Unknown surface '{$surface}'." ) );
		}

		if ( ! in_array( $width, array( 'boxed', 'full' ), true ) ) {
			throw new \InvalidArgumentException( esc_html( "Unknown section width '{$width}'." ) );
		}

		$panel = $extra['panel'] ?? true;
		$side  = 'page' !== $surface && $panel ? self::PANEL_GUTTER : 'var(--forma-gutter)';

		unset( $extra['panel'] );

		$settings = array(
			'content_width'         => $width,
			'flex_direction'        => 'column',
			'flex_gap'              => Builder::gap( 0 ),
			'padding'               => Builder::box( 'var(--forma-section)', $side, 'var(--forma-section)', $side, 'custom' ),
			'background_background' => 'classic',
			'__globals__'           => array( 'background_color' => self::color( $surface ) ),
		);

		$classes = array();

		if ( 'page' !== $surface && $panel ) {
			$classes[] = 'forma-panel';
		}

		if ( 'deep' === $surface ) {
			$classes[] = 'forma-deep';
		}

		if ( $classes ) {
			$settings['css_classes'] = implode( ' ', $classes );
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

	/** A button in the Kit's button style: an Ink pill with Paper text in Label 600, a Signal blue fill on hover. */
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
