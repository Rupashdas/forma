<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The ordered steps of `wp forma design`: the Kit first (everything else references its globals), then the saved
 * components the pages use, the Theme Builder documents, Home, the projects (archive, single template and bodies) and
 * the inner pages with the 404 template. Each step saves through
 * {@see Builder::save()}, so it skips a document that was edited in Elementor unless the run is forced.
 */
final class Design {

	/** Step names in build order. */
	public const STEPS = array( 'kit', 'components', 'menu', 'header', 'footer', 'home', 'projects', 'pages' );

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
			'projects'   => $this->projects(),
			'pages'      => $this->pages(),
			default      => throw new \InvalidArgumentException( esc_html( "Unknown design step: {$step}." ) ),
		};
	}

	/**
	 * The projects step builds three things that belong together: the archive template, the single project template
	 * and each project's own body.
	 *
	 * @return array<string,int> The archive and single template ids, keyed `archive` and `single`.
	 */
	private function projects(): array {
		$ids = array(
			'archive' => ( new Archive( $this->log ) )->build(),
			'single'  => ( new Single( $this->log ) )->build(),
		);

		( new ProjectBodies( $this->log ) )->build();

		return $ids;
	}

	/**
	 * The pages step builds the inner pages (Studio, Services, Process, Contact, Colophon) and the 404 template. The
	 * saved components they show are built by the `components` step, which has to have run before.
	 *
	 * @return array<string,int> The post id of each, keyed by its slug (`not-found` for the 404 template).
	 */
	private function pages(): array {
		return array(
			'studio'    => ( new Studio( $this->log ) )->build(),
			'services'  => ( new Services( $this->log ) )->build(),
			'process'   => ( new Process( $this->log ) )->build(),
			'contact'   => ( new Contact( $this->log ) )->build(),
			'not-found' => ( new NotFound( $this->log ) )->build(),
			'colophon'  => ( new Colophon( $this->log ) )->build(),
		);
	}
}
