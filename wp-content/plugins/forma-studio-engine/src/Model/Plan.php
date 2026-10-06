<?php

namespace Forma\Engine\Model;

defined( 'ABSPATH' ) || exit;

/**
 * A model drawn as a still: the same volumes the runtime builds, projected in axonometric and written as an inline SVG.
 *
 * It is what a study model shows when there is no photograph to stand in for it and the page cannot draw the 3D model (no
 * WebGL, no JavaScript, a print): a tidy drawing of the building instead of an empty box. It is the model, not an
 * illustration of it, so it can never disagree with it. Convex solids only (box, slab, cylinder, gable), each drawn
 * back to front with its visible faces shaded by one light; wire volumes are dashed outlines.
 */
final class Plan {

	/** Cylinders are drawn as polygons with this many sides. */
	private const SIDES = 20;

	/** Fill of each material. */
	private const FILL = array(
		'foam'   => '#fbfaf6',
		'shade'  => '#c6c4ba',
		'ink'    => '#1a1a19',
		'timber' => '#c4a074',
		'stone'  => '#dacdb6',
		'ground' => '#d3c8b2',
		'metal'  => '#5a4a3a',
		'leaf'   => '#8f9d78',
		'water'  => '#a9c0c4',
		'glass'  => '#e3e7ee',
	);

	/**
	 * @param list<array<string, mixed>> $volumes Volumes as Models::normalise() returns them.
	 * @return string The SVG, or an empty string when there is nothing to draw.
	 */
	public static function svg( array $volumes ): string {
		$solids = array();
		$wires  = array();

		foreach ( $volumes as $volume ) {
			$wire = 'wire' === ( $volume['kind'] ?? '' ) || 'wire' === ( $volume['material'] ?? '' );

			if ( $wire ) {
				$wires[] = self::place( $volume );
			} else {
				$solids[] = array(
					'depth'  => self::depth( array( (float) $volume['x'], (float) $volume['y'] + (float) $volume['h'] / 2, (float) $volume['z'] ) ),
					'ground' => 'slab' === $volume['kind'] && (float) $volume['y'] < 0.02 && empty( $volume['part'] ) && empty( $volume['stage'] ),
					'faces'  => self::faces( $volume ),
					'fill'   => self::FILL[ $volume['material'] ] ?? self::FILL['foam'],
					'glass'  => 'glass' === $volume['material'],
				);
			}
		}

		if ( ! $solids && ! $wires ) {
			return '';
		}

		// The plinth first, then everything else from the back of the scene to the front.
		usort(
			$solids,
			static fn( array $a, array $b ): int => array( ! $a['ground'], $a['depth'] ) <=> array( ! $b['ground'], $b['depth'] )
		);

		$paths  = array();
		$points = array();

		foreach ( $solids as $solid ) {
			$faces = array();

			foreach ( $solid['faces'] as $face ) {
				if ( self::dot( $face['n'], self::VIEW ) <= 0.0001 ) {
					continue;
				}

				$faces[] = $face + array( 'depth' => self::depth( $face['c'] ) );
			}

			usort( $faces, static fn( array $a, array $b ): int => $a['depth'] <=> $b['depth'] );

			foreach ( $faces as $face ) {
				$projected = array_map( array( self::class, 'project' ), $face['pts'] );
				$points    = array_merge( $points, $projected );
				$light     = 0.6 + 0.4 * max( 0.0, self::dot( $face['n'], self::LIGHT ) );

				$paths[] = sprintf(
					'<polygon points="%s" fill="%s"%s/>',
					self::list( $projected ),
					self::shade( $solid['fill'], $light ),
					$solid['glass'] ? ' fill-opacity=".55"' : ''
				);
			}
		}

		foreach ( $wires as $box ) {
			$corners = array_map( array( self::class, 'project' ), $box );
			$points  = array_merge( $points, $corners );

			foreach ( array( array( 0, 1 ), array( 1, 2 ), array( 2, 3 ), array( 3, 0 ), array( 4, 5 ), array( 5, 6 ), array( 6, 7 ), array( 7, 4 ), array( 0, 4 ), array( 1, 5 ), array( 2, 6 ), array( 3, 7 ) ) as [ $from, $to ] ) {
				$paths[] = sprintf(
					'<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#2b3bff" stroke-width="1.4" stroke-dasharray="4 3" fill="none"/>',
					self::n( $corners[ $from ][0] ),
					self::n( $corners[ $from ][1] ),
					self::n( $corners[ $to ][0] ),
					self::n( $corners[ $to ][1] )
				);
			}
		}

		$xs  = array_column( $points, 0 );
		$ys  = array_column( $points, 1 );
		$pad = 0.05 * max( max( $xs ) - min( $xs ), max( $ys ) - min( $ys ) );

		return sprintf(
			'<svg class="forma-model__fallback forma-model__plan" viewBox="%s %s %s %s" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false"><g stroke="#141414" stroke-opacity=".4" stroke-width="1" stroke-linejoin="round">%s</g></svg>',
			self::n( min( $xs ) - $pad ),
			self::n( min( $ys ) - $pad ),
			self::n( max( $xs ) - min( $xs ) + 2 * $pad ),
			self::n( max( $ys ) - min( $ys ) + 2 * $pad ),
			implode( '', $paths )
		);
	}

	/** Towards the viewer, in world space: the camera stands at +X, +Y and +Z (an isometric view). */
	private const VIEW = array( 0.57735, 0.57735, 0.57735 );

	/** The light, from above and to the right. */
	private const LIGHT = array( 0.35, 0.85, 0.4 );

	/**
	 * The faces of one solid in world space, each with its corners, its outward normal and its centre.
	 *
	 * @param array<string, mixed> $v A volume.
	 * @return list<array{pts: list<list<float>>, n: list<float>, c: list<float>}>
	 */
	private static function faces( array $v ): array {
		$w = (float) $v['w'];
		$h = (float) $v['h'];
		$d = (float) $v['d'];

		if ( 'cylinder' === $v['kind'] ) {
			$ring = array();

			for ( $i = 0; $i < self::SIDES; $i++ ) {
				$a      = 2 * M_PI * $i / self::SIDES;
				$ring[] = array( 0.5 * $w * cos( $a ), 0.5 * $d * sin( $a ) );
			}

			$solid = self::prism_y( $ring, 0.0, $h );
		} elseif ( 'gable' === $v['kind'] ) {
			$solid = self::prism_z(
				array(
					array( -0.5 * $w, 0.0 ),
					array( 0.5 * $w, 0.0 ),
					array( 0.5 * $w, 0.62 * $h ),
					array( 0.0, $h ),
					array( -0.5 * $w, 0.62 * $h ),
				),
				-0.5 * $d,
				0.5 * $d
			);
		} else {
			$solid = self::prism_y(
				array( array( -0.5 * $w, -0.5 * $d ), array( 0.5 * $w, -0.5 * $d ), array( 0.5 * $w, 0.5 * $d ), array( -0.5 * $w, 0.5 * $d ) ),
				0.0,
				$h
			);
		}

		$out = array();

		foreach ( $solid['faces'] as $face ) {
			$pts = array_map( static fn( array $p ): array => self::world( $p, $v ), $face );
			$out[] = array(
				'pts' => $pts,
				'n'   => self::normal( $pts, self::world( $solid['centre'], $v ) ),
				'c'   => self::centroid( $pts ),
			);
		}

		return $out;
	}

	/**
	 * A prism standing on the XZ plane: `$ring` (x, z pairs) extruded from y0 to y1.
	 *
	 * @param list<list<float>> $ring
	 * @return array{faces: list<list<list<float>>>, centre: list<float>}
	 */
	private static function prism_y( array $ring, float $y0, float $y1 ): array {
		$faces = array();
		$top   = array();
		$count = count( $ring );

		foreach ( $ring as $i => [ $x, $z ] ) {
			[ $nx, $nz ] = $ring[ ( $i + 1 ) % $count ];
			$faces[]     = array( array( $x, $y0, $z ), array( $nx, $y0, $nz ), array( $nx, $y1, $nz ), array( $x, $y1, $z ) );
			$top[]       = array( $x, $y1, $z );
		}

		$faces[] = $top;

		return array(
			'faces'  => $faces,
			'centre' => array( 0.0, ( $y0 + $y1 ) / 2, 0.0 ),
		);
	}

	/**
	 * A prism lying along Z: `$profile` (x, y pairs) extruded from z0 to z1.
	 *
	 * @param list<list<float>> $profile
	 * @return array{faces: list<list<list<float>>>, centre: list<float>}
	 */
	private static function prism_z( array $profile, float $z0, float $z1 ): array {
		$faces = array();
		$front = array();
		$back  = array();
		$count = count( $profile );
		$cy    = 0.0;

		foreach ( $profile as $i => [ $x, $y ] ) {
			[ $nx, $ny ] = $profile[ ( $i + 1 ) % $count ];
			$faces[]     = array( array( $x, $y, $z0 ), array( $nx, $ny, $z0 ), array( $nx, $ny, $z1 ), array( $x, $y, $z1 ) );
			$front[]     = array( $x, $y, $z1 );
			$back[]      = array( $x, $y, $z0 );
			$cy         += $y / $count;
		}

		$faces[] = $front;
		$faces[] = $back;

		return array(
			'faces'  => $faces,
			'centre' => array( 0.0, $cy, ( $z0 + $z1 ) / 2 ),
		);
	}

	/** A local point (before the volume's turn) in world space: turned about the vertical axis, then moved to the volume's base. */
	private static function world( array $p, array $v ): array {
		$a = deg2rad( (float) $v['rot'] );
		$c = cos( $a );
		$s = sin( $a );

		return array(
			(float) $v['x'] + $p[0] * $c + $p[2] * $s,
			(float) $v['y'] + $p[1],
			(float) $v['z'] - $p[0] * $s + $p[2] * $c,
		);
	}

	/** The eight corners of a wire volume's box, bottom ring then top ring. */
	private static function place( array $v ): array {
		$w = 0.5 * (float) $v['w'];
		$d = 0.5 * (float) $v['d'];
		$h = (float) $v['h'];

		return array(
			self::world( array( -$w, 0.0, -$d ), $v ),
			self::world( array( $w, 0.0, -$d ), $v ),
			self::world( array( $w, 0.0, $d ), $v ),
			self::world( array( -$w, 0.0, $d ), $v ),
			self::world( array( -$w, $h, -$d ), $v ),
			self::world( array( $w, $h, -$d ), $v ),
			self::world( array( $w, $h, $d ), $v ),
			self::world( array( -$w, $h, $d ), $v ),
		);
	}

	/** The unit normal of a face (from its first three corners), turned to point away from the solid's centre. */
	private static function normal( array $pts, array $centre ): array {
		$a = $pts[0];
		$u = array( $pts[1][0] - $a[0], $pts[1][1] - $a[1], $pts[1][2] - $a[2] );
		$w = array( $pts[2][0] - $a[0], $pts[2][1] - $a[1], $pts[2][2] - $a[2] );
		$n = array( $u[1] * $w[2] - $u[2] * $w[1], $u[2] * $w[0] - $u[0] * $w[2], $u[0] * $w[1] - $u[1] * $w[0] );
		$l = sqrt( $n[0] ** 2 + $n[1] ** 2 + $n[2] ** 2 ) ?: 1.0;
		$n = array( $n[0] / $l, $n[1] / $l, $n[2] / $l );
		$c = self::centroid( $pts );

		return self::dot( $n, array( $c[0] - $centre[0], $c[1] - $centre[1], $c[2] - $centre[2] ) ) < 0 ? array( -$n[0], -$n[1], -$n[2] ) : $n;
	}

	private static function centroid( array $pts ): array {
		$n = count( $pts );

		return array( array_sum( array_column( $pts, 0 ) ) / $n, array_sum( array_column( $pts, 1 ) ) / $n, array_sum( array_column( $pts, 2 ) ) / $n );
	}

	private static function dot( array $a, array $b ): float {
		return $a[0] * $b[0] + $a[1] * $b[1] + $a[2] * $b[2];
	}

	/** Distance towards the viewer: larger is nearer. */
	private static function depth( array $p ): float {
		return self::dot( $p, self::VIEW );
	}

	/** World to screen: x across (right is +X and -Z), y down. */
	private static function project( array $p ): array {
		return array(
			( $p[0] - $p[2] ) * 0.70711,
			-( -$p[0] * 0.40825 + $p[1] * 0.81650 - $p[2] * 0.40825 ),
		);
	}

	/** A list of points as an SVG `points` value. */
	private static function list( array $points ): string {
		return implode( ' ', array_map( static fn( array $p ): string => self::n( $p[0] ) . ',' . self::n( $p[1] ), $points ) );
	}

	private static function n( float $value ): string {
		return rtrim( rtrim( number_format( $value, 2, '.', '' ), '0' ), '.' );
	}

	/** A fill colour darkened by `$light` (1 = as is). */
	private static function shade( string $hex, float $light ): string {
		$rgb = sscanf( $hex, '#%02x%02x%02x' );

		return sprintf( '#%02x%02x%02x', (int) round( $rgb[0] * $light ), (int) round( $rgb[1] * $light ), (int) round( $rgb[2] * $light ) );
	}
}
