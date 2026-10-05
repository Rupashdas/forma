<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The Process page (spec 5.2): one model that grows through the six stages of a project as you scroll.
 *
 * 1. Hero: an inset Panel with the H1 and an intro.
 * 2. The stages: a section six screens tall whose sticky Panel stage holds the `process` model (plot and contours, rough
 *    massing, refined volumes, section cuts, structural frame, roof and finish, each appearing in turn), with a pill that
 *    reads the stage the model is at. Six glass cards scroll over it at the left, one per stage: the stage's number (the
 *    stages are a true sequence, so they are numbered), its title and duration, what happens, and what you receive.
 * 3. The saved Closing CTA.
 */
final class Process extends Page {

	public const KEY = 'process';

	/** The tour's height in vh: one screen per stage. */
	private const TOUR = 600;

	protected function key(): string {
		return self::KEY;
	}

	protected function summary(): string {
		return '3 sections, 6 stages, 1 model';
	}

	protected function components(): array {
		return array( 'closing-cta' );
	}

	protected function sections(): array {
		$page = $this->site['process'];

		return array(
			$this->masthead( $page['title'], $page['intro'] ),
			$this->stages(),
			$this->component( 'closing-cta' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// The stages.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The model's scroll follows the section, with its stages on: the volumes of stage n appear when the scroll passes
	 * (n - 1) / 6 of the section. The pill is laid over the model at the bottom right of the column (the top left below
	 * 1024px, where the cards cover the bottom); `process.js` rewrites it from the model's `forma-model:stage` event, and
	 * it is hidden from assistive technology because the cards carry the same words.
	 */
	private function stages(): array {
		$stages = $this->site['process']['stages'];
		$notes  = array();

		foreach ( $stages as $index => $stage ) {
			$notes[] = $this->note( $index + 1, $stage );
		}

		return $this->tour(
			array(
				'source' => 'recipe',
				'recipe' => 'process',
				'stages' => 'yes',
			),
			$notes,
			self::TOUR,
			array( $this->pill( count( $stages ), (string) $stages[0]['title'] ) )
		);
	}

	/**
	 * The text of one glass card: the stage's number as a chip and its duration in a Label, the title, what happens, and
	 * under a hairline what you receive.
	 *
	 * @param array{title:string,duration:string,text:string,receive:string} $stage
	 */
	private function note( int $number, array $stage ): array {
		return array(
			Style::row(
				array(
					Style::chip( 'Stage ' . $number ),
					Style::label( $stage['duration'] ),
				),
				array(
					'flex_wrap'        => 'nowrap',
					'flex_align_items' => 'center',
					'flex_gap'         => Builder::gap( 10 ),
				)
			),
			Style::display( $stage['title'], 'clamp(26px, 2.6vw, 36px)', 'h2' ),
			Style::text( '<p>' . esc_html( $stage['text'] ) . '</p>', 'body', 'muted' ),
			Style::stack(
				array(
					Style::label( 'You receive' ),
					Style::text( '<p>' . esc_html( ucfirst( $stage['receive'] ) ) . '</p>', 'body', 'ink' ),
				),
				array(
					'flex_gap'      => Builder::gap( 4 ),
					'padding'       => Builder::box( 12, 0, 0, 0 ),
					'border_border' => 'solid',
					'border_width'  => Builder::box( 1, 0, 0, 0 ),
					'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
				)
			),
		);
	}

	/** The pill that reads "Stage 3 of 6, Development" while the model is at that stage. */
	private function pill( int $count, string $first ): array {
		return Style::chip(
			"Stage 1 of {$count}, {$first}",
			array(
				'_css_classes' => 'forma-stage-pill',
				'_attributes'  => 'aria-hidden|true',
				'custom_css'   => <<<'CSS'
				selector {
					position: absolute;
					top: 88px;
					left: 0;
					z-index: 2;
				}
				@media (min-width: 1024px) {
					selector {
						top: auto;
						bottom: clamp(16px, 2vw, 28px);
						left: auto;
						right: 0;
					}
				}
				CSS,
			)
		);
	}
}
