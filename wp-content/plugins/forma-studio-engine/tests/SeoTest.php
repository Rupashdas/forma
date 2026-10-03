<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seo\Seo;

defined( 'ABSPATH' ) || exit;

final class SeoTest extends TestCase {

	private array $globals = array();

	protected function set_up(): void {
		$this->globals = array( $GLOBALS['wp_query'] ?? null, $GLOBALS['wp_the_query'] ?? null, $GLOBALS['post'] ?? null );
	}

	protected function tear_down(): void {
		[ $GLOBALS['wp_query'], $GLOBALS['wp_the_query'], $GLOBALS['post'] ] = $this->globals;
	}

	public function test_project_description_is_its_excerpt(): void {
		$project = $this->visit_project( 'casa-nera' );

		$this->assert_same( $project->post_excerpt, Seo::description() );
	}

	public function test_projects_archive_has_a_short_description(): void {
		$this->visit( array( 'post_type' => 'forma_project' ) );
		$description = Seo::description();

		$this->assert_true( is_post_type_archive( 'forma_project' ), 'archive query' );
		$this->assert_true( '' !== $description && mb_strlen( $description ) <= 160, "archive description set and ≤160: {$description}" );
	}

	public function test_project_head_has_one_description_and_social_tags(): void {
		$this->visit_project( 'casa-nera' );
		$head = $this->capture( 'wp_head' );

		$this->assert_same( 1, substr_count( $head, 'name="description"' ), 'exactly one meta description (Hello\'s is off)' );
		$this->assert_true( str_contains( $head, 'property="og:type" content="article"' ), 'og:type article' );
		$this->assert_true( 1 === preg_match( '/property="og:image" content="[^"]+casa-nera-01[^"]*\.webp"/', $head ), 'og:image is the project image' );
		$this->assert_true( str_contains( $head, 'name="twitter:card" content="summary_large_image"' ), 'twitter card' );
	}

	public function test_project_json_ld_describes_the_work_and_its_breadcrumbs(): void {
		$project = $this->visit_project( 'casa-nera' );
		$graph   = $this->json_ld();
		$types   = array_column( $graph, '@type' );

		foreach ( array( 'Organization', 'WebSite', 'CreativeWork', 'BreadcrumbList' ) as $type ) {
			$this->assert_true( in_array( $type, $types, true ), "graph has {$type}" );
		}

		$work = $graph[ array_search( 'CreativeWork', $types, true ) ];
		$this->assert_same( 'Casa Nera', $work['name'] );
		$this->assert_same( '2024-06-14', $work['dateCreated'] );
		$this->assert_same( 'Comporta, Portugal', $work['locationCreated']['name'] ?? null );

		$crumbs = $graph[ array_search( 'BreadcrumbList', $types, true ) ]['itemListElement'];
		$this->assert_same( array( 'Home', 'Projects', 'Casa Nera' ), array_column( $crumbs, 'name' ) );
		$this->assert_same( get_permalink( $project ), $crumbs[2]['item'] );
	}

	private function visit_project( string $slug ): \WP_Post {
		$project = get_page_by_path( $slug, OBJECT, 'forma_project' );
		$this->visit(
			array(
				'p'         => $project->ID,
				'post_type' => 'forma_project',
			)
		);
		$GLOBALS['post'] = $project;
		setup_postdata( $project );

		return $project;
	}

	private function visit( array $query ): void {
		$GLOBALS['wp_the_query'] = new \WP_Query( $query );
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];
	}

	private function capture( string $hook ): string {
		ob_start();
		do_action( $hook );
		return (string) ob_get_clean();
	}

	private function json_ld(): array {
		preg_match( '#<script type="application/ld\+json" id="forma-schema">(.+?)</script>#s', $this->capture( 'wp_footer' ), $match );
		$data = json_decode( $match[1] ?? '', true );

		$this->assert_true( is_array( $data ), 'JSON-LD decodes' );

		return $data['@graph'] ?? array();
	}
}
