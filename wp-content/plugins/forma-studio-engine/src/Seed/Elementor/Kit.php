<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The Elementor Kit (Site Settings): the Chalk and bottle green Global Colors, the type scale as Global Fonts, the
 * theme style that applies them to body text, links, headings, buttons, form fields and images, the layout defaults
 * and the breakpoints. Everything seeded afterwards references these globals instead of raw values.
 */
final class Kit {

	/** Width-expanded face of the sans, declared in the child theme's site.css (Elementor can't set the width axis). */
	private const SANS_EXPANDED = Style::SANS . ' Expanded';

	public function __construct( private \Closure $log ) {}

	/**
	 * Save the settings to the active kit.
	 *
	 * @return int The kit's post id.
	 */
	public function build(): int {
		$id = (int) \Elementor\Plugin::$instance->kits_manager->get_active_id();

		if ( ! $id ) {
			throw new \RuntimeException( 'There is no active Elementor kit.' );
		}

		Builder::reset( 'kit' );

		if ( Builder::save( $id, array(), $this->settings() ) ) {
			( $this->log )( 'Kit: 10 colours, 8 fonts, theme style, layout and breakpoints.' );
		} else {
			( $this->log )( 'Kit: ' . Builder::SKIPPED );
		}

		return $id;
	}

	/** The complete kit settings, as Elementor stores them in the kit's page settings. */
	public function settings(): array {
		$site = require FORMA_ENGINE_PATH . 'data/site.php';

		return array_merge(
			array(
				'site_name'        => $site['blog']['name'],
				'site_description' => $site['blog']['description'],
			),
			$this->colors(),
			$this->fonts(),
			$this->theme_style(),
			$this->layout(),
			$this->breakpoints()
		);
	}

	private function colors(): array {
		$color = static fn( string $id, string $title, string $value ): array => array(
			'_id'   => $id,
			'title' => $title,
			'color' => $value,
		);

		return array(
			'system_colors' => array(
				$color( 'primary', 'Chalk', '#F2EFE8' ),
				$color( 'secondary', 'Ink', '#161917' ),
				$color( 'text', 'Slate', '#575C57' ),
				$color( 'accent', 'Brass', '#7D5C1D' ),
			),
			'custom_colors' => array(
				$color( 'raised', 'Raised', '#E7E3D9' ),
				$color( 'deep', 'Bottle', '#1E3A2F' ),
				$color( 'sage', 'Sage', '#A9B8AE' ),
				$color( 'brasslight', 'Brass light', '#D2AE63' ),
				$color( 'line', 'Line', 'rgba(22,25,23,0.14)' ),
				$color( 'linedeep', 'Line on deep', 'rgba(242,239,232,0.16)' ),
			),
		);
	}

	private function fonts(): array {
		$font = static function ( string $id, string $title, string $family, string $weight, array $style ): array {
			$item = array(
				'_id'                    => $id,
				'title'                  => $title,
				'typography_typography'  => 'custom',
				'typography_font_family' => $family,
				'typography_font_weight' => $weight,
			);

			foreach ( $style as $key => $value ) {
				$item[ "typography_{$key}" ] = $value;
			}

			return $item;
		};
		$clamp = static fn( string $css ): array => Builder::size( $css, 'custom' );
		$em    = static fn( int|float $value ): array => Builder::size( $value, 'em' );

		return array(
			'system_typography'     => array(
				$font(
					'primary',
					'Display XL',
					Style::DISPLAY,
					'400',
					array(
						'font_size'      => $clamp( 'clamp(80px, 16vw, 240px)' ),
						'line_height'    => $em( 0.85 ),
						'letter_spacing' => $em( -0.02 ),
					)
				),
				$font(
					'secondary',
					'Display L',
					Style::DISPLAY,
					'400',
					array(
						'font_size'      => $clamp( 'clamp(56px, 9vw, 160px)' ),
						'line_height'    => $em( 0.92 ),
						'letter_spacing' => $em( -0.02 ),
					)
				),
				$font(
					'text',
					'Body',
					Style::SANS,
					'400',
					array(
						'font_size'        => Builder::size( 17 ),
						'font_size_mobile' => Builder::size( 16 ),
						'line_height'      => $em( 1.6 ),
					)
				),
				$font(
					'accent',
					'Label',
					self::SANS_EXPANDED,
					'500',
					array(
						'font_size'      => Builder::size( 12 ),
						'line_height'    => $em( 1.3 ),
						'letter_spacing' => $em( 0.08 ),
						'text_transform' => 'uppercase',
					)
				),
			),
			'custom_typography'     => array(
				$font(
					'heading',
					'Heading',
					Style::DISPLAY,
					'400',
					array(
						'font_size'      => $clamp( 'clamp(40px, 5vw, 80px)' ),
						'line_height'    => $em( 1 ),
						'letter_spacing' => $em( -0.01 ),
					)
				),
				$font(
					'statement',
					'Statement',
					Style::DISPLAY,
					'400',
					array(
						'font_size'   => $clamp( 'clamp(28px, 3.4vw, 50px)' ),
						'line_height' => $em( 1.15 ),
					)
				),
				$font(
					'subheading',
					'Subheading',
					Style::SANS,
					'500',
					array(
						'font_size'   => $clamp( 'clamp(20px, 1.8vw, 26px)' ),
						'line_height' => $em( 1.25 ),
					)
				),
				$font(
					'meta',
					'Meta',
					Style::SANS,
					'400',
					array(
						'font_size'   => Builder::size( 13 ),
						'line_height' => $em( 1.4 ),
					)
				),
			),
			'default_generic_fonts' => 'serif',
		);
	}

	/**
	 * Body, links, headings, buttons, form fields and images. Colours and fonts are references to the globals above.
	 */
	private function theme_style(): array {
		$globals = array(
			// Body and links.
			'body_background_color'            => Style::color( 'page' ),
			'body_color'                       => Style::color( 'ink' ),
			'body_typography_typography'       => Style::font( 'body' ),
			'link_normal_color'                => Style::color( 'ink' ),
			'link_hover_color'                 => Style::color( 'accent' ),

			// Buttons: Label type, outlined in Ink, filled with Ink on hover.
			'button_typography_typography'     => Style::font( 'label' ),
			'button_text_color'                => Style::color( 'ink' ),
			'button_border_color'              => Style::color( 'ink' ),
			'button_hover_text_color'          => Style::color( 'page' ),
			'button_hover_background_color'    => Style::color( 'ink' ),
			'button_hover_border_color'        => Style::color( 'ink' ),

			// Form fields: Raised surface, Line border, Slate labels.
			'form_label_color'                 => Style::color( 'muted' ),
			'form_label_typography_typography' => Style::font( 'meta' ),
			'form_field_typography_typography' => Style::font( 'body' ),
			'form_field_text_color'            => Style::color( 'ink' ),
			'form_field_background_color'      => Style::color( 'raised' ),
			'form_field_border_color'          => Style::color( 'line' ),
		);

		// H1 is Display L, H2 Heading, H3 to H6 Subheading; all Ink.
		foreach ( array( 'h1' => 'display-l', 'h2' => 'heading', 'h3' => 'subheading', 'h4' => 'subheading', 'h5' => 'subheading', 'h6' => 'subheading' ) as $level => $font ) {
			$globals[ "{$level}_color" ]                = Style::color( 'ink' );
			$globals[ "{$level}_typography_typography" ] = Style::font( $font );
		}

		return array(
			'body_background_background'         => 'classic',
			'button_background_background'       => 'classic',
			'button_background_color'            => 'rgba(0,0,0,0)',
			'button_border_border'               => 'solid',
			'button_border_width'                => Builder::box( 1 ),
			'button_border_radius'               => Builder::box( 0 ),
			'button_padding'                     => Builder::box( 18, 28 ),
			'button_hover_background_background' => 'classic',
			'form_field_border_border'           => 'solid',
			'form_field_border_width'            => Builder::box( 1 ),
			'form_field_border_radius'           => Builder::box( 0 ),
			'form_field_padding'                 => Builder::box( 16 ),
			'image_border_radius'                => Builder::box( 0 ),
			'__globals__'                        => $globals,
		);
	}

	private function layout(): array {
		return array(
			// The boxed content width: every section's content sits in a column this wide, centred (see Style::section()).
			'container_width'           => Builder::size( 1320 ),
			'container_padding'         => Builder::box( 0 ),
			'space_between_widgets'     => Builder::gap( 0 ),
			'default_page_template'     => 'elementor_header_footer',
			'page_title_selector'       => 'h1.entry-title',
			'mobile_browser_background' => '#F2EFE8',
		);
	}

	/** Mobile up to 767, Tablet up to 1023, Laptop up to 1439, Desktop from 1440. */
	private function breakpoints(): array {
		return array(
			'active_breakpoints' => array( 'viewport_mobile', 'viewport_tablet', 'viewport_laptop' ),
			'viewport_mobile'    => 767,
			'viewport_tablet'    => 1023,
			'viewport_laptop'    => 1439,
		);
	}
}
