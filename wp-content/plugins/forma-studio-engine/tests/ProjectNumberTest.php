<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Projects\ProjectNumber;

defined( 'ABSPATH' ) || exit;

final class ProjectNumberTest extends TestCase {

	protected function set_up(): void {
		ProjectNumber::flush();
	}

	public function test_numbers_follow_completion_date_oldest_first(): void {
		$late  = $this->project( '1990-06-01' );
		$early = $this->project( '1990-01-01' );
		$mid   = $this->project( '1990-03-01' );

		$this->assert_same( '01', ProjectNumber::for_post( $early ) );
		$this->assert_same( '02', ProjectNumber::for_post( $mid ) );
		$this->assert_same( '03', ProjectNumber::for_post( $late ) );
	}

	public function test_drafts_and_other_post_types_have_no_number(): void {
		$draft = $this->project( '1990-01-01', 'draft' );
		$page  = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Not a project',
				'post_status' => 'publish',
			)
		);

		$this->assert_same( '', ProjectNumber::for_post( $draft ) );
		$this->assert_same( '', ProjectNumber::for_post( $page ) );
	}

	public function test_numbers_update_when_an_older_project_is_added(): void {
		$second = $this->project( '1990-06-01' );
		$this->assert_same( '01', ProjectNumber::for_post( $second ) );

		$this->project( '1990-01-01' );
		$this->assert_same( '02', ProjectNumber::for_post( $second ) );
	}

	private function project( string $date, string $status = 'publish' ): int {
		return (int) wp_insert_post(
			array(
				'post_type'   => 'forma_project',
				'post_title'  => 'Test project ' . $date,
				'post_status' => $status,
				'post_date'   => $date . ' 10:00:00',
			)
		);
	}
}
