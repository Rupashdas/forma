<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The ordered steps of `wp forma design`: the Kit first (everything else references its globals), then the saved
 * components Home uses, the Theme Builder documents, and the pages built from them. Each step saves through
 * {@see Builder::save()}, so it skips a document that was edited in Elementor unless the run is forced.
 */
final class Design {

	/** Step names in build order. */
	public const STEPS = array( 'kit', 'components', 'menu', 'header', 'footer', 'home' );

	public function __construct( private \Closure $log ) {}

	/**
	 * @param string[] $only Step names to run; all steps when empty.
	 * @throws \InvalidArgumentException For a step that does not exist.
	 */
	public function run( array $only = array() ): void {
		$unknown = array_diff( $only, self::STEPS );

		if ( $unknown ) {
			throw new \InvalidArgumentException(
				esc_html( sprintf( 'Unknown design step: %s. Steps: %s.', implode( ', ', $unknown ), implode( ', ', self::STEPS ) ) )
			);
		}

		foreach ( self::STEPS as $step ) {
			if ( ! $only || in_array( $step, $only, true ) ) {
				$this->build( $step );
			}
		}

		// Documents and the Kit compile to cached CSS files; rebuild them on the next request.
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	/**
	 * Build one step and return the id of the post it saved (the ids by template key for the components step).
	 *
	 * @return int|array<string,int>
	 */
	private function build( string $step ): int|array {
		return match ( $step ) {
			'kit'        => ( new Kit( $this->log ) )->build(),
			'components' => ( new Components( $this->log ) )->build(),
			'menu'       => ( new MenuPopup( $this->log ) )->build(),
			'header'     => ( new Header( $this->log ) )->build(),
			'footer'     => ( new Footer( $this->log ) )->build(),
			'home'       => ( new Home( $this->log ) )->build(),
			default      => throw new \InvalidArgumentException( esc_html( "Unknown design step: {$step}." ) ),
		};
	}
}
