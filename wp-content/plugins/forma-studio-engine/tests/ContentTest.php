<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

final class ContentTest extends TestCase {

	private Content $content;

	protected function set_up(): void {
		$this->content = new Content( static function ( string $message ): void {} );
	}

	public function test_run_creates_eleven_published_projects_with_terms(): void {
		$this->content->run();

		$this->assert_same( 11, count( $this->seeded( 'forma_project' ) ) );

		$casa = get_post( $this->content->project_id( 'casa-nera' ) );
		$this->assert_same( 'Casa Nera', $casa->post_title );
		$this->assert_same( 'publish', $casa->post_status );
		$this->assert_same( '2024', get_the_date( 'Y', $casa ) );
		$this->assert_true( '' !== $casa->post_excerpt, 'Casa Nera has an excerpt' );
		$this->assert_same( array( 'residential' ), wp_get_object_terms( $casa->ID, 'project_type', array( 'fields' => 'slugs' ) ) );
		$this->assert_same( array( 'Comporta, Portugal' ), wp_get_object_terms( $casa->ID, 'project_location', array( 'fields' => 'names' ) ) );
		$this->assert_same( 'http://forma.local/projects/casa-nera/', get_permalink( $casa ) );
	}

	public function test_run_is_idempotent(): void {
		$this->content->run();
		$this->content->run();

		$this->assert_same( 11, count( $this->seeded( 'forma_project' ) ) );
		$this->assert_same( 6, count( $this->seeded( 'page' ) ) );
		$this->assert_same( 6, count( get_terms( array( 'taxonomy' => 'project_type', 'hide_empty' => false ) ) ) );
		$this->assert_same( 5, count( wp_get_nav_menu_items( wp_get_nav_menu_object( 'Primary' )->term_id ) ) );
	}

	public function test_front_page_and_menus(): void {
		$this->content->run();

		$this->assert_same( 'page', get_option( 'show_on_front' ) );
		$this->assert_same( $this->content->page_id( 'home' ), (int) get_option( 'page_on_front' ) );

		$primary = wp_get_nav_menu_object( 'Primary' );
		$items   = wp_get_nav_menu_items( $primary->term_id );

		$this->assert_same( array( 'Projects', 'Studio', 'Services', 'Process', 'Contact' ), wp_list_pluck( $items, 'title' ) );
		$this->assert_same( get_post_type_archive_link( 'forma_project' ), $items[0]->url );
		$this->assert_same( (int) $primary->term_id, get_nav_menu_locations()['menu-1'] ?? 0 );
		$this->assert_same( 'Colophon', wp_list_pluck( wp_get_nav_menu_items( wp_get_nav_menu_object( 'Footer' )->term_id ), 'title' )[5] ?? null );
	}

	public function test_elementor_bodies_are_not_overwritten(): void {
		$this->content->run();

		$id = $this->content->project_id( 'casa-nera' );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => 'Built in Elementor',
			)
		);

		$this->content->run();

		$this->assert_same( 'Built in Elementor', get_post_field( 'post_content', $id ) );
	}

	/**
	 * @return int[]
	 */
	private function seeded( string $post_type ): array {
		return get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
			)
		);
	}
}
