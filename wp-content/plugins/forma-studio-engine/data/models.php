<?php
/**
 * Study-model recipes: one block-built massing study per project (keyed by project slug) plus the studio, the
 * Lisbon block, the empty plot and the process stages (keyed by recipe name). Read by Forma\Engine\Model\Models.
 *
 * Units: 1 ≈ 4 m. Every model stands on a slab plinth of about 6.4 × 4.2 whose top is at y = 0.18. A volume is
 *   kind     box | gable | cylinder | slab | wire   (gable: w = span across X, h = ridge height, d = ridge length along Z;
 *                                                    cylinder: w = diameter; wire: a dashed blue outline of a box)
 *   w, h, d  size along X, up and along Z
 *   x, y, z  centre X, BASE height, centre Z
 *   rot      degrees around the vertical axis
 *   material foam | shade | ink | glass | wire
 *   part     label shown in the exploded view ('' = unlabelled)
 *   stage    1–6, used by the process recipe only (0 = always there)
 * `camera.distance` is a multiplier of the camera preset's distance (default 1).
 */

defined( 'ABSPATH' ) || exit;

/**
 * One volume, with every key present.
 */
$v = static fn( string $kind, float $w, float $h, float $d, float $x, float $y, float $z, string $material = 'foam', string $part = '', float $rot = 0, int $stage = 0 ): array => array(
	'kind'     => $kind,
	'w'        => $w,
	'h'        => $h,
	'd'        => $d,
	'x'        => $x,
	'y'        => $y,
	'z'        => $z,
	'rot'      => $rot,
	'material' => $material,
	'part'     => $part,
	'stage'    => $stage,
);

$p      = 0.18; // Top of the plinth.
$plinth = $v( 'slab', 6.4, 0.18, 4.2, 0, 0, 0, 'foam' );

/*
 * Forma Pavilion: six courses of identical beams, laid in alternating directions around a round opening.
 */
$pavilion = array( $plinth );

for ( $i = 0; $i < 6; $i++ ) {
	$y    = $p + $i * 0.26;
	$even = 0 === $i % 2;
	$name = 0 === $i ? 'Base course' : ( 5 === $i ? 'Top course' : '' );

	// Even courses run the long beams along X, odd courses along Z, so the corners lock like a log stack.
	$pavilion[] = $even ? $v( 'box', 3.4, 0.26, 0.3, 0, $y, -1.35, 'foam', $name ) : $v( 'box', 2.8, 0.26, 0.3, 0, $y, -1.35 );
	$pavilion[] = $even ? $v( 'box', 3.4, 0.26, 0.3, 0, $y, 1.35 ) : $v( 'box', 2.8, 0.26, 0.3, 0, $y, 1.35 );
	$pavilion[] = $even ? $v( 'box', 0.3, 0.26, 2.4, -1.55, $y, 0 ) : $v( 'box', 0.3, 0.26, 3.0, -1.55, $y, 0 );
	$pavilion[] = $even ? $v( 'box', 0.3, 0.26, 2.4, 1.55, $y, 0 ) : $v( 'box', 0.3, 0.26, 3.0, 1.55, $y, 0 );
}

$pavilion[] = $v( 'cylinder', 2.4, 0.06, 2.4, 0, $p + 1.56, 0, 'glass', 'Round opening' );
$pavilion[] = $v( 'box', 1.3, 0.12, 0.7, 0, $p, -0.7, 'ink', 'Speakers platform' );

/*
 * Concrete Garden: a sunken garden between retaining terraces, a long concrete roof on columns, a water channel.
 */
$garden = array(
	$plinth,
	$v( 'box', 1.3, 0.5, 3.8, -2.45, $p, 0, 'shade', 'Retaining terrace' ),
	$v( 'box', 1.3, 0.5, 3.8, 2.45, $p, 0, 'shade' ),
	$v( 'box', 3.6, 0.5, 1.1, 0, $p, -1.35, 'shade', 'Pavilion terrace' ),
	$v( 'box', 3.6, 0.3, 0.8, 0, $p, 1.5, 'shade' ),
	$v( 'box', 3.6, 0.08, 1.9, 0, $p, 0.15, 'foam', 'Sunken garden' ),
	$v( 'box', 3.6, 0.18, 0.4, 0, $p, -0.6, 'foam' ),
	$v( 'box', 3.6, 0.12, 0.4, 0, $p, 0.95, 'foam' ),
	$v( 'box', 3.4, 0.04, 0.16, 0, $p + 0.09, 0.2, 'glass', 'Water channel' ),
	$v( 'box', 3.8, 0.14, 1.5, 0, $p + 1.45, -1.35, 'foam', 'Concrete roof' ),
	$v( 'box', 0.12, 0.95, 0.12, -1.55, $p + 0.5, -0.95 ),
	$v( 'box', 0.12, 0.95, 0.12, 1.55, $p + 0.5, -0.95 ),
	$v( 'box', 0.12, 0.95, 0.12, -1.55, $p + 0.5, -1.75 ),
	$v( 'box', 0.12, 0.95, 0.12, 1.55, $p + 0.5, -1.75 ),
	$v( 'cylinder', 0.1, 0.5, 0.1, -1.0, $p + 0.08, 0.45, 'ink' ),
	$v( 'cylinder', 0.75, 0.32, 0.75, -1.0, $p + 0.58, 0.45, 'foam', 'Fig trees' ),
	$v( 'cylinder', 0.1, 0.5, 0.1, 0.4, $p + 0.08, 0.85, 'ink' ),
	$v( 'cylinder', 0.75, 0.32, 0.75, 0.4, $p + 0.58, 0.85, 'foam' ),
	$v( 'cylinder', 0.1, 0.5, 0.1, 1.2, $p + 0.08, 0.35, 'ink' ),
	$v( 'cylinder', 0.75, 0.32, 0.75, 1.2, $p + 0.58, 0.35, 'foam' ),
);

/*
 * Terra Residence: four rammed-earth volumes stepping down olive terraces between dry-stone walls.
 */
$terra = array( $plinth );

foreach ( array( 0, 1, 2, 3 ) as $i ) {
	$x         = -2.25 + 1.5 * $i;
	$thickness = 1.1 - 0.3 * $i;
	$top       = $p + $thickness;
	$z         = array( 0.0, -0.3, 0.3, -0.2 )[ $i ];

	$terra[] = $v( 'box', 1.5, $thickness, 3.6, $x, $p, 0, 'foam', 0 === $i ? 'Terraces' : '' );
	$terra[] = $v( 'box', 1.1, 0.6, 1.5, $x, $top, $z, 'shade', 0 === $i ? 'Rammed-earth volumes' : '' );
	$terra[] = $v( 'box', 1.35, 0.07, 1.75, $x, $top + 0.6, $z, 'foam', 0 === $i ? 'Roofs' : '' );
	$terra[] = $v( 'box', 1.5, 0.22, 0.1, $x, $top, 1.65, 'ink', 0 === $i ? 'Dry-stone walls' : '' );
}

$terra[] = $v( 'cylinder', 0.08, 0.4, 0.08, -2.2, $p + 1.1, 1.0, 'ink' );
$terra[] = $v( 'cylinder', 0.7, 0.3, 0.7, -2.2, $p + 1.45, 1.0, 'foam', 'Olive trees' );
$terra[] = $v( 'cylinder', 0.08, 0.4, 0.08, -0.7, $p + 0.8, -1.2, 'ink' );
$terra[] = $v( 'cylinder', 0.7, 0.3, 0.7, -0.7, $p + 1.15, -1.2, 'foam' );
$terra[] = $v( 'cylinder', 0.08, 0.4, 0.08, 1.9, $p + 0.2, 1.0, 'ink' );
$terra[] = $v( 'cylinder', 0.7, 0.3, 0.7, 1.9, $p + 0.55, 1.0, 'foam' );

/*
 * Atelier 27: a harbour warehouse (glass, so the inside reads) divided by a long wall of oak shelving.
 */
$atelier = array(
	$plinth,
	$v( 'gable', 2.4, 1.5, 5.4, 0, $p, 0, 'glass', 'Warehouse', 90 ),
	$v( 'box', 4.8, 0.8, 0.12, 0, $p, 0, 'shade', 'Oak shelving wall' ),
	$v( 'box', 0.7, 0.28, 0.35, -1.9, $p, -0.75, 'foam', 'Throwing and glazing' ),
	$v( 'box', 0.7, 0.28, 0.35, -0.9, $p, -0.75 ),
	$v( 'box', 0.7, 0.28, 0.35, 0.1, $p, -0.75 ),
	$v( 'cylinder', 0.55, 0.7, 0.55, 1.2, $p, -0.75, 'ink', 'Kiln room' ),
	$v( 'cylinder', 0.55, 0.7, 0.55, 2.05, $p, -0.75, 'ink' ),
	$v( 'box', 1.3, 0.32, 0.4, -1.0, $p, 0.7, 'shade', 'Shop' ),
	$v( 'box', 0.7, 0.22, 0.45, 0.6, $p, 0.7 ),
	$v( 'box', 0.7, 0.22, 0.45, 1.7, $p, 0.7 ),
	$v( 'box', 0.4, 0.6, 1.2, -2.5, $p, 0.1, 'shade', 'Storage' ),
	$v( 'box', 0.4, 0.6, 1.2, 2.5, $p, 0.1, 'shade' ),
);

/*
 * House of Light: a flat opened into one run of rooms along the courtyard window, with a deep window seat.
 */
$light = array(
	$plinth,
	$v( 'box', 5.0, 0.06, 3.0, 0, $p, 0, 'shade', 'White-oiled oak floor' ),
	$v( 'box', 5.0, 0.8, 0.1, 0, $p + 0.06, -1.45, 'foam', 'Walls' ),
	$v( 'box', 0.1, 0.8, 3.0, -2.45, $p + 0.06, 0 ),
	$v( 'box', 0.1, 0.8, 3.0, 2.45, $p + 0.06, 0 ),
	$v( 'box', 5.0, 0.8, 0.04, 0, $p + 0.06, 1.46, 'glass', 'Courtyard window' ),
	$v( 'box', 0.1, 0.8, 1.1, -0.8, $p + 0.06, -0.9, 'foam', 'Remaining partitions' ),
	$v( 'box', 0.1, 0.8, 0.9, 1.0, $p + 0.06, -1.0 ),
	$v( 'box', 1.2, 0.28, 0.45, 1.2, $p + 0.06, 1.2, 'shade', 'Window seat' ),
	$v( 'box', 1.3, 0.36, 0.55, -1.5, $p + 0.06, -0.1, 'foam', 'Kitchen' ),
	$v( 'box', 1.0, 0.3, 0.6, 0.2, $p + 0.06, 0.3, 'shade' ),
	$v( 'box', 1.2, 0.26, 0.5, -1.3, $p + 0.06, 1.0, 'foam' ),
	$v( 'box', 1.5, 0.24, 1.0, 1.7, $p + 0.06, -0.55, 'foam' ),
	$v( 'box', 0.6, 0.7, 0.4, -2.1, $p + 0.06, -1.15, 'shade' ),
	$v( 'wire', 3.2, 0.6, 2.6, 0, $p + 0.95, 0.3, 'wire', 'Daylight reach' ),
);

/*
 * Monolith House: one heavy stone block with a deep cut, a wind wall behind it and a grass roof.
 */
$monolith = array(
	$plinth,
	$v( 'box', 2.3, 1.6, 2.0, -1.95, $p, 0.1, 'shade', 'Stone wall' ),
	$v( 'box', 2.3, 1.6, 2.0, 1.95, $p, 0.1, 'shade' ),
	$v( 'box', 1.6, 0.55, 2.0, 0, $p + 1.05, 0.1, 'shade', 'Lintel' ),
	$v( 'box', 1.6, 1.05, 0.08, 0, $p, -0.4, 'glass', 'Glazed recess' ),
	$v( 'box', 1.6, 0.06, 1.3, 0, $p, 0.4, 'foam', 'Timber lining' ),
	$v( 'box', 5.8, 1.9, 0.3, 0, $p, -1.45, 'foam', 'Wind wall' ),
	$v( 'box', 5.8, 0.12, 2.3, 0, $p + 1.6, 0.1, 'foam', 'Machair roof' ),
	$v( 'box', 1.2, 0.18, 0.8, -2.4, $p, 1.55, 'foam' ),
	$v( 'box', 1.2, 0.18, 0.8, 0.3, $p, 1.75, 'foam' ),
	$v( 'box', 1.2, 0.18, 0.8, 2.2, $p, 1.45, 'foam' ),
);

/*
 * Axis Workspace: four floors around one timber stair, inside the original facade.
 */
$axis = array(
	$plinth,
	$v( 'box', 4.6, 2.5, 3.0, 0, $p, 0, 'glass', 'Existing facade' ),
	$v( 'box', 4.4, 0.1, 2.8, 0, $p, 0, 'foam', 'Ground floor' ),
);

foreach ( array( 1, 2, 3 ) as $level ) {
	$y      = $p + $level * 0.65;
	$names  = array( 1 => 'First floor', 2 => 'Second floor', 3 => 'Third floor' );
	$axis[] = $v( 'box', 1.7, 0.1, 2.8, -1.35, $y, 0, 'foam', $names[ $level ] );
	$axis[] = $v( 'box', 1.7, 0.1, 2.8, 1.35, $y, 0 );
}

foreach ( array( 0, 1, 2 ) as $flight ) {
	foreach ( array( 0, 1, 2, 3 ) as $step ) {
		$x      = 0 === $flight % 2 ? -0.375 + 0.25 * $step : 0.375 - 0.25 * $step;
		$axis[] = $v( 'box', 0.25, 0.14 * ( $step + 1 ), 0.8, $x, $p + $flight * 0.65 + 0.1, 0 === $flight % 2 ? -0.5 : 0.5, 'shade', 0 === $flight && 0 === $step ? 'Timber stair' : '' );
	}
}

/*
 * Casa Nera: a raised ring of black timber rooms around a shaded courtyard, two gabled wings.
 */
$casa = array( $plinth );

foreach ( array( -2.2, -1.1, 0, 1.1, 2.2 ) as $px ) {
	foreach ( array( -1.3, 1.3 ) as $pz ) {
		$casa[] = $v( 'cylinder', 0.12, 0.45, 0.12, $px, $p, $pz, 'ink', -2.2 === $px && -1.3 === $pz ? 'Timber piles' : '' );
	}
}

$casa[] = $v( 'box', 5.4, 0.1, 3.4, 0, $p + 0.45, 0, 'shade', 'Raised deck' );
$casa[] = $v( 'gable', 1.2, 1.35, 4.8, 0, $p + 0.55, -1.1, 'ink', 'North wing', 90 );
$casa[] = $v( 'gable', 1.2, 1.15, 4.2, -0.3, $p + 0.55, 1.1, 'ink', 'South wing', 90 );
$casa[] = $v( 'box', 1.0, 0.85, 1.0, -2.0, $p + 0.55, 0, 'ink' );
$casa[] = $v( 'box', 1.0, 0.85, 1.0, 1.9, $p + 0.55, 0, 'ink' );
$casa[] = $v( 'box', 2.9, 0.05, 1.0, -0.05, $p + 0.55, 0, 'foam', 'Courtyard' );
$casa[] = $v( 'cylinder', 0.1, 0.7, 0.1, 0.1, $p + 0.6, 0, 'ink' );
$casa[] = $v( 'cylinder', 1.0, 0.35, 1.0, 0.1, $p + 1.2, 0, 'foam', 'Courtyard tree' );
$casa[] = $v( 'box', 2.9, 0.7, 0.04, -0.05, $p + 0.6, -0.5, 'glass' );
$casa[] = $v( 'box', 2.9, 0.7, 0.04, -0.05, $p + 0.6, 0.5, 'glass' );
$casa[] = $v( 'box', 1.2, 0.22, 0.3, -1.2, $p, 1.95, 'foam', 'Entrance steps' );
$casa[] = $v( 'box', 1.2, 0.4, 0.3, -1.2, $p, 1.75, 'foam' );

/*
 * The Quiet Hotel: two restored townhouses on the street, a two-storey garden wing and a covered walk around a garden.
 */
$hotel = array(
	$plinth,
	$v( 'gable', 1.5, 1.1, 1.7, -1.0, $p, 1.3, 'foam', 'Restored townhouse', 90 ),
	$v( 'gable', 1.5, 1.1, 1.7, 0.8, $p, 1.3, 'foam', '', 90 ),
	$v( 'box', 5.2, 0.6, 1.2, 0, $p, -1.35, 'foam', 'Garden wing' ),
	$v( 'box', 4.8, 0.55, 1.0, 0, $p + 0.6, -1.35, 'shade', 'Cedar upper floor' ),
	$v( 'box', 5.6, 0.07, 1.4, 0, $p + 1.15, -1.35, 'foam', 'Roof' ),
	$v( 'box', 0.6, 0.05, 2.4, -2.55, $p + 0.55, 0.05, 'foam', 'Covered walk' ),
	$v( 'box', 0.07, 0.55, 0.07, -2.55, $p, -0.95 ),
	$v( 'box', 0.07, 0.55, 0.07, -2.55, $p, -0.2 ),
	$v( 'box', 0.07, 0.55, 0.07, -2.55, $p, 0.55 ),
	$v( 'box', 0.07, 0.55, 0.07, -2.55, $p, 1.2 ),
	$v( 'cylinder', 1.1, 0.03, 1.1, 0.9, $p, 0.0, 'glass', 'Pond' ),
	$v( 'cylinder', 0.5, 0.05, 0.5, -0.9, $p, 0.2, 'shade', 'Moss garden' ),
	$v( 'cylinder', 0.5, 0.05, 0.5, 0.0, $p, -0.4, 'shade' ),
	$v( 'cylinder', 0.5, 0.05, 0.5, 1.9, $p, 0.5, 'shade' ),
	$v( 'cylinder', 0.1, 0.45, 0.1, -0.4, $p, 0.55, 'ink' ),
	$v( 'cylinder', 0.8, 0.4, 0.8, -0.4, $p + 0.4, 0.55, 'foam', 'Maple' ),
);

/*
 * Plinth Series: furniture from stacked travertine offcuts and oak, shown larger than the buildings.
 */
$series = array(
	$plinth,
	$v( 'box', 0.45, 0.32, 0.6, -2.15, $p, -0.8, 'foam', 'Low table' ),
	$v( 'box', 0.45, 0.32, 0.6, -1.25, $p, -0.8 ),
	$v( 'box', 1.5, 0.1, 0.8, -1.7, $p + 0.32, -0.8, 'shade' ),
	$v( 'box', 0.55, 0.22, 0.55, 0.0, $p, -0.9, 'foam', 'Side table' ),
	$v( 'box', 0.55, 0.22, 0.55, 0.0, $p + 0.22, -0.9 ),
	$v( 'box', 0.6, 0.06, 0.6, 0.0, $p + 0.44, -0.9, 'shade' ),
	$v( 'cylinder', 0.6, 0.5, 0.6, 1.0, $p, -0.9, 'foam', 'Second side table' ),
	$v( 'cylinder', 0.66, 0.06, 0.66, 1.0, $p + 0.5, -0.9, 'shade' ),
	$v( 'box', 0.5, 0.35, 0.45, -2.2, $p, 0.9, 'foam', 'Bench' ),
	$v( 'box', 0.5, 0.35, 0.45, -0.9, $p, 0.9 ),
	$v( 'box', 1.9, 0.08, 0.5, -1.55, $p + 0.35, 0.9, 'shade' ),
	$v( 'box', 0.55, 0.18, 0.55, 0.3, $p, 0.9, 'foam', 'Lamp base' ),
	$v( 'box', 0.42, 0.18, 0.42, 0.3, $p + 0.18, 0.9 ),
	$v( 'box', 0.3, 0.18, 0.3, 0.3, $p + 0.36, 0.9 ),
	$v( 'cylinder', 0.36, 0.1, 0.36, 0.3, $p + 0.54, 0.9, 'ink', 'Lamp' ),
	$v( 'box', 0.14, 1.1, 0.4, 1.6, $p, 0.7, 'foam', 'Shelf' ),
	$v( 'box', 0.14, 1.1, 0.4, 2.8, $p, 0.7 ),
	$v( 'box', 1.2, 0.07, 0.4, 2.2, $p + 0.3, 0.7, 'shade' ),
	$v( 'box', 1.2, 0.07, 0.4, 2.2, $p + 0.65, 0.7, 'shade' ),
	$v( 'box', 1.2, 0.07, 0.4, 2.2, $p + 1.0, 0.7, 'shade' ),
	$v( 'cylinder', 0.5, 0.42, 0.5, 2.2, $p, -0.9, 'foam', 'Stool' ),
	$v( 'cylinder', 0.56, 0.06, 0.56, 2.2, $p + 0.42, -0.9, 'shade' ),
);

/*
 * Northline Residence: a timber house bent around the rock, one wing finished, one still a frame.
 */
$northline = array(
	$plinth,
	$v( 'box', 1.8, 0.55, 1.3, -1.6, $p, 0.7, 'shade', 'Rock', 20 ),
	$v( 'box', 1.0, 0.8, 0.9, -0.9, $p, 1.5, 'shade', '', -15 ),
	$v( 'box', 0.8, 0.35, 0.7, -2.5, $p, 1.4, 'shade', '', 40 ),
	$v( 'gable', 1.2, 1.0, 2.6, -1.7, $p, -1.2, 'foam', 'Finished wing', 90 ),
	$v( 'box', 1.3, 0.9, 1.3, 0.25, $p, -1.2, 'glass', 'Living room' ),
);

foreach ( array( -0.35, 0.35, 1.05, 1.75 ) as $pz ) {
	$northline[] = $v( 'box', 0.1, 1.0, 0.1, -0.3, $p, $pz, 'foam', -0.35 === $pz ? 'Timber frame' : '' );
	$northline[] = $v( 'box', 0.1, 1.0, 0.1, 0.8, $p, $pz );
}

$northline[] = $v( 'box', 0.1, 0.1, 2.5, -0.3, $p + 1.0, 0.7 );
$northline[] = $v( 'box', 0.1, 0.1, 2.5, 0.8, $p + 1.0, 0.7 );

foreach ( array( 0.0, 0.7, 1.4 ) as $pz ) {
	$northline[] = $v( 'wire', 1.1, 1.0, 0.7, 0.25, $p, $pz, 'wire', 0.0 === $pz ? 'Bays still to build' : '' );
}

/*
 * The studio building: a three-floor warehouse. Each floor is a team.
 */
$studio = array(
	$plinth,
	$v( 'box', 4.4, 0.8, 2.6, 0, $p, 0, 'foam', 'Workshop and objects' ),
	$v( 'box', 4.4, 0.8, 2.6, 0, $p + 0.85, 0, 'foam', 'Interiors' ),
	$v( 'box', 4.4, 0.8, 2.6, 0, $p + 1.7, 0, 'foam', 'Architecture' ),
	$v( 'gable', 2.7, 0.9, 4.6, 0, $p + 2.5, 0, 'shade', 'Roof', 90 ),
	$v( 'box', 4.0, 0.35, 0.04, 0, $p + 0.25, 1.32, 'glass' ),
	$v( 'box', 4.0, 0.35, 0.04, 0, $p + 1.1, 1.32, 'glass' ),
	$v( 'box', 4.0, 0.35, 0.04, 0, $p + 1.95, 1.32, 'glass' ),
	$v( 'box', 0.7, 0.6, 0.05, -1.4, $p, 1.33, 'ink', 'Entrance' ),
	$v( 'box', 1.0, 0.08, 0.08, 2.7, $p + 2.4, 0, 'ink', 'Hoist beam' ),
	$v( 'box', 0.03, 0.4, 0.03, 3.1, $p + 2.0, 0, 'ink' ),
	$v( 'box', 0.8, 2.7, 0.9, -2.6, $p, -0.2, 'shade', 'Stair tower' ),
);

/*
 * The Lisbon block: a perimeter block of buildings around a courtyard, the studio in ink and a blue pin above it.
 */
$block = array( $plinth );

foreach ( array( -2.3, -1.15, 0, 1.15, 2.3 ) as $i => $bx ) {
	$north = array( 1.1, 1.6, 1.3, 1.9, 1.2 )[ $i ];
	$south = array( 1.4, 1.0, 1.7, 1.3, 1.5 )[ $i ];

	$block[] = 1 === $i || 3 === $i
		? $v( 'gable', 1.1, $north, 1.1, $bx, $p, -1.45, 'foam' )
		: $v( 'box', 1.1, $north, 1.1, $bx, $p, -1.45, 0 === $i % 2 ? 'foam' : 'shade' );
	$block[] = 2 === $i
		? $v( 'box', 1.1, $south, 1.1, $bx, $p, 1.45, 'ink', 'Studio' )
		: ( 0 === $i
			? $v( 'gable', 1.1, $south, 1.1, $bx, $p, 1.45, 'foam' )
			: $v( 'box', 1.1, $south, 1.1, $bx, $p, 1.45, 3 === $i ? 'shade' : 'foam' ) );
}

$block[] = $v( 'box', 0.9, 1.2, 1.6, -2.65, $p, 0, 'foam' );
$block[] = $v( 'box', 0.9, 0.9, 1.6, 2.65, $p, 0, 'shade' );
$block[] = $v( 'cylinder', 0.1, 0.4, 0.1, 0, $p, 0, 'ink' );
$block[] = $v( 'cylinder', 0.8, 0.3, 0.8, 0, $p + 0.4, 0, 'foam' );
$block[] = $v( 'wire', 0.14, 1.1, 0.14, 0, $p + 1.75, 1.45, 'wire', 'Studio pin' );

/*
 * The empty plot: neighbours on either side and one dashed volume for what is not yet built.
 */
$plot = array(
	$plinth,
	$v( 'slab', 3.0, 0.03, 2.4, 0, $p, 0, 'shade', 'Plot' ),
	$v( 'box', 1.4, 1.4, 2.6, -2.4, $p, -0.1, 'foam', 'Neighbour' ),
	$v( 'box', 1.2, 1.0, 2.4, 2.5, $p, 0.2, 'shade' ),
	$v( 'wire', 2.6, 1.8, 2.0, 0, $p + 0.03, 0, 'wire', 'Your project' ),
	$v( 'box', 1.0, 0.04, 1.2, 0, $p, 1.7, 'shade', 'Path' ),
	$v( 'cylinder', 0.1, 0.5, 0.1, -1.5, $p, 1.6, 'ink' ),
	$v( 'cylinder', 0.8, 0.4, 0.8, -1.5, $p + 0.5, 1.6, 'foam', 'Trees' ),
	$v( 'cylinder', 0.1, 0.5, 0.1, 1.6, $p, -1.6, 'ink' ),
	$v( 'cylinder', 0.8, 0.4, 0.8, 1.6, $p + 0.5, -1.6, 'foam' ),
);

/*
 * The process: one model that grows through six stages. Stage 0 is the plinth, which is always there.
 */
$process = array( $plinth );

foreach ( array( array( 5.2, 3.6 ), array( 4.2, 2.8 ), array( 3.2, 2.0 ), array( 2.4, 1.4 ) ) as $i => $size ) {
	$process[] = $v( 'slab', $size[0], 0.08, $size[1], 0, $p + 0.08 * $i, 0, 'shade', 0 === $i ? 'Contours' : '', 0, 1 );
}

$base = $p + 0.32;

$process[] = $v( 'wire', 2.6, 1.0, 1.2, -0.3, $base, 0, 'wire', 'Rough massing', 0, 2 );
$process[] = $v( 'wire', 1.2, 0.7, 1.0, 1.4, $base, 0.2, 'wire', '', 0, 2 );
$process[] = $v( 'wire', 0.9, 1.6, 0.9, -1.2, $base, -0.1, 'wire', '', 0, 2 );

$process[] = $v( 'box', 2.5, 0.95, 1.1, -0.3, $base, 0, 'foam', 'Refined volumes', 0, 3 );
$process[] = $v( 'box', 1.1, 0.65, 0.9, 1.4, $base, 0.2, 'foam', '', 0, 3 );
$process[] = $v( 'box', 0.8, 1.55, 0.8, -1.2, $base, -0.1, 'foam', '', 0, 3 );

// Section cuts: thin ink plates at the levels where the volumes are cut, a little wider than the volumes.
$process[] = $v( 'slab', 2.8, 0.03, 1.4, -0.3, $base + 0.3, 0, 'ink', 'Section cuts', 0, 4 );
$process[] = $v( 'slab', 2.8, 0.03, 1.4, -0.3, $base + 0.65, 0, 'ink', '', 0, 4 );
$process[] = $v( 'slab', 1.4, 0.03, 1.2, 1.4, $base + 0.3, 0.2, 'ink', '', 0, 4 );
$process[] = $v( 'slab', 1.1, 0.03, 1.1, -1.2, $base + 0.8, -0.1, 'ink', '', 0, 4 );

foreach ( array( -1.6, -0.3, 1.0 ) as $px ) {
	$process[] = $v( 'box', 0.1, 1.1, 0.1, $px, $base, -0.78, 'foam', -1.6 === $px ? 'Structural frame' : '', 0, 5 );
	$process[] = $v( 'box', 0.1, 1.1, 0.1, $px, $base, 0.78, 'foam', '', 0, 5 );
}

$process[] = $v( 'box', 3.0, 0.08, 0.1, -0.3, $base + 1.1, -0.78, 'foam', '', 0, 5 );
$process[] = $v( 'box', 3.0, 0.08, 0.1, -0.3, $base + 1.1, 0.78, 'foam', '', 0, 5 );

$process[] = $v( 'box', 2.8, 0.07, 1.3, -0.3, $base + 0.95, 0, 'foam', 'Roof and finish', 0, 6 );
$process[] = $v( 'box', 1.3, 0.07, 1.1, 1.4, $base + 0.65, 0.2, 'foam', '', 0, 6 );
$process[] = $v( 'box', 0.7, 0.4, 0.04, -0.3, $base + 0.3, 0.57, 'glass', '', 0, 6 );

return array(
	'forma-pavilion'      => array( 'volumes' => $pavilion ),
	'concrete-garden'     => array( 'volumes' => $garden ),
	'terra-residence'     => array( 'volumes' => $terra ),
	'atelier-27'          => array( 'volumes' => $atelier ),
	'house-of-light'      => array( 'volumes' => $light ),
	'monolith-house'      => array( 'volumes' => $monolith ),
	'axis-workspace'      => array( 'volumes' => $axis ),
	'casa-nera'           => array(
		'volumes' => $casa,
		'camera'  => array( 'distance' => 1.25 ),
	),
	'the-quiet-hotel'     => array( 'volumes' => $hotel ),
	'plinth-series'       => array(
		'volumes' => $series,
		'camera'  => array( 'distance' => 0.82 ),
	),
	'northline-residence' => array( 'volumes' => $northline ),
	'studio'              => array( 'volumes' => $studio ),
	'lisbon-block'        => array( 'volumes' => $block ),
	'plot'                => array( 'volumes' => $plot ),
	'process'             => array(
		'volumes' => $process,
		'camera'  => array( 'distance' => 0.82 ),
	),
);
