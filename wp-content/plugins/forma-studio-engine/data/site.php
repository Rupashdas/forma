<?php
/**
 * Site-wide content: name, project types, pages and menus. Read by the seeders only.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'blog'       => array(
		'name'        => 'FORMA',
		'description' => 'Architecture, interiors and objects. A studio in Lisbon.',
	),

	'types'      => array(
		'residential' => array(
			'name'        => 'Residential',
			'description' => 'Houses designed for one family and one place.',
		),
		'interiors'   => array(
			'name'        => 'Interiors',
			'description' => 'Rooms reworked inside existing buildings.',
		),
		'hospitality' => array(
			'name'        => 'Hospitality',
			'description' => 'Hotels and places to stay.',
		),
		'workplace'   => array(
			'name'        => 'Workplace',
			'description' => 'Offices and studios where people make things together.',
		),
		'cultural'    => array(
			'name'        => 'Cultural',
			'description' => 'Pavilions, gardens and public rooms.',
		),
		'objects'     => array(
			'name'        => 'Objects',
			'description' => 'Furniture and pieces made at the scale of the hand.',
		),
	),

	'pages'      => array(
		'home'     => array(
			'title'   => 'Home',
			'excerpt' => 'FORMA is an architecture and interiors studio in Lisbon, designing houses, hotels, workplaces and objects across Europe and Japan.',
		),
		'studio'   => array(
			'title'   => 'Studio',
			'excerpt' => 'Founded in Lisbon in 2011, FORMA is a team of 23 architects, interior designers and makers working on buildings, rooms and objects.',
		),
		'services' => array(
			'title'   => 'Services',
			'excerpt' => 'Architecture, interior design, hospitality, workplace, art direction and furniture: what FORMA does and how we work with clients.',
		),
		'process'  => array(
			'title'   => 'Process',
			'excerpt' => 'How a FORMA project runs, from the first site visit to the day you move in: six stages, and what you receive at each one.',
		),
		'contact'  => array(
			'title'   => 'Contact',
			'excerpt' => 'Start a conversation with FORMA about a house, hotel, workplace or interior. Studio in Lisbon, working across Europe and Japan.',
		),
		'colophon' => array(
			'title'   => 'Colophon',
			'excerpt' => 'Typefaces, photography credits, privacy, and the story behind FORMA, a fictional studio designed and built by Rupash Das.',
		),
	),

	'front_page' => 'home',

	'menus'      => array(
		'primary' => array(
			'name'     => 'Primary',
			'location' => 'menu-1',
			'items'    => array( 'archive:forma_project', 'page:studio', 'page:services', 'page:process', 'page:contact' ),
		),
		'footer'  => array(
			'name'     => 'Footer',
			'location' => 'menu-2',
			'items'    => array( 'archive:forma_project', 'page:studio', 'page:services', 'page:process', 'page:contact', 'page:colophon' ),
		),
	),
);
