<?php

namespace Forma\Engine\Elementor;

use Forma\Engine\Contracts\Module as ModuleContract;
use Forma\Engine\Support\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * The "Forma" panel in Elementor: the studio's own widgets and dynamic tags, registered next to the native ones.
 * Only what Elementor Pro doesn't already do lives here.
 */
final class Module implements ModuleContract {

	public const CATEGORY = 'forma';

	/**
	 * Widget classes, in panel order.
	 *
	 * @var array<class-string<\Elementor\Widget_Base>>
	 */
	public const WIDGETS = array(
		Widgets\Marquee::class,
		Widgets\BeforeAfter::class,
		Widgets\ProjectIndex::class,
		Widgets\NextProject::class,
		Widgets\ScrollStory::class,
		Widgets\StudyModel::class,
	);

	public static function id(): string {
		return 'elementor';
	}

	public function is_available(): bool {
		return did_action( 'elementor/loaded' ) > 0;
	}

	public function register(): void {
		add_action( 'elementor/elements/categories_registered', array( $this, 'category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'tags' ) );
		add_action( 'elementor/frontend/widget/before_render', array( $this, 'accordion_link' ) );
		add_filter( 'elementor/skin/loop_header_attributes', array( $this, 'loop_attributes' ) );

		( new ArchiveFilter() )->register();
	}

	/**
	 * The Loop Grid marks its container as a list, but every card here is a link (a clickable container), not a list
	 * item, so assistive technology rightly reports the list as invalid. Without the role the cards are plain links.
	 *
	 * @param array<string, mixed> $attributes The container's attributes (class and role).
	 * @return array<string, mixed>
	 */
	public function loop_attributes( array $attributes ): array {
		unset( $attributes['role'] );

		return $attributes;
	}

	/**
	 * A Nested Accordion whose items have ids can be linked to (`/services/#architecture`): the item the address points
	 * at is opened by a small script, loaded with the accordion.
	 */
	public function accordion_link( \Elementor\Element_Base $widget ): void {
		if ( 'nested-accordion' === $widget->get_name() ) {
			Assets::enqueue( 'accordion-link' );
		}
	}

	public function category( \Elementor\Elements_Manager $elements ): void {
		$elements->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Forma', 'forma-studio-engine' ),
				'icon'  => 'eicon-apps',
			)
		);
	}

	public function widgets( \Elementor\Widgets_Manager $widgets ): void {
		foreach ( self::WIDGETS as $class ) {
			$widgets->register( new $class() );
		}
	}

	public function tags( \Elementor\Core\DynamicTags\Manager $tags ): void {
		$tags->register_group( self::CATEGORY, array( 'title' => __( 'Forma', 'forma-studio-engine' ) ) );
		$tags->register( new Tags\ProjectNumber() );
		$tags->register( new Tags\ProjectCount() );
	}
}
