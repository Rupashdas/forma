<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Elementor\Builder;

defined( 'ABSPATH' ) || exit;

final class BuilderTest extends TestCase {

	public function test_ids_are_stable_for_the_same_seed(): void {
		Builder::reset( 'home' );
		$first = array( Builder::id(), Builder::id() );

		Builder::reset( 'home' );
		$this->assert_same( $first, array( Builder::id(), Builder::id() ) );
		$this->assert_same( 1, preg_match( '/^[a-f0-9]{7}$/', $first[0] ), 'Elementor-style 7-char id' );
		$this->assert_true( $first[0] !== $first[1], 'ids differ within a document' );
	}

	public function test_elements_have_the_shape_the_editor_saves(): void {
		Builder::reset( 'shape' );
		$heading   = Builder::widget( 'heading', array( 'title' => 'FORMA' ) );
		$container = Builder::container( array( 'flex_direction' => 'column' ), array( $heading ) );

		$this->assert_same( 'container', $container['elType'] );
		$this->assert_same( 'boxed', $container['settings']['content_width'] );
		$this->assert_same( false, $container['isInner'] );
		$this->assert_same( 'widget', $container['elements'][0]['elType'] );
		$this->assert_same( 'heading', $container['elements'][0]['widgetType'] );
		$this->assert_same( array( 'unit' => 'vh', 'size' => 80, 'sizes' => array() ), Builder::size( 80, 'vh' ) );
		$this->assert_same( '24', Builder::box( 24, 48 )['top'] );
		$this->assert_same( '48', Builder::box( 24, 48 )['left'] );
	}

	public function test_save_writes_an_elementor_document(): void {
		$page = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Builder test',
				'post_status' => 'private',
			)
		);

		Builder::reset( 'save' );
		Builder::save( $page, array( Builder::container( array(), array( Builder::widget( 'heading', array( 'title' => 'Saved' ) ) ) ) ) );

		$this->assert_same( 'builder', get_post_meta( $page, '_elementor_edit_mode', true ) );
		$this->assert_true( str_contains( (string) get_post_meta( $page, '_elementor_data', true ), '"title":"Saved"' ), 'element data stored' );
	}
}
