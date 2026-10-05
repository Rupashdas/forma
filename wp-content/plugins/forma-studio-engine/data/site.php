<?php
/**
 * Site-wide content: name, services, project types, pages and menus, plus the copy of the inner pages (Studio,
 * Services, Process, Contact, 404, Colophon). Read by the seeders only; the Seo module reads the FAQ.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'blog'       => array(
		'name'        => 'FORMA',
		'description' => 'Architecture, interiors and objects. A studio in Lisbon.',
	),

	/*
	 * Contact details are placeholders until the owner confirms them. Social links point to platform home pages only.
	 */
	'studio'     => array(
		'email'       => 'studio@forma.freedev.app',
		'phone'       => '+351 210 000 000',
		'address'     => array( 'Rua da Boavista 72, 2.º', '1200-066 Lisboa' ),
		'hours'       => 'Mon–Fri, 9:00–18:00',
		'coordinates' => '38°43′N 9°08′W',
		'founded'     => 2011,
		'social'      => array(
			'Instagram' => 'https://www.instagram.com/',
			'LinkedIn'  => 'https://www.linkedin.com/',
			'Pinterest' => 'https://www.pinterest.com/',
		),
	),

	/*
	 * What the studio does, in the order Home and Services list it. The slug is the anchor on the Services page.
	 * `line` is the one-sentence summary, `deliverables` and `timeline` fill the Services accordion panel, and
	 * `project` is the slug of a typical project: the Services stage swaps its study model to it.
	 */
	'services'   => array(
		'architecture'      => array(
			'name'         => 'Architecture',
			'project'      => 'casa-nera',
			'line'         => 'New houses, hotels and public buildings, from the first site visit to the last coat of lime.',
			'deliverables' => array(
				'A site study and measured survey',
				'Concept, developed and construction drawings',
				'The permit application',
				'A tender package and help choosing the builder',
				'Weekly site visits until handover',
			),
			'timeline'     => 'Typically 18 to 36 months from the first visit to handover, about half of it on site.',
		),
		'interior-design'   => array(
			'name'         => 'Interior Design',
			'project'      => 'house-of-light',
			'line'       => 'Rooms reworked inside existing buildings, down to the joinery and the light switches.',
			'deliverables' => array(
				'A measured survey of the existing rooms',
				'Layout and furniture plans',
				'Joinery and detail drawings',
				'Schedules for materials, colour and lighting',
				'Site visits throughout the fit-out',
			),
			'timeline'     => 'Typically 8 to 14 months, depending on how much of the building is opened up.',
		),
		'hospitality'       => array(
			'name'         => 'Hospitality',
			'project'      => 'the-quiet-hotel',
			'line'       => 'Hotels and restaurants where the quiet is designed as carefully as the rooms.',
			'deliverables' => array(
				'A study of the guest journey with the operator',
				'A guest room built at full scale before the rest',
				'Interior, lighting and furniture design',
				'Back-of-house planning with the kitchen and housekeeping teams',
				'Styling for opening day',
			),
			'timeline'     => 'Typically 24 to 40 months for a hotel and 10 to 16 months for a restaurant.',
		),
		'workplace'         => array(
			'name'         => 'Workplace',
			'project'      => 'axis-workspace',
			'line'       => 'Offices and studios that make it easier for people to make things together.',
			'deliverables' => array(
				'A space study done with the people who will use it',
				'Test fits and layout options',
				'Acoustic and daylight strategy',
				'Furniture selection and custom pieces',
				'A plan for the move',
			),
			'timeline'     => 'Typically 9 to 18 months, with the move planned around your calendar.',
		),
		'art-direction'     => array(
			'name'         => 'Art Direction',
			'project'      => 'atelier-27',
			'line'       => 'Photography, signage and material palettes that keep a project whole once it opens.',
			'deliverables' => array(
				'A palette of materials, colours and finishes',
				'Signage and wayfinding drawings',
				'A photography brief and a commissioned shoot',
				'Guidelines for the people who look after the place',
			),
			'timeline'     => 'Typically 3 to 6 months, usually alongside a building project.',
		),
		'furniture-objects' => array(
			'name'         => 'Furniture & Objects',
			'project'      => 'plinth-series',
			'line'       => 'Pieces made at the scale of the hand, many from offcuts of our own buildings.',
			'deliverables' => array(
				'Sketches and full-size prototypes',
				'Samples of every material and finish',
				'Production drawings',
				'A small run made with a workshop we know',
			),
			'timeline'     => 'Typically 6 to 12 months from the first sketch to the first finished piece.',
		),
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

	/*
	 * Studio page. The team are the people named on the projects' fact tables; the press names are invented.
	 * `teams` are the three floors of the studio model, top floor first; each title matches the part label of its floor
	 * in the `studio` recipe of data/models.php.
	 */
	'studio_page' => array(
		'statement'  => 'Twenty-three people, one long table.',
		'intro'      => 'Inês Carvalho and Tomás Ribeiro founded FORMA in Lisbon in 2011. Today we design houses, hotels, workplaces and the furniture inside them.',
		'teams'      => array(
			array(
				'title' => 'Architecture',
				'text'  => 'Houses, hotels and public buildings, drawn by hand first and then built as models. A partner leads every project, and one architect stays with it until handover.',
			),
			array(
				'title' => 'Interiors',
				'text'  => 'Rooms inside old buildings and new ones: the plan, the joinery, the lighting and the colour of the plaster. Most of what you touch in a FORMA building was drawn on this floor.',
			),
			array(
				'title' => 'Workshop and objects',
				'text'  => 'The ground floor holds the model shop and the workshop. We test joints and finishes at full size here, and make the furniture.',
			),
		),
		'principles_title' => 'What we hold to',
		'principles' => array(
			array(
				'title' => 'Start on site',
				'text'  => 'Before any drawing we spend time on the land: the slope, the wind, where the sun falls at four in the afternoon. The first sketch is a reply to what we found there.',
			),
			array(
				'title' => 'Use less, and make it last',
				'text'  => 'We choose a few honest materials, such as lime, stone, timber and cork, and detail them to age well. Fewer materials mean fewer joints, less upkeep and a calmer room.',
			),
			array(
				'title' => 'Stay until it is finished',
				'text'  => 'The people who draw a building are on site every week while it is built. We would rather settle a detail with the builder in person than in an email.',
			),
		),
		'numbers'    => array(
			array( 15, 'Years in practice' ),
			array( 64, 'Built works' ),
			array( 11, 'Countries' ),
			array( 23, 'People in the studio' ),
		),
		'team_title' => 'The team',
		'team_intro' => 'These eight lead the studio and its projects. The other fifteen of us are architects, interior designers, model-makers and a carpenter.',
		'team'       => array(
			array( 'Inês Carvalho', 'Founding partner, architect', '2011' ),
			array( 'Tomás Ribeiro', 'Founding partner, architect', '2011' ),
			array( 'Marta Sousa', 'Partner, interiors', '2014' ),
			array( 'Kenji Aoki', 'Associate partner, hospitality', '2016' ),
			array( 'Oskar Lindqvist', 'Associate, workplace and landscape', '2017' ),
			array( 'Lea Brandt', 'Associate, interiors', '2018' ),
			array( 'Sami Haddad', 'Head of objects and fabrication', '2019' ),
			array( 'Beatriz Nunes', 'Senior architect, drawings and detail', '2020' ),
		),
		'approach'   => array(
			'title' => 'How we work',
			'text'  => array(
				'We take on about ten projects at a time across the whole studio, and a partner leads every one of them. One architect stays with a project from the first visit to handover, so the person who promised you the north light is the person who checks the window.',
				'We draw by hand first and build models before we build anything else. The studio has a workshop where we test joints, glazing and finishes at full size, which is why a FORMA detail rarely changes on site.',
				'We build for clients who want to be involved. You will see every drawing before the builder does, and we will tell you plainly when a decision costs more than it gives.',
			),
		),
		'press_title' => 'In the press',
		'press'      => array(
			array( 'Plano Review', 'Eight houses that are better in winter', '2025' ),
			array( 'Atlas of Rooms', 'The Quiet Hotel, Kyoto: a lesson in leaving things out', '2025' ),
			array( 'Site Notes', 'Working with a stone mason on the Isle of Harris', '2024' ),
			array( 'Meridian Design Weekly', 'Studio visit: FORMA in Lisbon', '2024' ),
			array( 'Hearth & Hall Quarterly', 'Atelier 27, a warehouse that learned to be a studio', '2023' ),
			array( 'Cadernos de Obra', 'A rammed-earth house in Mallorca', '2022' ),
		),
	),

	/*
	 * Services page: ways to work with the studio, and the FAQ (also read by the Seo module for the FAQPage schema).
	 */
	'services_page' => array(
		'title' => 'Buildings, rooms and objects.',
		'intro' => 'FORMA designs houses, hotels, workplaces and furniture. We take on a few projects at a time, and a partner leads each one from the first visit to handover.',
		'image' => 'studio-04.jpg',
	),

	'ways'       => array(
		'title' => 'Ways to work with us',
		'items' => array(
			array(
				'name' => 'Full service',
				'text' => 'From the first site visit to the day you move in. One partner leads the project and the same team sees it through. It suits new buildings and major renovations.',
			),
			array(
				'name' => 'Interiors only',
				'text' => 'We design the rooms inside a building that already exists, or one designed by someone else: layout, joinery, lighting and furniture, with site visits until the last piece is in.',
			),
			array(
				'name' => 'Feasibility study',
				'text' => 'A fixed-fee study of four to six weeks to find out what a site or building can become. You get massing options, a cost range and the planning route, which is useful before you buy.',
			),
		),
	),

	'faq'        => array(
		array(
			'q' => 'How are your fees set?',
			'a' => 'We agree the fee after the first stage, once we know the size and difficulty of the work. For a new house it is usually between 9 and 14 percent of the construction cost. Interiors and feasibility studies are quoted as fixed fees.',
		),
		array(
			'q' => 'How many projects do you take on at a time?',
			'a' => 'About ten across the studio. A partner leads each one, and we say no to work we cannot give proper attention.',
		),
		array(
			'q' => 'Do you work outside Portugal?',
			'a' => 'Yes. About half our work is abroad, in 11 countries so far. Where the law requires a local architect of record, we work with one we trust and stay responsible for the design.',
		),
		array(
			'q' => 'Can you work on an existing building?',
			'a' => 'Yes, and many of our best projects are renovations, such as Atelier 27 in Copenhagen and House of Light in Oslo. We start with a measured survey and keep what is worth keeping.',
		),
		array(
			'q' => 'How long does a project take?',
			'a' => 'A house usually takes two to three years from the first visit to handover, and construction is about half of that. The Process page shows the six stages and how long each one tends to take.',
		),
		array(
			'q' => 'How do we get started?',
			'a' => 'Write to us through the Contact page with the site, your brief and your timeline. A partner replies within two working days to arrange a call or a visit.',
		),
	),

	/*
	 * Process page: the six stages. `duration` and `receive` fill the story's meta line, `text` is what happens.
	 */
	'process'    => array(
		'title'  => 'Six stages, one conversation.',
		'intro'  => 'Every project follows the same six stages. How long each takes depends on the size of the building and the speed of the permit office, but you always know where you are and what happens next.',
		'stages' => array(
			array(
				'title'    => 'Discovery',
				'duration' => '3 to 6 weeks',
				'text'     => 'We visit the site, often more than once, and spend time with you and the people who will use the building. We talk about budget, programme and what you want the place to feel like on an ordinary Tuesday.',
				'receive'  => 'a written brief, a measured site survey and a fee proposal for the next stage.',
				'image'    => 'monolith-house-05.jpg',
			),
			array(
				'title'    => 'Concept',
				'duration' => '4 to 8 weeks',
				'text'     => 'We draw the idea at its simplest: a plan, a section and a model. You see two or three directions, and we tell you which one we would build and why.',
				'receive'  => 'concept drawings, a study model and an updated cost estimate.',
				'image'    => 'studio-01.jpg',
			),
			array(
				'title'    => 'Development',
				'duration' => '8 to 16 weeks',
				'text'     => 'The chosen concept becomes a building. We settle structure, materials and services with the engineers, and make samples you can hold.',
				'receive'  => 'developed drawings, a materials palette with samples and a cost plan we have agreed with you.',
				'image'    => 'plinth-series-04.jpg',
			),
			array(
				'title'    => 'Documentation',
				'duration' => '8 to 12 weeks',
				'text'     => 'We prepare the permit application, then draw every junction and write every specification, so a builder can price the work without guessing.',
				'receive'  => 'permit drawings, a full construction set and a tender package.',
				'image'    => 'studio-02.jpg',
			),
			array(
				'title'    => 'Construction',
				'duration' => '12 to 30 months',
				'text'     => 'We are on site every week. We review the builder’s work, answer questions the same day and adjust details when the building asks for it.',
				'receive'  => 'a report after every site visit and a dated record of every change.',
				'image'    => 'atelier-27-01.jpg',
			),
			array(
				'title'    => 'Completion',
				'duration' => '2 to 3 months',
				'text'     => 'We walk the finished building with you, room by room, and fix what is not right. Then we stay through the first season to see how it lives.',
				'receive'  => 'as-built drawings, a maintenance guide for every material and a photographed record of the project.',
				'image'    => 'casa-nera-01.jpg',
			),
		),
	),

	/*
	 * Contact page: the enquiry form's choices and wording.
	 */
	'contact'    => array(
		'title'         => 'Start a conversation.',
		'intro'         => 'Tell us about the site, the brief and the timeline. A partner replies within two working days.',
		'form_title'    => 'Tell us about the project',
		'form_note'     => 'Only your name, email, project type and message are required. The rest helps us reply with something useful.',
		'form_name'     => 'FORMA enquiry',
		'project_types' => array( 'A new house', 'A renovation or interior', 'A hotel or restaurant', 'An office or studio', 'A public or cultural project', 'Furniture or objects', 'Something else' ),
		'budgets'       => array( 'Under €250,000', '€250,000 to €500,000', '€500,000 to €1 million', '€1 million to €3 million', 'Over €3 million', 'Not sure yet' ),
		'consent'       => 'I agree that FORMA may use these details to reply to my enquiry.',
		'success'       => 'Thank you. Your message has reached the studio, and a partner will reply within two working days.',
		'visit_title'   => 'Visit the studio',
		'visit_note'    => 'We are a short walk from the river, on the second floor behind the timber double door. Please write first, so someone is here to meet you.',
	),

	/*
	 * 404 page.
	 */
	'not_found'  => array(
		'title'          => 'This room hasn’t been built yet.',
		'text'           => 'The page you were looking for does not exist, or it has moved.',
		'home'           => 'Back to the homepage',
		'projects_title' => 'Three recent projects instead',
	),

	/*
	 * Colophon: typefaces, privacy and the demo disclosure. Photography credits are generated from the media library.
	 */
	'colophon'   => array(
		'title'      => 'Colophon',
		'intro'      => 'How this site is set and built, who took its photographs, what it does with your data and why it exists.',
		'type'       => array(
			'title'       => 'Typefaces',
			'text'        => array(
				'Everything is set in Archivo, a typeface by Omnibus-Type, in its regular, medium and semibold weights. The wordmark uses Archivo Expanded, the same family at its widest.',
				'The font file is served from this site, not from Google, so a visit makes no request to a font provider.',
			),
			'licence'     => 'Archivo is licensed under the SIL Open Font License 1.1.',
			'licence_url' => 'https://openfontlicense.org',
		),
		'code'       => array(
			'title' => 'Code',
			'text'  => 'The study models and the motion are made with three open libraries, all served from this site.',
			'items' => array(
				array(
					'name'    => 'Three.js',
					'what'    => 'draws the study models in WebGL',
					'licence' => 'MIT licence',
					'url'     => 'https://github.com/mrdoob/three.js/blob/dev/LICENSE',
				),
				array(
					'name'    => 'GSAP',
					'what'    => 'with its ScrollTrigger and SplitText plugins, times the scroll and the text reveals',
					'licence' => 'GSAP Standard “no charge” licence',
					'url'     => 'https://gsap.com/standard-license',
				),
				array(
					'name'    => 'Lenis',
					'what'    => 'smooths the scroll',
					'licence' => 'MIT licence',
					'url'     => 'https://github.com/darkroomengineering/lenis/blob/main/LICENSE',
				),
			),
			'note'  => 'The site itself is WordPress with Elementor, and a small plugin written for it.',
		),
		'credits'    => array(
			'title' => 'Photography',
			'intro' => 'Every photograph on this site is licensed for free use by its photographer on Unsplash or Pexels. They are listed here by the project or page they appear on.',
		),
		'privacy'    => array(
			'title' => 'Privacy',
			'text'  => array(
				'This site sets no cookies of its own, runs no analytics and loads no scripts, fonts, maps or videos from other companies. WordPress sets a cookie only when someone signs in to edit it, and visitors never do.',
				'The contact form sends your message to the studio by email. The site keeps a copy of the enquiry in its own database, so that nothing is lost if an email fails, and nothing else about your visit. Your details are used only to reply to you, and they are deleted if you ask.',
			),
		),
		'about'      => array(
			'title'  => 'Why it exists',
			'before' => 'FORMA is a fictional studio, designed and built by ',
			'name'   => 'Rupash Das',
			'after'  => '.',
			'link'   => 'https://devrupash.com',
			'text'   => 'Its projects, clients, team, press and awards are invented, and the address and phone number are not a real office. The site is a portfolio piece, made with WordPress, Elementor and a small plugin written for it.',
		),
	),
);
