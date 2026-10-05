<?php

namespace Forma\Engine\Seed\Elementor;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * The 404 page (spec 5.2), a Theme Builder `error-404` template shown for every address that is not found:
 *
 * 1. An inset Panel with the H1 "This room hasn't been built yet.", a short line, an Ink pill to the homepage and, beside
 *    them, an unfinished study model drawn only in dashed blue outlines, which can be turned.
 * 2. The three newest projects as project cards in a Loop Grid.
 *
 * It is not a page, so it is saved as a library document under the seed key `not-found`, with the Theme Builder
 * condition "Singular, 404 Page"; the header and footer come from their own site-wide templates.
 */
final class NotFound extends Page {

	public const KEY = 'not-found';

	/** The Theme Builder condition: include, singular, 404 Page. */
	public const CONDITION = array( 'include', 'singular', 'not_found404' );

	protected function key(): string {
		return self::KEY;
	}

	protected function summary(): string {
		return '2 sections, 1 model';
	}

	protected function components(): array {
		return array( 'project-card' );
	}

	/**
	 * @return int The 404 template's post id.
	 * @throws \RuntimeException When the project card component has not been seeded yet.
	 */
	public function build(): int {
		foreach ( $this->components() as $component ) {
			if ( ! Templates::id( $component ) ) {
				throw new \RuntimeException( esc_html( "The {$component} component does not exist: run `wp forma design --only=components` first." ) );
			}
		}

		$id = Templates::upsert( self::KEY, 'error-404', '404 page', $this->elements(), $this->settings(), array( self::CONDITION ) );

		( $this->log )( '404: ' . ( Templates::saved() ? "{$this->summary()} (#{$id})." : Builder::SKIPPED ) );

		return $id;
	}

	/** A Theme Builder document has no page template or title to set. */
	public function settings(): array {
		return array();
	}

	protected function sections(): array {
		return array(
			$this->hero(),
			$this->projects(),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Hero.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Panel at least 92vh tall. From 1024px the copy is the left 42% of the boxed column and the model the right
	 * 56%, centred on each other; below that they stack, the copy first.
	 */
	private function hero(): array {
		$page = $this->site['not_found'];

		return $this->panel(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( $page['title'], 'display-l', 'h1', 'ink', array( 'fm_entrance' => 'lines' ) ),
								$this->paragraph( $page['text'], 'muted', 38 ),
								Style::button( $page['home'], home_url( '/' ) ),
							),
							42,
							100,
							100,
							array(
								'flex_gap'         => Builder::gap( 'clamp(18px, 2.2vw, 28px)', null, 'custom' ),
								'flex_align_items' => 'flex-start',
							)
						),
						Style::cell( array( $this->model() ), 56, 100, 100 ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'center',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(16px, 3vw, 48px)', null, 'custom' ),
					)
				),
			),
			'clamp(112px, 12vw, 168px)',
			'clamp(24px, 3vw, 48px)',
			array(
				'min_height'        => Builder::size( 92, 'vh' ),
				'min_height_tablet' => Builder::size( 0 ),
				'flex_justify_content' => 'center',
			)
		);
	}

	/**
	 * The model: a house that is only drawn. Every volume is a dashed outline in blue, so nothing in it is solid. The
	 * widget holds the volumes itself (source "custom"), so they can be edited in Elementor.
	 */
	private function model(): array {
		$volumes = array();

		foreach ( $this->volumes() as $volume ) {
			$volumes[] = array( '_id' => Builder::id() ) + $volume;
		}

		return Builder::widget(
			'forma-study-model',
			array(
				'source'             => 'custom',
				'volumes'            => $volumes,
				'camera'             => 'three-quarter',
				'view_height'        => Builder::size( 66, 'vh' ),
				'view_height_tablet' => Builder::size( 52, 'vh' ),
				'view_height_mobile' => Builder::size( 44, 'vh' ),
				'assemble'           => 'yes',
				'drag'               => 'yes',
				'callouts'           => 'yes',
			)
		);
	}

	/**
	 * A plinth and two floors under a roof plate, a stair, four posts, and one more room beside the house that is only an
	 * outline of an outline. 14 volumes, all of kind `wire` and material `wire`.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function volumes(): array {
		$wire = static fn( float $w, float $h, float $d, float $x, float $y, float $z, string $part = '', float $rot = 0 ): array => array(
			'kind'     => 'wire',
			'w'        => $w,
			'h'        => $h,
			'd'        => $d,
			'x'        => $x,
			'y'        => $y,
			'z'        => $z,
			'rot'      => $rot,
			'material' => 'wire',
			'part'     => $part,
			'stage'    => 0,
		);

		return array(
			$wire( 6.4, 0.18, 4.2, 0, 0, 0 ),
			$wire( 3.4, 1.0, 2.4, -1.1, 0.18, -0.2, 'Ground floor' ),
			$wire( 3.4, 0.9, 2.4, -1.1, 1.18, -0.2, 'First floor' ),
			$wire( 3.8, 0.08, 2.8, -1.1, 2.08, -0.2, 'Roof, to come' ),
			$wire( 1.5, 1.0, 1.7, 1.95, 0.18, 0.5, 'This room' ),
			$wire( 0.1, 1.0, 0.1, 0.9, 0.18, 0.95 ),
			$wire( 0.1, 1.0, 0.1, 2.7, 0.18, 0.95 ),
			$wire( 0.1, 1.0, 0.1, 2.7, 0.18, -0.35 ),
			$wire( 1.5, 0.06, 0.1, 1.95, 1.18, 0.95 ),
			$wire( 0.7, 0.18, 0.3, -2.2, 0.18, 1.45, 'Stair' ),
			$wire( 0.7, 0.18, 0.3, -1.5, 0.36, 1.45 ),
			$wire( 0.7, 0.18, 0.3, -0.8, 0.54, 1.45 ),
			$wire( 0.7, 0.18, 0.3, -0.1, 0.72, 1.45 ),
			$wire( 0.8, 0.5, 0.8, -2.5, 0.18, -1.55, 'Foundations' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. Recent projects.
	// ---------------------------------------------------------------------------------------------------------------

	/** Three project cards: the three newest projects, one column on mobile. */
	private function projects(): array {
		return $this->band(
			array(
				$this->heading( $this->site['not_found']['projects_title'] ),
				Builder::widget(
					'loop-grid',
					array(
						'template_id'          => (string) Templates::id( 'project-card' ),
						'columns'              => '3',
						'columns_tablet'       => '3',
						'columns_mobile'       => '1',
						'posts_per_page'       => 3,
						'post_query_post_type' => Projects::POST_TYPE,
						'post_query_orderby'   => 'date',
						'post_query_order'     => 'desc',
						'column_gap'           => Builder::size( 16 ),
						'row_gap'              => Builder::size( 16 ),
					)
				),
			),
			'clamp(24px, 3vw, 48px)',
			self::MID,
			array( 'flex_gap' => Builder::gap( 'clamp(24px, 3vw, 40px)', null, 'custom' ) )
		);
	}
}
