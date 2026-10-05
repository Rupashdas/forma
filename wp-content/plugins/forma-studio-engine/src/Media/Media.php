<?php

namespace Forma\Engine\Media;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Leaner uploads for inode-limited hosting: generate only the sizes the layouts request
 * (150 thumbnail, 480 medium, 960, 1600 large, originals capped at 2400) and write them as WebP.
 */
final class Media implements Module {

	public const MAX_EDGE = 2400;

	/** Sizes no FORMA template or Elementor layout requests. */
	private const UNUSED_SIZES = array( 'medium_large', '1536x1536', '2048x2048' );

	public static function id(): string {
		return 'media';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_image_size( 'forma-960', 960, 0 );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'drop_unused_sizes' ) );
		add_filter( 'big_image_size_threshold', static fn() => self::MAX_EDGE );
		add_filter( 'image_editor_output_format', array( $this, 'webp_output' ) );
		add_filter( 'image_size_names_choose', array( $this, 'size_names' ) );
		add_filter( 'elementor/widget/render_content', array( $this, 'mark_hero_images' ), 10, 2 );
		add_filter( 'wp_content_img_tag', array( $this, 'load_hero_images' ), 99 );
		add_filter( 'wp_get_loading_optimization_attributes', array( $this, 'lazy_by_default' ), 20, 3 );
	}

	/**
	 * Every page here opens on type and a study model, never a photograph, so the photographs are all below the fold. WordPress
	 * cannot know that: it skips lazy-loading for the first few images of a page and gives the first large one a high fetch
	 * priority, which here sent the first three project cards of Home's strip (a swipe row far down the page) ahead of the
	 * styles and scripts. Photographs load lazily unless they are the marked hero layers.
	 *
	 * @param array<string, string> $attrs   The loading attributes WordPress chose.
	 * @param string                $tag     The tag name.
	 * @param array<string, mixed>  $attr    The tag's attributes.
	 * @return array<string, string>
	 */
	public function lazy_by_default( array $attrs, string $tag, array $attr ): array {
		if ( 'img' !== $tag || str_contains( (string) ( $attr['class'] ?? '' ), 'is-hero-' ) ) {
			return $attrs;
		}

		$attrs['loading'] = 'lazy';
		unset( $attrs['fetchpriority'] );

		return $attrs;
	}

	/**
	 * Home's hero stacks a line drawing under the photograph that wipes across it, and both are above the fold. The
	 * Image widgets carry a `forma-hero__drawing` or `forma-hero__photo` class; this marks the `<img>` itself, which
	 * is all the content filter below can see.
	 */
	public function mark_hero_images( string $content, \Elementor\Widget_Base $widget ): string {
		$classes = (string) $widget->get_settings( '_css_classes' );

		if ( 'image' !== $widget->get_name() || ! str_contains( $classes, 'forma-hero__' ) ) {
			return $content;
		}

		$tags = new \WP_HTML_Tag_Processor( $content );

		if ( ! $tags->next_tag( 'img' ) ) {
			return $content;
		}

		$tags->add_class( str_contains( $classes, 'forma-hero__photo' ) ? 'is-hero-photo' : 'is-hero-drawing' );

		return $this->load_hero_images( $tags->get_updated_html() );
	}

	/**
	 * Eager loading for both hero layers, and high fetch priority for the photograph alone. WordPress gives
	 * `fetchpriority` to the first large image of a page, which here would be the drawing, and adds a second one to a
	 * tag that already has it, so this runs after WordPress's own pass and settles it: one attribute, on the photograph.
	 */
	public function load_hero_images( string $image ): string {
		$photo = str_contains( $image, 'is-hero-photo' );

		if ( ! $photo && ! str_contains( $image, 'is-hero-drawing' ) ) {
			return $image;
		}

		$tags = new \WP_HTML_Tag_Processor( $image );

		if ( ! $tags->next_tag( 'img' ) ) {
			return $image;
		}

		$tags->remove_attribute( 'fetchpriority' );
		$tags->set_attribute( 'loading', 'eager' );

		if ( $photo ) {
			$tags->set_attribute( 'fetchpriority', 'high' );
		}

		return $tags->get_updated_html();
	}

	public function drop_unused_sizes( array $sizes ): array {
		return array_diff_key( $sizes, array_flip( self::UNUSED_SIZES ) );
	}

	/**
	 * @param array<string, string> $formats Source mime type => output mime type.
	 */
	public function webp_output( array $formats ): array {
		if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formats['image/jpeg'] = 'image/webp';
			$formats['image/png']  = 'image/webp';
		}

		return $formats;
	}

	/**
	 * Offer the 960 size in the editor's size pickers (Elementor's Image widget reads this list).
	 */
	public function size_names( array $names ): array {
		return $names + array( 'forma-960' => __( 'Half width (960)', 'forma-studio-engine' ) );
	}
}
