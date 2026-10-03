<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

final class ElementorTest extends TestCase {

	public function test_forma_widget_category_exists(): void {
		$categories = \Elementor\Plugin::$instance->elements_manager->get_categories();
		$this->assert_same( 'Forma', $categories['forma']['title'] ?? null );
	}

	public function test_project_number_tag_renders_for_the_current_project(): void {
		$tags = \Elementor\Plugin::$instance->dynamic_tags;
		$this->assert_true( null !== $tags->get_tag_info( 'forma-project-number' ), 'tag registered' );

		$id              = ( new Content( static function ( string $message ): void {} ) )->project_id( 'casa-nera' );
		$GLOBALS['post'] = get_post( $id );
		setup_postdata( $GLOBALS['post'] );

		$tag = $tags->create_tag( null, 'forma-project-number', array( 'prefix' => 'No. ' ) );
		$this->assert_same( 'No. 08', $tag->get_content() );

		wp_reset_postdata();
	}
}
