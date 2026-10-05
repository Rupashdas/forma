<?php

namespace Forma\Engine\Seo;

use Forma\Engine\Contracts\Module;
use Forma\Engine\Projects\Projects;
use Forma\Engine\Support\Editor;

defined( 'ABSPATH' ) || exit;

/**
 * The search and sharing basics without an SEO plugin: one meta description per page, Open Graph and Twitter tags,
 * and a JSON-LD graph (the studio, the site, for projects the work itself plus its breadcrumb trail, and for the
 * Services page its FAQ as a FAQPage).
 * Descriptions come from excerpts, which every page and project already carries.
 */
final class Seo implements Module {

	private const MAX = 160;

	public static function id(): string {
		return 'seo';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_post_type_support( 'page', 'excerpt' );
		add_filter( 'hello_elementor_description_meta_tag', '__return_false' );
		add_action( 'wp_head', array( $this, 'head' ), 1 );
		add_action( 'wp_footer', array( $this, 'json_ld' ), 20 );
	}

	public static function description(): string {
		if ( is_front_page() ) {
			$text = (string) get_post_field( 'post_excerpt', (int) get_option( 'page_on_front' ) );
		} elseif ( is_singular() ) {
			$post = get_queried_object();
			$text = $post->post_excerpt ?: wp_trim_words( strip_shortcodes( $post->post_content ), 30, '' );
		} elseif ( is_post_type_archive( Projects::POST_TYPE ) ) {
			$text = get_post_type_object( Projects::POST_TYPE )->description;
		} elseif ( is_tax() ) {
			$term = get_queried_object();
			$text = $term->description ?: sprintf( '%s projects by %s.', $term->name, get_bloginfo( 'name' ) );
		} else {
			$text = get_bloginfo( 'description' );
		}

		return self::trim( (string) $text );
	}

	/**
	 * The sharing image: the page's own featured image, or Home's.
	 *
	 * @return array{0: string, 1: int, 2: int, 3: string}|null URL, width, height, alt.
	 */
	public static function image(): ?array {
		$id = is_singular() ? (int) get_post_thumbnail_id( get_queried_object_id() ) : 0;
		$id = $id ?: (int) get_post_thumbnail_id( (int) get_option( 'page_on_front' ) );

		$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;

		return $src ? array( $src[0], (int) $src[1], (int) $src[2], (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : null;
	}

	public function head(): void {
		if ( is_admin() || is_feed() || Editor::active() ) {
			return;
		}

		$description = self::description();
		$image       = self::image();
		$tags        = array(
			array( 'name', 'description', $description ),
			array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
			array( 'property', 'og:locale', 'en_GB' ),
			array( 'property', 'og:type', is_singular( Projects::POST_TYPE ) ? 'article' : 'website' ),
			array( 'property', 'og:title', wp_get_document_title() ),
			array( 'property', 'og:description', $description ),
			array( 'property', 'og:url', self::url() ),
			array( 'name', 'twitter:card', $image ? 'summary_large_image' : 'summary' ),
		);

		if ( $image ) {
			array_push(
				$tags,
				array( 'property', 'og:image', $image[0] ),
				array( 'property', 'og:image:width', (string) $image[1] ),
				array( 'property', 'og:image:height', (string) $image[2] ),
				array( 'property', 'og:image:alt', $image[3] )
			);
		}

		foreach ( $tags as [ $attribute, $key, $value ] ) {
			if ( '' !== $value ) {
				printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attribute ), esc_attr( $key ), esc_attr( $value ) );
			}
		}
	}

	public function json_ld(): void {
		if ( is_admin() || is_feed() || Editor::active() ) {
			return;
		}

		$home  = home_url( '/' );
		$graph = array(
			array(
				'@type'   => 'Organization',
				'@id'     => $home . '#studio',
				'name'    => get_bloginfo( 'name' ),
				'url'     => $home,
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => 'Lisbon',
					'addressCountry'  => 'PT',
				),
			),
			array(
				'@type'     => 'WebSite',
				'@id'       => $home . '#website',
				'url'       => $home,
				'name'      => get_bloginfo( 'name' ),
				'publisher' => array( '@id' => $home . '#studio' ),
			),
		);

		if ( is_singular( Projects::POST_TYPE ) ) {
			array_push( $graph, ...$this->project( get_queried_object(), $home ) );
		}

		if ( is_page( 'services' ) ) {
			$graph[] = $this->faq();
		}

		printf(
			'<script type="application/ld+json" id="forma-schema">%s</script>' . "\n",
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
			)
		);
	}

	/**
	 * The Services page's FAQPage: the same questions and answers its accordion shows, from data/site.php, as plain text.
	 *
	 * @return array<string, mixed>
	 */
	private function faq(): array {
		$site      = require FORMA_ENGINE_PATH . 'data/site.php';
		$questions = array();

		foreach ( (array) ( $site['faq'] ?? array() ) as $entry ) {
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => (string) $entry['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => (string) $entry['a'],
				),
			);
		}

		return array(
			'@type'      => 'FAQPage',
			'@id'        => trailingslashit( (string) get_permalink() ) . '#faq',
			'mainEntity' => $questions,
		);
	}

	/**
	 * @return array<int, array<string, mixed>> The CreativeWork and its BreadcrumbList.
	 */
	private function project( \WP_Post $project, string $home ): array {
		$url      = get_permalink( $project );
		$image    = self::image();
		$type     = get_the_terms( $project, Projects::TYPE_TAX );
		$location = get_the_terms( $project, Projects::LOCATION_TAX );
		$work     = array(
			'@type'       => 'CreativeWork',
			'@id'         => $url . '#work',
			'name'        => get_the_title( $project ),
			'description' => self::trim( $project->post_excerpt ),
			'url'         => $url,
			'dateCreated' => get_the_date( 'Y-m-d', $project ),
			'creator'     => array( '@id' => $home . '#studio' ),
		);

		if ( $image ) {
			$work['image'] = $image[0];
		}

		if ( $type && ! is_wp_error( $type ) ) {
			$work['genre'] = $type[0]->name;
		}

		if ( $location && ! is_wp_error( $location ) ) {
			$work['locationCreated'] = array(
				'@type' => 'Place',
				'name'  => $location[0]->name,
			);
		}

		$crumbs = array(
			array( __( 'Home', 'forma-studio-engine' ), $home ),
			array( get_post_type_object( Projects::POST_TYPE )->labels->name, get_post_type_archive_link( Projects::POST_TYPE ) ),
			array( get_the_title( $project ), $url ),
		);

		return array(
			$work,
			array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array_map(
					static fn( array $crumb, int $index ) => array(
						'@type'    => 'ListItem',
						'position' => $index + 1,
						'name'     => $crumb[0],
						'item'     => $crumb[1],
					),
					$crumbs,
					array_keys( $crumbs )
				),
			),
		);
	}

	private static function url(): string {
		if ( is_singular() ) {
			return (string) get_permalink( get_queried_object_id() );
		}

		if ( is_post_type_archive( Projects::POST_TYPE ) ) {
			return (string) get_post_type_archive_link( Projects::POST_TYPE );
		}

		if ( is_tax() ) {
			$link = get_term_link( get_queried_object() );
			return is_wp_error( $link ) ? home_url( '/' ) : $link;
		}

		return home_url( '/' );
	}

	/**
	 * Plain text, one line, at most 160 characters, cut at a word.
	 */
	private static function trim( string $text ): string {
		$text = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );

		if ( mb_strlen( $text ) <= self::MAX ) {
			return $text;
		}

		$cut = mb_substr( $text, 0, self::MAX - 1 );

		return rtrim( mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) ), ' ,.;:' ) . '…';
	}
}
