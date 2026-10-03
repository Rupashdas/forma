<?php
/**
 * AJAX handlers for project filtering.
 */

namespace Forma\Studio\Engine;

defined( 'ABSPATH' ) || exit;

final class AJAX {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_forma_filter_projects', array( $this, 'filter_projects' ) );
		add_action( 'wp_ajax_nopriv_forma_filter_projects', array( $this, 'filter_projects' ) );
	}

	/**
	 * @return void
	 */
	public function filter_projects() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'forma_filter_projects' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'forma-studio-engine' ) ) );
		}

		$filters = isset( $_POST['filters'] ) ? (array) wp_unslash( $_POST['filters'] ) : array();

		$query_args = array(
			'post_type'   => 'forma_project',
			'post_status' => 'publish',
			'posts_per_page' => isset( $filters['per_page'] ) ? max( 1, absint( $filters['per_page'] ) ) : 12,
			'paged'       => isset( $filters['page'] ) ? max( 1, absint( $filters['page'] ) ) : 1,
		);

		$tax_query = array();
		if ( ! empty( $filters['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'project_category',
				'terms'    => array_map( 'absint', (array) $filters['category'] ),
				'field'    => 'id',
				'operator' => 'IN',
			);
		}
		if ( ! empty( $filters['location'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'project_location',
				'terms'    => array_map( 'absint', (array) $filters['location'] ),
				'field'    => 'id',
				'operator' => 'IN',
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		if ( ! empty( $filters['search'] ) ) {
			$query_args['s'] = sanitize_text_field( wp_unslash( $filters['search'] ) );
		}

		$loop = new \WP_Query( $query_args );

		$projects = array();
		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$projects[] = forma_get_project_card( get_the_ID() );
			}
		}
		wp_reset_postdata();

		wp_send_json_success(
		 array(
			'projects'     => $projects,
			'found_posts'  => $loop->found_posts,
			'max_pages'    => $loop->max_num_pages,
			'current_page' => $query_args['paged'],
		 )
		);
	}
}