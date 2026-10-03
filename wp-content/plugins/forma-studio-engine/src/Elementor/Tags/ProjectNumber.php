<?php

namespace Forma\Engine\Elementor\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as DynamicTags;
use Forma\Engine\Projects\ProjectNumber as Number;

defined( 'ABSPATH' ) || exit;

/**
 * "No. 08": the current project's sheet number, for headings and title blocks in Theme Builder templates and loops.
 */
final class ProjectNumber extends Tag {

	public function get_name() {
		return 'forma-project-number';
	}

	public function get_title() {
		return esc_html__( 'Project number', 'forma-studio-engine' );
	}

	public function get_group() {
		return 'forma';
	}

	public function get_categories() {
		return array( DynamicTags::TEXT_CATEGORY );
	}

	protected function register_controls() {
		$this->add_control(
			'prefix',
			array(
				'label'   => esc_html__( 'Prefix', 'forma-studio-engine' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'No. ',
			)
		);
	}

	public function render() {
		$number = Number::for_post( (int) get_the_ID() );

		if ( '' !== $number ) {
			echo esc_html( (string) $this->get_settings( 'prefix' ) . $number );
		}
	}
}
