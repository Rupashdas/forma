<?php

namespace Forma\Engine\Elementor;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * Makes Pro's Taxonomy Filter work on the Projects archive.
 *
 * The archive's Loop Grid shows the "current query", so term archives (/projects/type/interiors/) reuse the same
 * template. Pro's filter does not reach such a grid: it narrows a grid through the `elementor/query/query_args`
 * filter, which a current-query grid never runs, and its refresh request is answered by WordPress's home query, so
 * choosing a type would show blog posts. This class closes both gaps, using Pro's own filter state and query builder:
 *
 * - the filter is applied to the grid's current query, on page load (`?e-filter-…`) and on Pro's refresh request;
 * - the refresh request is given the project archive's query (published projects, newest first, one page);
 * - on a type archive the filter starts on that type, so "All" shows everything and the page and filter agree.
 *
 * Nothing here runs unless Elementor Pro's Loop Filter module is active.
 */
final class ArchiveFilter {

	public function register(): void {
		add_filter( 'elementor/query/get_query_args/current_query', array( $this, 'query' ) );
		add_action( 'elementor/frontend/widget/before_render', array( $this, 'preselect' ) );
	}

	/**
	 * Apply the active filters to a current-query grid.
	 *
	 * @param array<string,mixed> $vars Query variables of the main query (of the home page, on Pro's refresh request).
	 * @return array<string,mixed>
	 */
	public function query( $vars ) {
		$module = $this->module();

		if ( ! $module || ! is_array( $vars ) ) {
			return $vars;
		}

		if ( $this->is_refresh_request() && empty( $vars['post_type'] ) ) {
			$vars = array_merge(
				$vars,
				array(
					'post_type'           => Projects::POST_TYPE,
					'post_status'         => 'publish',
					'posts_per_page'      => max( 1, (int) get_option( 'posts_per_page' ) ),
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
				)
			);
		}

		$filters = $module->get_query_string_filters();

		if ( ! $filters ) {
			return $vars;
		}

		if ( ! isset( $vars['tax_query'] ) || ! is_array( $vars['tax_query'] ) ) {
			$vars['tax_query'] = array();
		}

		foreach ( array_keys( $filters ) as $widget_id ) {
			$vars = $module->filter_loop_query( $vars, $this->widget( (string) $widget_id ) );
		}

		// A chosen type replaces the type archive's own term, so the filter can move between types from a type page.
		foreach ( $filters as $filter ) {
			$terms = $filter['taxonomy'][ Projects::TYPE_TAX ]['terms'] ?? array();

			if ( $terms && '' !== $terms[0] ) {
				unset( $vars[ Projects::TYPE_TAX ] );

				// WordPress also restates a taxonomy archive's term as the generic `taxonomy` and `term` variables.
				if ( Projects::TYPE_TAX === ( $vars['taxonomy'] ?? '' ) ) {
					unset( $vars['taxonomy'], $vars['term'] );
				}
			}
		}

		return $vars;
	}

	/**
	 * On a type archive, start the type filter on that type: Pro renders the active item from its filter state and
	 * its script takes the same state, so choosing another type or "All" then refreshes the grid as usual.
	 *
	 * @param \Elementor\Element_Base $element The element about to render.
	 */
	public function preselect( $element ): void {
		$module = $this->module();

		if ( ! $module || 'taxonomy-filter' !== $element->get_name() ) {
			return;
		}

		$taxonomy = (string) $element->get_settings( 'taxonomy' );
		$grid     = (string) $element->get_settings( 'selected_element' );
		$term     = get_queried_object();

		if ( '' === $taxonomy || '' === $grid || ! is_tax( $taxonomy ) || ! $term instanceof \WP_Term ) {
			return;
		}

		if ( isset( $module->get_query_string_filters()[ $grid ]['taxonomy'][ $taxonomy ] ) ) {
			return;
		}

		$module->register_widget_filter(
			$grid,
			array(
				'taxonomy' => array(
					$taxonomy => array(
						'terms'       => array( $term->slug ),
						'logicalJoin' => 'AND',
					),
				),
			)
		);
	}

	private function module(): ?\ElementorPro\Modules\LoopFilter\Module {
		if ( ! class_exists( \ElementorPro\Plugin::class ) ) {
			return null;
		}

		$module = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'loop-filter' );

		return $module instanceof \ElementorPro\Modules\LoopFilter\Module ? $module : null;
	}

	/** Whether this is Pro's request for a grid's new markup after a filter was chosen. */
	private function is_refresh_request(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && str_contains( (string) ( $GLOBALS['wp']->query_vars['rest_route'] ?? '' ), 'refresh-loop' );
	}

	/** The one thing Pro's query filter asks of a widget: its id. */
	private function widget( string $id ): object {
		return new class( $id ) {
			public function __construct( private string $id ) {}

			public function get_id(): string {
				return $this->id;
			}
		};
	}
}
