<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class ProjectsTest extends TestCase {

	public function test_post_type_is_public_with_archive_and_elementor_support(): void {
		$type = get_post_type_object( 'forma_project' );

		$this->assert_true( null !== $type, 'forma_project is registered' );
		$this->assert_true( $type->public, 'forma_project is public' );
		$this->assert_true( $type->show_in_rest, 'forma_project is in the REST API' );
		$this->assert_same( 'projects', $type->has_archive );
		$this->assert_same( 'projects', $type->rewrite['slug'] ?? null );

		foreach ( array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'elementor' ) as $feature ) {
			$this->assert_true( post_type_supports( 'forma_project', $feature ), "forma_project supports {$feature}" );
		}

		$this->assert_true( ! post_type_supports( 'forma_project', 'comments' ), 'forma_project has no comments' );
	}

	public function test_taxonomies_are_attached(): void {
		$taxonomies = get_object_taxonomies( 'forma_project' );
		sort( $taxonomies );

		$this->assert_same( array( 'project_location', 'project_type' ), $taxonomies );
		$this->assert_true( get_taxonomy( 'project_type' )->hierarchical, 'project_type is hierarchical' );
		$this->assert_true( ! get_taxonomy( 'project_location' )->hierarchical, 'project_location is flat' );
		$this->assert_true( get_taxonomy( 'project_type' )->show_admin_column, 'project_type shows in the admin list' );
	}

	public function test_urls_route_to_the_right_queries(): void {
		$this->assert_true( $GLOBALS['wp_rewrite']->using_permalinks(), 'pretty permalinks are on (Task 0 step 6)' );

		$this->assert_true( str_contains( $this->route( 'projects/' ), 'post_type=forma_project' ), 'projects/ → archive' );
		$this->assert_true( str_contains( $this->route( 'projects/casa-nera/' ), 'forma_project=' ), 'projects/casa-nera/ → single' );
		$this->assert_true( str_contains( $this->route( 'projects/type/residential/' ), 'project_type=' ), 'projects/type/residential/ → type archive' );
		$this->assert_true( str_contains( $this->route( 'projects/location/kyoto-japan/' ), 'project_location=' ), 'projects/location/… → location archive' );
	}

	/**
	 * The query string of the first rewrite rule matching $path, the way WP::parse_request() matches.
	 */
	private function route( string $path ): string {
		foreach ( $GLOBALS['wp_rewrite']->rewrite_rules() as $regex => $query ) {
			if ( preg_match( "#^{$regex}#", $path ) ) {
				return $query;
			}
		}

		return '';
	}
}
