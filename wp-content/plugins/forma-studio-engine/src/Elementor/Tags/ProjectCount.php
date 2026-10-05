<?php

namespace Forma\Engine\Elementor\Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as DynamicTags;
use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * "11 projects": how many projects the archive shows. On a project type archive it counts that type, anywhere else
 * (the projects archive, the editor's preview of it) every published project.
 */
final class ProjectCount extends Tag {

	public function get_name() {
		return 'forma-project-count';
	}

	public function get_title() {
		return esc_html__( 'Project count', 'forma-studio-engine' );
	}

	public function get_group() {
		return 'forma';
	}

	public function get_categories() {
		return array( DynamicTags::TEXT_CATEGORY );
	}

	public function render() {
		$term  = is_tax( Projects::TYPE_TAX ) ? get_queried_object() : null;
		$count = $term instanceof \WP_Term ? (int) $term->count : (int) ( wp_count_posts( Projects::POST_TYPE )->publish ?? 0 );

		echo esc_html(
			sprintf(
				/* translators: %d: number of projects. */
				_n( '%d project', '%d projects', $count, 'forma-studio-engine' ),
				$count
			)
		);
	}
}
