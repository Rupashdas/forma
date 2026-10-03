<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class DataTest extends TestCase {

	private array $projects;
	private array $site;

	protected function set_up(): void {
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
		$this->site     = require FORMA_ENGINE_PATH . 'data/site.php';
	}

	public function test_eleven_projects_with_unique_slugs_in_date_order(): void {
		$this->assert_same( 11, count( $this->projects ) );

		$slugs = array_column( $this->projects, 'slug' );
		$this->assert_same( $slugs, array_values( array_unique( $slugs ) ), 'slugs are unique' );

		$dates  = array_column( $this->projects, 'date' );
		$sorted = $dates;
		sort( $sorted );
		$this->assert_same( $sorted, $dates, 'projects are listed oldest first' );
	}

	public function test_every_project_is_complete(): void {
		$keys = array( 'slug', 'title', 'type', 'location', 'date', 'excerpt', 'facts', 'labels', 'site', 'idea', 'result', 'quote', 'recognition', 'layout', 'drawing', 'featured' );

		foreach ( $this->projects as $project ) {
			$slug = $project['slug'];

			$this->assert_same( $keys, array_keys( $project ), "{$slug} keys" );
			$this->assert_true( isset( $this->site['types'][ $project['type'] ] ), "{$slug} type '{$project['type']}' exists" );
			$this->assert_true( 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $project['date'] ), "{$slug} date is Y-m-d" );
			$this->assert_true( 1 === preg_match( '/^[^,]+, [^,]+$/', $project['location'] ), "{$slug} location is 'City, Country'" );
			$this->assert_true( mb_strlen( $project['excerpt'] ) <= 160, "{$slug} excerpt fits a meta description" );
			$this->assert_same( array( 'Client', 'Area', 'Status', 'Team' ), array_keys( $project['facts'] ), "{$slug} facts" );
			$this->assert_same( 3, count( $project['labels'] ), "{$slug} has three section labels" );
			$this->assert_same( array( 'text', 'cite' ), array_keys( $project['quote'] ), "{$slug} quote" );
			$this->assert_true( in_array( $project['layout'], array( 'a', 'b', 'c' ), true ), "{$slug} layout variant" );

			foreach ( array( 'title', 'excerpt', 'site', 'idea', 'result' ) as $field ) {
				$this->assert_true( '' !== trim( $project[ $field ] ), "{$slug} {$field} is written" );
			}
		}
	}

	public function test_five_featured_projects_numbered_one_to_five(): void {
		$featured = array_values( array_filter( array_column( $this->projects, 'featured' ) ) );
		sort( $featured );

		$this->assert_same( array( 1, 2, 3, 4, 5 ), $featured );
	}

	public function test_three_projects_have_the_drawing_comparison(): void {
		$drawing = array_values( array_column( array_filter( $this->projects, static fn( array $p ) => $p['drawing'] ), 'slug' ) );

		$this->assert_same( array( 'concrete-garden', 'atelier-27', 'casa-nera' ), $drawing );
	}

	public function test_site_pages_and_menus_reference_real_pages(): void {
		$this->assert_same( array( 'home', 'studio', 'services', 'process', 'contact', 'colophon' ), array_keys( $this->site['pages'] ) );
		$this->assert_true( isset( $this->site['pages'][ $this->site['front_page'] ] ), 'front page is a seeded page' );

		foreach ( $this->site['menus'] as $menu ) {
			foreach ( $menu['items'] as $item ) {
				[ $kind, $value ] = explode( ':', $item, 2 );
				$ok               = 'archive' === $kind ? 'forma_project' === $value : isset( $this->site['pages'][ $value ] );
				$this->assert_true( $ok, "menu item {$item} resolves" );
			}
		}
	}

	public function test_no_placeholder_copy(): void {
		$all = (string) wp_json_encode( array( $this->projects, $this->site ) );

		foreach ( array( 'lorem', 'ipsum', 'placeholder', 'TODO', 'TBD', 'example.com' ) as $word ) {
			$this->assert_true( false === stripos( $all, $word ), "no '{$word}' in the copy" );
		}
	}
}
