<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers for writing Elementor documents as the editor itself saves them, so generated layouts open, edit and
 * re-save normally in Elementor. Element ids are derived from a seed, so rebuilding a document keeps its ids stable.
 */
final class Builder {

	private static int $counter = 0;

	private static string $seed = '';

	public static function reset( string $seed ): void {
		self::$seed    = $seed;
		self::$counter = 0;
	}

	/** A stable 7-character element id, the format Elementor generates. */
	public static function id(): string {
		return substr( md5( self::$seed . ':' . self::$counter++ ), 0, 7 );
	}

	public static function container( array $settings, array $children = array(), bool $inner = false ): array {
		return array(
			'id'       => self::id(),
			'elType'   => 'container',
			'isInner'  => $inner,
			'settings' => $settings + array( 'content_width' => $inner ? 'full' : 'boxed' ),
			'elements' => $children,
		);
	}

	public static function widget( string $type, array $settings = array() ): array {
		return array(
			'id'         => self::id(),
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	public static function size( int|float $size, string $unit = 'px' ): array {
		return array(
			'unit'  => $unit,
			'size'  => $size,
			'sizes' => array(),
		);
	}

	public static function box( int $top, ?int $right = null, ?int $bottom = null, ?int $left = null, string $unit = 'px' ): array {
		$right  ??= $top;
		$bottom ??= $top;
		$left   ??= $right;

		return array(
			'unit'     => $unit,
			'top'      => (string) $top,
			'right'    => (string) $right,
			'bottom'   => (string) $bottom,
			'left'     => (string) $left,
			'isLinked' => $top === $right && $right === $bottom && $bottom === $left,
		);
	}

	/** An Image control value for a media library attachment. */
	public static function image( int $attachment_id ): array {
		return array(
			'id'     => $attachment_id,
			'url'    => (string) wp_get_attachment_image_url( $attachment_id, 'full' ),
			'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'source' => 'library',
		);
	}

	/** A dynamic tag reference for an element's `__dynamic__` settings map. */
	public static function tag( string $name, array $settings = array() ): string {
		return sprintf(
			'[elementor-tag id="%s" name="%s" settings="%s"]',
			self::id(),
			$name,
			rawurlencode( (string) wp_json_encode( (object) $settings ) )
		);
	}

	/**
	 * Save elements (and optional page settings) to a post through Elementor's Document API.
	 * Acts as the first administrator when nobody is logged in (WP-CLI).
	 */
	public static function save( int $post_id, array $elements, array $settings = array() ): void {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			$admins = get_users(
				array(
					'role'   => 'administrator',
					'number' => 1,
					'fields' => 'ID',
				)
			);
			wp_set_current_user( (int) ( $admins[0] ?? 0 ) );
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_id, false );

		if ( ! $document ) {
			throw new \RuntimeException( esc_html( "Post {$post_id} is not an Elementor document." ) );
		}

		$document->set_is_built_with_elementor( true );

		$data = array( 'elements' => $elements );

		if ( $settings ) {
			$data['settings'] = $settings;
		}

		if ( false === $document->save( $data ) ) {
			throw new \RuntimeException( esc_html( "Elementor refused to save post {$post_id}." ) );
		}
	}
}
