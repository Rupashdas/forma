<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers for writing Elementor documents as the editor itself saves them, so generated layouts open, edit and
 * re-save normally in Elementor. Element ids are derived from a seed, so rebuilding a document keeps its ids stable.
 *
 * A saved document is fingerprinted. If someone later edits it in Elementor the fingerprint no longer matches, and
 * rebuilding it is skipped unless forced, so a rerun never overwrites hand edits.
 */
final class Builder {

	/** Post meta that holds the fingerprint of a seeded document. */
	public const HASH_KEY = '_forma_design_hash';

	/** What the seeders log when the edit guard stops a save. */
	public const SKIPPED = 'skipped: edited in Elementor (use --force)';

	private static int $counter = 0;

	private static string $seed = '';

	private static bool $force = false;

	public static function reset( string $seed ): void {
		self::$seed    = $seed;
		self::$counter = 0;
	}

	/** Let the next saves overwrite documents that were edited in Elementor. */
	public static function force( bool $force ): void {
		self::$force = $force;
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

	/**
	 * A Slider control value. With the `custom` unit the size is a CSS expression such as `clamp(72px, 18vw, 320px)`.
	 */
	public static function size( int|float|string $size, string $unit = 'px' ): array {
		return array(
			'unit'  => $unit,
			'size'  => $size,
			'sizes' => array(),
		);
	}

	/**
	 * A Dimensions control value. Use the `custom` unit for values such as `var(--forma-gutter)`.
	 */
	public static function box( int|string $top, int|string|null $right = null, int|string|null $bottom = null, int|string|null $left = null, string $unit = 'px' ): array {
		$right  ??= $top;
		$bottom ??= $top;
		$left   ??= $right;

		return array(
			'unit'     => $unit,
			'top'      => (string) $top,
			'right'    => (string) $right,
			'bottom'   => (string) $bottom,
			'left'     => (string) $left,
			'isLinked' => (string) $top === (string) $right && (string) $right === (string) $bottom && (string) $bottom === (string) $left,
		);
	}

	/**
	 * A Gaps control value, as a container's `flex_gap` stores it.
	 */
	public static function gap( int|string $row, int|string|null $column = null, string $unit = 'px' ): array {
		$column ??= $row;

		return array(
			'row'      => (string) $row,
			'column'   => (string) $column,
			'unit'     => $unit,
			'isLinked' => (string) $row === (string) $column,
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
	 *
	 * @return bool False when the document was edited in Elementor since it was seeded and the save was skipped.
	 */
	public static function save( int $post_id, array $elements, array $settings = array() ): bool {
		if ( ! self::$force && self::edited( $post_id ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			self::as_admin();
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

		update_post_meta( $post_id, self::HASH_KEY, self::hash( $post_id ) );

		return true;
	}

	/** Act as the first administrator, so Elementor's capability checks pass under WP-CLI. */
	public static function as_admin(): void {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);

		wp_set_current_user( (int) ( $admins[0] ?? 0 ) );
	}

	/**
	 * Whether the document's stored data differs from what was last seeded. A document that was never seeded
	 * has no fingerprint and counts as unedited.
	 */
	public static function edited( int $post_id ): bool {
		$hash = (string) get_post_meta( $post_id, self::HASH_KEY, true );

		return '' !== $hash && self::hash( $post_id ) !== $hash;
	}

	private static function hash( int $post_id ): string {
		return md5(
			(string) get_post_meta( $post_id, '_elementor_data', true ) .
			maybe_serialize( get_post_meta( $post_id, '_elementor_page_settings', true ) )
		);
	}

	/**
	 * Elementor silently ignores setting keys that are not controls of the element, so a typo means the design
	 * never applies. This walks an element tree and lists every such key as `{type}.{key}` (repeater items as
	 * `{type}.{repeater}[].{key}`), plus the bare type for a widget that is not registered.
	 *
	 * @param array $elements Element tree as passed to {@see self::save()}.
	 * @return string[]
	 */
	public static function unknown_settings( array $elements ): array {
		$unknown = array();

		foreach ( $elements as $element ) {
			$type     = 'widget' === ( $element['elType'] ?? '' ) ? (string) ( $element['widgetType'] ?? '' ) : (string) ( $element['elType'] ?? '' );
			$controls = self::controls_of( $element );

			if ( null === $controls ) {
				$unknown[] = $type;
			} else {
				foreach ( self::scan( (array) ( $element['settings'] ?? array() ), $controls ) as $path ) {
					$unknown[] = "{$type}.{$path}";
				}
			}

			array_push( $unknown, ...self::unknown_settings( (array) ( $element['elements'] ?? array() ) ) );
		}

		return array_values( array_unique( $unknown ) );
	}

	/**
	 * The same check for a flat settings map, such as document or kit settings.
	 *
	 * @param array $settings Settings to check.
	 * @param array $controls The document's registered controls (`$document->get_controls()`).
	 * @return string[]
	 */
	public static function unknown_keys( array $settings, array $controls ): array {
		return array_values( array_unique( self::scan( $settings, $controls ) ) );
	}

	/** The registered controls of an element's type, or null when the type is not registered. */
	private static function controls_of( array $element ): ?array {
		$plugin = \Elementor\Plugin::$instance;

		if ( 'widget' === ( $element['elType'] ?? '' ) ) {
			$type = $plugin->widgets_manager->get_widget_types( (string) ( $element['widgetType'] ?? '' ) );
		} else {
			$type = $plugin->elements_manager->get_element_types( (string) ( $element['elType'] ?? '' ) );
		}

		return $type ? $type->get_controls() : null;
	}

	/**
	 * A responsive control is stored once and its per-device values are keys with a device suffix
	 * (`padding_mobile`), so `{control}_{device}` is valid when `{control}` is responsive.
	 */
	private static function is_device_key( string $key, array $controls ): bool {
		$devices = array_keys( \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints() );

		foreach ( $devices as $device ) {
			$suffix = "_{$device}";

			if ( str_ends_with( $key, $suffix ) ) {
				$base = substr( $key, 0, -strlen( $suffix ) );

				if ( ! empty( $controls[ $base ]['is_responsive'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return string[] Unknown key paths, relative to the settings map.
	 */
	private static function scan( array $settings, array $controls ): array {
		$unknown = array();

		foreach ( $settings as $key => $value ) {
			if ( '__globals__' === $key || '__dynamic__' === $key ) {
				continue;
			}

			if ( ! isset( $controls[ $key ] ) ) {
				if ( ! self::is_device_key( (string) $key, $controls ) ) {
					$unknown[] = (string) $key;
				}

				continue;
			}

			$fields = $controls[ $key ]['fields'] ?? null;

			if ( ! is_array( $fields ) || ! is_array( $value ) ) {
				continue;
			}

			// Repeater fields are keyed by name in some controls and listed with a `name` in others.
			$by_name = array( '_id' => array() );

			foreach ( $fields as $field_key => $field ) {
				$by_name[ $field['name'] ?? $field_key ] = $field;
			}

			foreach ( $value as $item ) {
				foreach ( self::scan( (array) $item, $by_name ) as $path ) {
					$unknown[] = "{$key}[].{$path}";
				}
			}
		}

		return $unknown;
	}
}
