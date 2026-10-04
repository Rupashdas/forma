<?php

namespace Forma\Engine\Elementor;

use Forma\Engine\Contracts\Module as ModuleContract;

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

		( new ArchiveFilter() )->register();
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
	}
}
