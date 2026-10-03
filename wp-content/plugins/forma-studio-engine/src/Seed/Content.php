<?php

namespace Forma\Engine\Seed;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * Creates project types, the projects, pages, the static front page and menus from data/*.php.
 *
 * Every post it creates carries a hidden `_forma_seed` key, so reruns update instead of duplicating.
 * Once a post has been built in Elementor, its body belongs to Elementor and is never overwritten here.
 */
final class Content {

	public const SEED_KEY = '_forma_seed';

	private array $projects;
	private array $site;

	public function __construct( private \Closure $log ) {
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
		$this->site     = require FORMA_ENGINE_PATH . 'data/site.php';
	}

	public function run(): void {
		$this->types();
		$this->projects();
		$this->pages();
		$this->front_page();
		$this->menus();
	}

	public function types(): void {
		foreach ( $this->site['types'] as $slug => $type ) {
			$existing = term_exists( $slug, Projects::TYPE_TAX );
			$args     = array(
				'slug'        => $slug,
				'description' => $type['description'],
			);

			if ( $existing ) {
				wp_update_term( (int) $existing['term_id'], Projects::TYPE_TAX, $args + array( 'name' => $type['name'] ) );
			} else {
				wp_insert_term( $type['name'], Projects::TYPE_TAX, $args );
			}
		}

		( $this->log )( sprintf( '%d project types.', count( $this->site['types'] ) ) );
	}

	public function projects(): void {
		foreach ( $this->projects as $project ) {
			$id = $this->upsert(
				'project:' . $project['slug'],
				array(
					'post_type'      => Projects::POST_TYPE,
					'post_status'    => 'publish',
					'post_title'     => $project['title'],
					'post_name'      => $project['slug'],
					'post_excerpt'   => $project['excerpt'],
					'post_content'   => $this->fallback_body( $project ),
					'post_date'      => $project['date'] . ' 10:00:00',
					// The native "Order" field holds the project's place in the Home showcase (0 = not featured).
					'menu_order'     => (int) $project['featured'],
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			wp_set_object_terms( $id, $project['type'], Projects::TYPE_TAX );
			wp_set_object_terms( $id, $project['location'], Projects::LOCATION_TAX );
		}

		( $this->log )( sprintf( '%d projects.', count( $this->projects ) ) );
	}

	public function pages(): void {
		foreach ( $this->site['pages'] as $slug => $page ) {
			$this->upsert(
				'page:' . $slug,
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => $page['title'],
					'post_name'      => $slug,
					'post_excerpt'   => $page['excerpt'],
					'post_content'   => '',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);
		}

		( $this->log )( sprintf( '%d pages.', count( $this->site['pages'] ) ) );
	}

	public function front_page(): void {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->page_id( $this->site['front_page'] ) );

		( $this->log )( 'Static front page: ' . $this->site['front_page'] . '.' );
	}

	/**
	 * Menus are rebuilt from scratch on each run; only the menu items this seeder created are replaced.
	 */
	public function menus(): void {
		$locations  = get_nav_menu_locations();
		$registered = get_registered_nav_menus();

		foreach ( $this->site['menus'] as $menu ) {
			$object  = wp_get_nav_menu_object( $menu['name'] );
			$menu_id = $object ? (int) $object->term_id : (int) wp_create_nav_menu( $menu['name'] );

			foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
				wp_delete_post( $item->ID, true );
			}

			foreach ( $menu['items'] as $index => $item ) {
				wp_update_nav_menu_item( $menu_id, 0, $this->menu_item( $item, $index + 1 ) );
			}

			if ( isset( $registered[ $menu['location'] ] ) ) {
				$locations[ $menu['location'] ] = $menu_id;
			}
		}

		set_theme_mod( 'nav_menu_locations', $locations );

		( $this->log )( sprintf( '%d menus.', count( $this->site['menus'] ) ) );
	}

	public function page_id( string $slug ): int {
		return $this->find( 'page', 'page:' . $slug );
	}

	public function project_id( string $slug ): int {
		return $this->find( Projects::POST_TYPE, 'project:' . $slug );
	}

	private function find( string $post_type, string $key ): int {
		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => self::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	private function upsert( string $key, array $postarr ): int {
		$existing = $this->find( $postarr['post_type'], $key );

		if ( $existing ) {
			$postarr['ID'] = $existing;

			if ( 'builder' === get_post_meta( $existing, '_elementor_edit_mode', true ) ) {
				unset( $postarr['post_content'] );
			}
		}

		// wp_update_post() merges with the stored post, so fields left out above (an Elementor body) survive;
		// wp_insert_post() with an ID would reset them to empty.
		$id = $existing ? wp_update_post( wp_slash( $postarr ), true ) : wp_insert_post( wp_slash( $postarr ), true );

		if ( is_wp_error( $id ) ) {
			throw new \RuntimeException( esc_html( $key . ': ' . $id->get_error_message() ) );
		}

		update_post_meta( $id, self::SEED_KEY, $key );

		return (int) $id;
	}

	/**
	 * Plain HTML used until the project's Elementor body is built; also what feeds and search read.
	 */
	private function fallback_body( array $project ): string {
		$html = '';

		foreach ( array( 'site', 'idea', 'result' ) as $index => $field ) {
			$html .= sprintf( "<h2>%s</h2>\n<p>%s</p>\n", esc_html( $project['labels'][ $index ] ), esc_html( $project[ $field ] ) );
		}

		return $html;
	}

	/**
	 * @param string $ref "archive:{post_type}" or "page:{slug}".
	 */
	private function menu_item( string $ref, int $position ): array {
		[ $kind, $value ] = explode( ':', $ref, 2 );

		if ( 'archive' === $kind ) {
			return array(
				'menu-item-type'     => 'post_type_archive',
				'menu-item-object'   => $value,
				'menu-item-title'    => get_post_type_object( $value )->labels->name,
				'menu-item-status'   => 'publish',
				'menu-item-position' => $position,
			);
		}

		$id = $this->page_id( $value );

		return array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $id,
			'menu-item-title'     => get_the_title( $id ),
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $position,
		);
	}
}
