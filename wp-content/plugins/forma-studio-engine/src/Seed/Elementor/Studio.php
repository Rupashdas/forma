<?php

namespace Forma\Engine\Seed\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * The Studio page (spec 5.2): the studio as a building you can open.
 *
 * 1. Hero: an inset Panel with the H1, the intro and a photograph of a study model.
 * 2. The studio model: a sticky Panel stage inside a 250vh section. The studio building turns and comes apart floor by
 *    floor as you scroll, each floor labelled with its team, while three glass cards introduce the teams.
 * 3. Principles: three things the studio holds to, in a three-column grid.
 * 4. Numbers: four Counters in Panel tiles.
 * 5. Team: the eight people who lead the studio as a typographic table (no faces), then two photographs of the
 *    workspace.
 * 6. Approach: how the studio works, beside a photograph of model-making.
 * 7. Recognition: the saved ledger.
 * 8. Press: the publications that wrote about the studio.
 * 9. The saved Closing CTA.
 *
 * Only the H1 carries an entrance; everything else sits still.
 */
final class Studio extends Page {

	public const KEY = 'studio';

	/** The tour's height in vh. */
	private const TOUR = 250;

	protected function key(): string {
		return self::KEY;
	}

	protected function summary(): string {
		return '9 sections, 1 model';
	}

	protected function components(): array {
		return array( 'recognition', 'closing-cta' );
	}

	protected function sections(): array {
		return array(
			$this->hero(),
			$this->building(),
			$this->principles(),
			$this->numbers(),
			$this->team(),
			$this->approach(),
			$this->component( 'recognition' ),
			$this->press(),
			$this->component( 'closing-cta' ),
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 1. Hero.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * An inset Panel. From 1024px the copy is the left 46% of the boxed column and a photograph of a study model the right
	 * 50%, level with each other; below that they stack, the copy first. The header floats over the panel, so the top
	 * padding clears it.
	 */
	private function hero(): array {
		$page = $this->site['studio_page'];

		return $this->panel(
			array(
				Style::row(
					array(
						Style::cell(
							array(
								Style::heading( $page['statement'], 'display-l', 'h1', 'ink', array( 'fm_entrance' => 'lines' ) ),
								$this->paragraph( $page['intro'], 'muted', 44 ),
							),
							46,
							100,
							100,
							array( 'flex_gap' => Builder::gap( 'clamp(18px, 2.2vw, 28px)', null, 'custom' ) )
						),
						Style::cell(
							array(
								$this->photo( 'studio-01.jpg', array( '5 / 4', '3 / 2', '4 / 3' ), '50% 50%', 'var(--forma-r-tile)' ),
							),
							50,
							100,
							100
						),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'center',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(24px, 4vw, 64px)', null, 'custom' ),
					)
				),
			),
			'clamp(136px, 15vw, 220px)',
			'clamp(12px, 1.6vw, 24px)'
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 2. The studio model.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * The studio as a three-floor warehouse. The model turns three quarters of a circle and comes apart over the section;
	 * each floor's callout is its team's name. The cards at the left say what each team does, top floor first.
	 */
	private function building(): array {
		$notes = array();

		foreach ( $this->site['studio_page']['teams'] as $team ) {
			$notes[] = array(
				Style::heading( $team['title'], 'subheading', 'h2' ),
				Style::text( '<p>' . esc_html( $team['text'] ) . '</p>', 'body', 'muted' ),
			);
		}

		return $this->tour(
			array(
				'source'         => 'recipe',
				'recipe'         => 'studio',
				'scroll_orbit'   => 'yes',
				'scroll_explode' => 'yes',
			),
			$notes,
			self::TOUR
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 3. Principles.
	// ---------------------------------------------------------------------------------------------------------------

	/** Three columns, each a Subheading and a paragraph under a hairline. They are not numbered: they are not a sequence. */
	private function principles(): array {
		$page    = $this->site['studio_page'];
		$columns = array();

		foreach ( $page['principles'] as $principle ) {
			$columns[] = Style::stack(
				array(
					Style::display( $principle['title'], 'clamp(24px, 2.4vw, 34px)', 'h3' ),
					$this->paragraph( $principle['text'], 'muted', 44 ),
				),
				array(
					'flex_gap'      => Builder::gap( 14 ),
					'padding'       => Builder::box( 26, 0, 0, 0 ),
					'border_border' => 'solid',
					'border_width'  => Builder::box( 1, 0, 0, 0 ),
					'__globals__'   => array( 'border_color' => Style::color( 'line' ) ),
				)
			);
		}

		return $this->band(
			array(
				$this->heading( $page['principles_title'] ),
				$this->grid( $columns, array( 3, 1, 1 ), array( 'grid_gaps' => Builder::gap( 'clamp(28px, 3vw, 40px)', 'clamp(24px, 3vw, 48px)', 'custom' ) ) ),
			),
			'clamp(56px, 8vw, 128px)',
			'clamp(24px, 3vw, 48px)',
			array( 'flex_gap' => Builder::gap( 'clamp(32px, 4vw, 56px)', null, 'custom' ) )
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 4. Numbers.
	// ---------------------------------------------------------------------------------------------------------------

	/**
	 * Four Counters, each in a Panel tile: four across from 1024px, two by two below. The figure is Display L, its
	 * caption a Label in Graphite.
	 */
	private function numbers(): array {
		$tiles = array();

		foreach ( $this->site['studio_page']['numbers'] as [ $value, $title ] ) {
			$tiles[] = $this->tile(
				array(
					Builder::widget(
						'counter',
						array(
							'starting_number'            => 0,
							'ending_number'              => $value,
							'duration'                   => 1800,
							'thousand_separator'         => '',
							'title'                      => $title,
							'title_tag'                  => 'p',
							'title_position'             => 'after',
							'number_position'            => 'start',
							'title_horizontal_alignment' => 'start',
							'title_gap'                  => Builder::size( 8 ),
							'__globals__'                => array(
								'typography_number_typography' => Style::font( 'display-l' ),
								'number_color'                 => Style::color( 'ink' ),
								'typography_title_typography'  => Style::font( 'label' ),
								'title_color'                  => Style::color( 'muted' ),
							),
						)
					),
				),
				array(
					'padding' => Builder::box( 'clamp(22px, 2.4vw, 32px)', 'clamp(22px, 2.4vw, 32px)', 'clamp(22px, 2.4vw, 32px)', 'clamp(22px, 2.4vw, 32px)', 'custom' ),
				)
			);
		}

		return $this->band(
			array( $this->grid( $tiles, array( 4, 2, 2 ) ) ),
			'clamp(24px, 3vw, 48px)',
			self::MID
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 5. Team and workspace.
	// ---------------------------------------------------------------------------------------------------------------

	/** The people who lead the studio as a table of name, role and year joined, then two photographs of the workspace. */
	private function team(): array {
		$page = $this->site['studio_page'];
		$rows = array(
			$this->ruled_row(
				array(
					Style::cell( array( Style::label( 'Name' ) ), 38, 38, 100 ),
					Style::cell( array( Style::label( 'Role' ) ), 46, 46, 100 ),
					Style::cell( array( Style::label( 'Joined', 'muted', array( 'align' => 'right' ) ) ), 10, 10, 'auto' ),
				),
				array(
					'padding'     => Builder::box( 12, 0, 12, 0 ),
					'hide_mobile' => 'hidden-mobile',
				)
			),
		);

		foreach ( $page['team'] as [ $name, $role, $joined ] ) {
			$rows[] = $this->ruled_row(
				array(
					Style::cell( array( Style::display( $name, 'clamp(24px, 2.6vw, 38px)', 'h3' ) ), 38, 38, 100 ),
					Style::cell( array( Style::text( '<p>' . esc_html( $role ) . '</p>', 'body', 'muted' ) ), 46, 46, 75 ),
					Style::cell( array( Style::label( $joined, 'muted', array( 'align' => 'right' ) ) ), 10, 10, 'auto' ),
				)
			);
		}

		return $this->band(
			array(
				$this->section_head( $page['team_title'], $page['team_intro'] ),
				$this->ruled( $rows ),
				$this->workspace(),
			),
			self::MID,
			self::MID
		);
	}

	/** Two photographs of the workspace, rounded, side by side from the mobile width, the narrower one set lower. */
	private function workspace(): array {
		return Style::row(
			array(
				Style::cell( array( $this->photo( 'studio-02.jpg', array( '3 / 2', '3 / 2', '4 / 3' ) ) ), 56, 56, 100 ),
				Style::cell( array( $this->photo( 'studio-03.jpg', array( '4 / 5', '4 / 5', '4 / 3' ), '50% 50%' ) ), 38, 38, 100 ),
			),
			array(
				'flex_wrap'            => 'nowrap',
				'flex_wrap_mobile'     => 'wrap',
				'flex_justify_content' => 'space-between',
				'flex_align_items'     => 'flex-end',
				'flex_gap'             => Builder::gap( 'clamp(16px, 2vw, 32px)', 'clamp(16px, 2vw, 32px)', 'custom' ),
			)
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 6. Approach.
	// ---------------------------------------------------------------------------------------------------------------

	/** How the studio works, in three short paragraphs, beside a photograph of model-making. */
	private function approach(): array {
		$approach = $this->site['studio_page']['approach'];
		$text     = array( $this->heading( $approach['title'] ) );

		foreach ( $approach['text'] as $paragraph ) {
			$text[] = $this->paragraph( $paragraph, 'ink', 52 );
		}

		return $this->band(
			array(
				Style::row(
					array(
						Style::cell( $text, 44, 100, 100, array( 'flex_gap' => Builder::gap( 18 ) ) ),
						Style::cell( array( $this->photo( 'studio-05.jpg', array( '4 / 5', '3 / 2', '4 / 3' ), '50% 40%' ) ), 50, 100, 100 ),
					),
					array(
						'flex_wrap'               => 'nowrap',
						'flex_justify_content'    => 'space-between',
						'flex_align_items'        => 'center',
						'flex_direction_tablet'   => 'column',
						'flex_align_items_tablet' => 'stretch',
						'flex_gap'                => Builder::gap( 'clamp(28px, 4vw, 64px)', null, 'custom' ),
					)
				),
			),
			'clamp(8px, 1vw, 16px)',
			self::MID
		);
	}

	// ---------------------------------------------------------------------------------------------------------------
	// 8. Press.
	// ---------------------------------------------------------------------------------------------------------------

	/** The fictional publications that wrote about the studio: publication, piece and year. */
	private function press(): array {
		$page = $this->site['studio_page'];
		$rows = array();

		foreach ( $page['press'] as [ $publication, $piece, $year ] ) {
			$rows[] = $this->ruled_row(
				array(
					Style::cell( array( Style::display( $publication, 'clamp(22px, 2.2vw, 32px)', 'h3' ) ), 38, 38, 100 ),
					Style::cell( array( Style::text( '<p>' . esc_html( $piece ) . '</p>', 'body', 'muted' ) ), 46, 46, 75 ),
					Style::cell( array( Style::label( $year, 'muted', array( 'align' => 'right' ) ) ), 10, 10, 'auto' ),
				)
			);
		}

		return $this->band(
			array(
				$this->heading( $page['press_title'] ),
				$this->ruled( $rows ),
			),
			self::MID,
			'clamp(40px, 5vw, 72px)'
		);
	}
}
