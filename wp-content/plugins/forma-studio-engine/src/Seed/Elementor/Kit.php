<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The Elementor Kit (Site Settings): the Paper, Panel, Ink and Signal blue Global Colors, the medium-weight Archivo type
 * scale as Global Fonts, the theme style that applies them to body text, links, headings, Ink pill buttons, form fields
 * and images, the layout defaults and the breakpoints. Everything seeded afterwards references these globals instead
 * of raw values.
 */
final class Kit {

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
			( $this->log )( 'Kit: 13 colours, 8 fonts, theme style, layout and breakpoints.' );
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
				$color( 'primary', 'Paper', '#F6F5F1' ),
				$color( 'secondary', 'Ink', '#141414' ),
				$color( 'text', 'Graphite', '#55554F' ),
				$color( 'accent', 'Signal blue', '#2B3BFF' ),
			),
			'custom_colors' => array(
				$color( 'raised', 'Panel', '#E9E8E3' ),
				$color( 'deep', 'Ink band', '#141414' ),
				$color( 'ash', 'Ash', '#A3A39C' ),
				$color( 'lightblue', 'Light blue', '#8E98FF' ),
				$color( 'line', 'Line', 'rgba(20,20,20,0.12)' ),
				$color( 'linedeep', 'Line on deep', 'rgba(246,245,241,0.16)' ),
				$color( 'chip', 'Chip', '#DCE0FF' ),
				$color( 'chipink', 'Chip ink', '#1B2799' ),
				$color( 'deepraised', 'Ink tile', '#222220' ),
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
					'Display XXL',
					Style::DISPLAY,
					'500',
					array(
						'font_size'      => $clamp( 'clamp(44px, 6.4vw, 104px)' ),
						'line_height'    => $em( 1 ),
						'letter_spacing' => $em( -0.035 ),
					)
				),
				$font(
					'secondary',
					'Display L',
					Style::DISPLAY,
					'500',
					array(
						'font_size'      => $clamp( 'clamp(40px, 5.2vw, 84px)' ),
						'line_height'    => $em( 1.02 ),
						'letter_spacing' => $em( -0.03 ),
					)
				),
				$font(
					'text',
					'Body',
					Style::SANS,
					'400',
					array(
						'font_size'        => Builder::size( 16 ),
						'font_size_mobile' => Builder::size( 15 ),
						'line_height'      => $em( 1.55 ),
					)
				),
				$font(
					'accent',
					'Label',
					Style::SANS,
					'500',
					array(
						'font_size'      => Builder::size( 13 ),
						'line_height'    => $em( 1.3 ),
						'text_transform' => 'none',
					)
				),
			),
			'custom_typography'     => array(
				$font(
					'heading',
					'Heading',
					Style::DISPLAY,
					'500',
					array(
						'font_size'      => $clamp( 'clamp(28px, 3.2vw, 48px)' ),
						'line_height'    => $em( 1.08 ),
						'letter_spacing' => $em( -0.025 ),
					)
				),
				$font(
					'subheading',
					'Subheading',
					Style::SANS,
					'600',
					array(
						'font_size'      => $clamp( 'clamp(18px, 1.6vw, 22px)' ),
						'line_height'    => $em( 1.25 ),
						'letter_spacing' => $em( -0.01 ),
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
				$font(
					'chip',
					'Chip',
					Style::SANS,
					'600',
					array(
						'font_size'   => Builder::size( 12 ),
						'line_height' => $em( 1 ),
					)
				),
			),
			'default_generic_fonts' => 'sans-serif',
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

			// Buttons: an Ink pill with Paper text, a Signal blue fill on hover. Every colour is a global, so inside the
			// Ink band, where the theme re-points Ink to Paper and Paper to the band, the pill flips to a Paper fill with
			// Ink text and still reads. The typography is local (Label size at weight 600), not the Label global.
			'button_background_color'          => Style::color( 'ink' ),
			'button_text_color'                => Style::color( 'page' ),
			'button_hover_text_color'          => Style::color( 'page' ),
			'button_hover_background_color'    => Style::color( 'accent' ),

			// Form fields are left to Defer Forms for Contact Form 7, whose design settings (Contact::form_design()) give
			// every form the FORMA look; Kit field styles would override its controls.
		);

		// H1 is Display L, H2 Heading, H3 to H6 Subheading; all Ink.
		foreach ( array( 'h1' => 'display-l', 'h2' => 'heading', 'h3' => 'subheading', 'h4' => 'subheading', 'h5' => 'subheading', 'h6' => 'subheading' ) as $level => $font ) {
			$globals[ "{$level}_color" ]                = Style::color( 'ink' );
			$globals[ "{$level}_typography_typography" ] = Style::font( $font );
		}

		return array(
			'body_background_background'         => 'classic',
			'button_typography_typography'       => 'custom',
			'button_typography_font_family'      => Style::SANS,
			'button_typography_font_weight'      => '600',
			'button_typography_font_size'        => Builder::size( 13 ),
			'button_typography_line_height'      => Builder::size( 1.3, 'em' ),
			'button_typography_text_transform'   => 'none',
			'button_background_background'       => 'classic',
			'button_border_radius'               => Builder::box( 999 ),
			'button_padding'                     => Builder::box( 14, 22 ),
			'button_hover_background_background' => 'classic',
			'image_border_radius'                => Builder::box( 12 ),
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
			'mobile_browser_background' => '#F6F5F1',
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
