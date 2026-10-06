/**
 * FORMA — model stage.
 *
 * Draws every `.forma-model` figure as a Three.js study model: a list of volumes (boxes, gabled blocks, cylinders,
 * slabs and dashed outlines) on a plinth, in real materials (CC0 PBR sets: lime-washed plaster, board-formed concrete, charred
 * timber, oak, travertine, gravel; bronze and glass), lit by a soft daylight HDRI (image-based light and reflections) and one
 * key light that casts soft shadows. Tall boxes are built as walls with recessed windows in thin metal frames, flat roofs and
 * gabled roofs overhang with a fascia, edges are bevelled, and a recipe can ask for a few trees and scale figures on the free
 * ground of the plinth. Each figure owns one small canvas (at most six per page), is mounted only when it nears the screen
 * and renders on demand. Behaviours come from the figure's `data-forma-model` JSON: assemble, drag, keyboard, scroll orbit,
 * exploded view, growth stages, swapping to another project's model and the Home tour (site, then foam, then built).
 *
 * The tour (behaviour `tour`) is one model telling three steps as the page scrolls through its section, read from the section's
 * cards (`data-tour-step` 1 to 3, the one nearest the middle of the screen is the step): 1 the site, a plinth with contour slabs
 * and trees and the house as a faint dashed ghost; 2 the volumes drop in as plain foam, with dimension lines drawn on two sides
 * of the building, in metres; 3 the foam gives way to the real materials, the callouts show and the model comes gently apart.
 *
 * Textures and the HDRI are fetched lazily, when a model is about to be drawn. Phones draw it cheaper: colour maps only (no
 * normal or roughness maps), the room environment instead of the HDRI, no shadow map, plain glass, and no trees or people.
 *
 * GSAP and ScrollTrigger (classic globals) only drive the scroll behaviours; without them those simply do not run.
 * Without WebGL the figure shows its photograph (class forma-model--fallback).
 */
import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

const root = document.documentElement;
const reducedQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );
const reduced = () => reducedQuery.matches;
const wideQuery = window.matchMedia( '(min-width: 768px)' );
const editing = () => document.body.classList.contains( 'elementor-editor-active' ) || root.classList.contains( 'elementor-html' );

const MAX_VIEWS = 6;
const SVG = 'http://www.w3.org/2000/svg';
const DEG = Math.PI / 180;
const TARGET = new THREE.Vector3( 0, 0.8, 0 );
const TARGET_TOP = new THREE.Vector3( 0, 0, 0 );
const CAMERAS = {
	hero: [ 9, 6.2, 10 ],
	'three-quarter': [ 10, 7.5, 11 ],
	top: [ 0.01, 16, 0.01 ],
	close: [ 6, 4, 7 ],
};

const clamp = ( value, min, max ) => Math.min( max, Math.max( min, value ) );
const lerp = ( from, to, k ) => from + ( to - from ) * k;
const quartOut = ( t ) => 1 - Math.pow( 1 - t, 4 );
const cubicInOut = ( t ) => ( t < 0.5 ? 4 * t * t * t : 1 - Math.pow( -2 * t + 2, 3 ) / 2 );
const round = ( value ) => Math.round( value * 1000 ) / 1000;

/** How much a screen is given: phones draw cheaply, tablets draw it all with a smaller shadow map, desktops with the full one. */
const tierNow = () => ( window.innerWidth < 768 ? 'phone' : window.innerWidth < 1024 ? 'tablet' : 'desktop' );
const SHADOW_SIZE = { desktop: 2048, tablet: 1024, phone: 0 };

/* Light: the tone-mapping exposure, the key light's strength, and how much the sky (the HDRI on tablets and desktops, the room on phones) lights the scene. */
const EXPOSURE = 0.9;
const KEY = 3.1;
const SKY = { hdri: 0.6, room: 0.7 };

/* How long a model that turns by itself keeps turning once nothing has touched it (a pointer, a key, scrolling it into view wakes it). */
const IDLE_SPIN = 7000;

/* The tour: how long a volume takes to drop in, the 1 unit = 4 m of a dimension line, and where a line stands off the building. */
const DROP = 0.9;
const METRES = 4;
const DIM_OFFSET = 0.42;
const DIM_GAP = 0.07;
const DIM_OVER = 0.1;
/* The contour slabs of the site, under the plinth: how many, how much each steps out past the one above it, and how thick. */
const CONTOURS = [ 0, 1, 2, 3 ];
const CONTOUR_STEP = 0.22;
const CONTOUR_THICK = 0.045;

/* Detail. */
const WALL = 0.07; // Thickness of a wall with windows in it.
const ROOF = 0.05; // Thickness of a flat roof slab, which overhangs the walls and reads as the fascia.
const OVERHANG = 0.04;
const WINDOW_MIN_HEIGHT = 0.6;
/* The most trees and people a model plants (see Models::MAX_TREES), and how far from every volume a tree stands. */
const MAX_TREES = 6;
const MAX_PEOPLE = 8;
const TREE_CLEARANCE = 0.24;
/* A tree: a trunk this tall (before the tree's own size), and clustered crowns as [ x, height, z, radius ] from its foot. */
const TRUNK = 0.4;
const CROWNS = [
	[ 0, 0.62, 0, 0.27 ],
	[ 0.17, 0.5, 0.07, 0.19 ],
	[ -0.15, 0.48, -0.09, 0.2 ],
	[ 0.03, 0.8, 0.06, 0.17 ],
];
/* A person, 1.8 m tall at 1 unit to 4 m: a capsule body and a head. */
const PERSON = { radius: 0.05, body: 0.28, head: 0.04 };
const PEOPLE_TONES = [ '#2c2c2e', '#8a7358', '#5f6b75', '#c9c3b6', '#6a4a3a', '#3e4a44' ];

/* ------------------------------------------------------------------ real surfaces and light */

/*
 * Every surface is a CC0 PBR set (colour, normal and roughness at 512px) from ambientCG, and the daylight is a CC0 HDRI from
 * Poly Haven; both ship with the plugin (assets/vendor/textures, assets/vendor/hdri, with their licences). Nothing is fetched
 * until a model is about to be drawn, and phones fetch the colour maps only and no HDRI. A material is made at once in a flat
 * colour of the right average and takes its maps when they arrive; a view waits for them before its first frame.
 */
const VENDOR = new URL( '../vendor/', import.meta.url ).href;
const HDRI = `${ VENDOR }hdri/kloofendal_overcast_puresky_1k.hdr`;

/**
 * How each surface that has a texture is laid out: the average colour it is drawn in until its maps arrive; the colour
 * it is multiplied by (`tint`, times `gain`); how many world units one repeat of the texture covers (the UVs are in world
 * units, so a pattern keeps its size however large a face is); whether the grain runs along the longest side of each face
 * (beams) or is turned a quarter (boards that stand upright); the strength of its normal map and of its roughness.
 */
const LOOKS = {
	foam: { flat: '#d7d3d0', tint: '#fffaf0', gain: 1.02, tile: 1.9, long: false, turn: false, normal: 0.7, rough: 1 },
	shade: { flat: '#686561', tint: '#ffffff', gain: 1.3, tile: 1.3, long: false, turn: false, normal: 1.3, rough: 1 },
	ink: { flat: '#201b0e', tint: '#ffffff', gain: 2.4, tile: 0.8, long: false, turn: true, normal: 2, rough: 0.9 },
	timber: { flat: '#977a50', tint: '#ffe6c4', gain: 1.15, tile: 0.8, long: true, turn: false, normal: 1.2, rough: 1 },
	stone: { flat: '#a18d77', tint: '#fff2de', gain: 1.15, tile: 1.7, long: false, turn: false, normal: 1, rough: 1 },
	ground: { flat: '#ccbe9f', tint: '#dedad0', gain: 0.98, tile: 1.3, long: false, turn: false, normal: 1.4, rough: 1 },
};

const rngOf = ( seed ) => {
	let a = seed >>> 0;

	return () => {
		a = ( a + 0x6d2b79f5 ) | 0;
		let t = Math.imul( a ^ ( a >>> 15 ), 1 | a );
		t = ( t + Math.imul( t ^ ( t >>> 7 ), 61 | t ) ) ^ t;

		return ( ( t ^ ( t >>> 14 ) ) >>> 0 ) / 4294967296;
	};
};

const canvasOf = ( size ) => {
	const canvas = document.createElement( 'canvas' );
	canvas.width = canvas.height = size;

	return canvas;
};

const textureLoader = new THREE.TextureLoader();

/** One texture file, resolved (to null when it is missing) once it has loaded; repeating, and read as colour or as data. */
const loadTexture = ( file, srgb ) =>
	new Promise( ( resolve ) => {
		const tex = textureLoader.load( `${ VENDOR }textures/${ file }`, () => resolve( tex ), undefined, () => resolve( null ) );

		tex.colorSpace = srgb ? THREE.SRGBColorSpace : THREE.NoColorSpace;
		tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
		tex.anisotropy = 8;
	} );

/* ------------------------------------------------------------------ shared geometry, materials, textures */

const cache = { geometry: new Map(), parts: new Map(), material: new Map(), textures: new Map(), blank: null, contact: null };

/** The maps of a surface, fetched once: { map, normalMap, roughnessMap, ready } (the last two only on tablets and desktops). */
const surfaces = ( name, tier ) => {
	const full = tier !== 'phone';
	const key = `${ name }:${ full ? 'full' : 'lite' }`;
	let set = cache.textures.get( key );

	if ( ! set ) {
		set = { map: null, normalMap: null, roughnessMap: null };
		set.ready = Promise.all( [ loadTexture( `${ name }-color.jpg`, true ), full ? loadTexture( `${ name }-normal.jpg`, false ) : null, full ? loadTexture( `${ name }-rough.jpg`, false ) : null ] ).then( ( [ map, normalMap, roughnessMap ] ) =>
			Object.assign( set, { map, normalMap, roughnessMap } )
		);
		cache.textures.set( key, set );
	}

	return set;
};

/** A material for a surface with a texture: flat in its average colour now, and in its maps once they have arrived. */
const dress = ( mat, name, tier ) => {
	const look = LOOKS[ name ];
	const set = surfaces( name, tier );

	mat.color.set( look.flat );
	mat.roughness = 0.85;
	mat.userData.ready = set.ready.then( () => {
		if ( set.map ) {
			mat.map = set.map;
			mat.color.set( look.tint ).multiplyScalar( look.gain );
			mat.normalMap = set.normalMap;
			mat.normalScale.setScalar( look.normal );
			mat.roughnessMap = set.roughnessMap;
			mat.roughness = look.rough;
		}
	} );
};

/** Materials that a tour model shows as plain foam until it is built (see View.tourStep): their colour, roughness and metal are mixed with foam by one uniform. */
const TOUR_FADES = new Set( [ ...Object.keys( LOOKS ), 'metal', 'interior', 'frame', 'pane', 'glass' ] );

const toFoam = ( mat, real ) => {
	mat.onBeforeCompile = ( shader ) => {
		shader.uniforms.uReal = real;
		shader.fragmentShader = `uniform float uReal;\n${ shader.fragmentShader }`
			.replace( '#include <map_fragment>', '#include <map_fragment>\n\tdiffuseColor.rgb = mix( vec3( 0.82, 0.805, 0.77 ), diffuseColor.rgb, uReal );' )
			.replace( '#include <roughnessmap_fragment>', '#include <roughnessmap_fragment>\n\troughnessFactor = mix( 0.94, roughnessFactor, uReal );' )
			.replace( '#include <metalnessmap_fragment>', '#include <metalnessmap_fragment>\n\tmetalnessFactor *= uReal;' );
	};
	mat.customProgramCacheKey = () => 'forma-tour';
};

/** The greens of foliage, from deep to light: each crown of a tree takes one of them, so a tree is not one flat ball. */
const LEAVES = [ '#2d4a27', '#385a2c', '#44683a', '#32502a', '#4f6e3d' ];

/** One-texel maps that stand in for a texture a material does not have (white colour, flat normal, white roughness). */
const blanks = () => {
	if ( ! cache.blank ) {
		const texel = ( rgba, srgb ) => {
			const tex = new THREE.DataTexture( new Uint8Array( rgba ), 1, 1 );

			tex.colorSpace = srgb ? THREE.SRGBColorSpace : THREE.NoColorSpace;
			tex.needsUpdate = true;

			return tex;
		};

		cache.blank = { map: texel( [ 255, 255, 255, 255 ], true ), normalMap: texel( [ 128, 128, 255, 255 ], false ), roughnessMap: texel( [ 255, 255, 255, 255 ], false ) };
	}

	return cache.blank;
};

/**
 * An ordinary opaque or glassy material. Every one of them carries the same maps (blank ones where it has no texture of its
 * own), so that all of them are drawn by a single shader program: each extra program is a stall the first time it draws.
 */
const standard = ( params, tier ) => {
	const mat = new THREE.MeshStandardMaterial( { metalness: 0, ...params } );
	const blank = blanks();

	mat.map = blank.map;

	if ( tier !== 'phone' ) {
		mat.normalMap = blank.normalMap;
		mat.roughnessMap = blank.roughnessMap;
	}

	return mat;
};

const plainMaterial = ( name, tier ) => {
	const full = tier !== 'phone';

	if ( LOOKS[ name ] ) {
		const mat = standard( {}, tier );

		dress( mat, name, tier );

		return mat;
	}

	if ( name.startsWith( 'leaf:' ) ) {
		return standard( { color: LEAVES[ Number( name.slice( 5 ) ) % LEAVES.length ], roughness: 0.92 }, tier );
	}

	if ( name.startsWith( 'person:' ) ) {
		return standard( { color: name.slice( 7 ), roughness: 0.8 }, tier );
	}

	switch ( name ) {
		case 'metal':
			return standard( full ? { color: '#4d3e2f', roughness: 0.34, metalness: 0.85 } : { color: '#5a4a3a', roughness: 0.55, metalness: 0.3 }, tier );
		case 'leaf':
			// Moss and lawn: a flat green.
			return standard( { color: '#4a5f3a', roughness: 0.95 }, tier );
		case 'water':
			return standard( full ? { color: '#1d3a45', roughness: 0.05, metalness: 0.3 } : { color: '#27424c', roughness: 0.15, metalness: 0.2 }, tier );
		case 'glass':
			// A clear volume of glass that reflects the sky. It blends rather than transmits: a transmissive volume over the page's
			// empty canvas would haze white. (The one physical material; only a model with a volume of glass in it draws it.)
			return full
				? new THREE.MeshPhysicalMaterial( { color: '#b9d0d4', transparent: true, opacity: 0.34, depthWrite: false, roughness: 0.03, metalness: 0.15, ior: 1.5 } )
				: standard( { color: '#dfe9ea', transparent: true, opacity: 0.32, depthWrite: false, roughness: 0.3 }, tier );
		case 'pane':
			// The glazing of a window: dark, glossy glass that mirrors the sky, over the dark inside of the building.
			return standard( full ? { color: '#3a5262', roughness: 0.03, metalness: 0.78 } : { color: '#1f2830', roughness: 0.2, metalness: 0.5 }, tier );
		case 'interior':
			return standard( { color: '#14161a', roughness: 0.9 }, tier );
		case 'frame':
			return standard( { color: '#1b1a19', roughness: 0.4, metalness: 0.75 }, tier );
		case 'trunk':
			return standard( { color: '#46372a', roughness: 0.9 }, tier );
		case 'skin':
			return standard( { color: '#d6c1ad', roughness: 0.8 }, tier );
		case 'wire':
			return new THREE.LineDashedMaterial( { color: '#2B3BFF', dashSize: 0.045, gapSize: 0.03 } );
		case 'shadow':
			return new THREE.ShadowMaterial( { opacity: 0.22 } );
		default:
			return null;
	}
};

/**
 * A material by name. Shared by every view, except for a tour view (one that shows its model as foam, then built), which keeps
 * its own so that its mix with foam is its own. A view is told which materials it uses so that it can wait for their maps.
 */
const material = ( name, tier = 'desktop', view = null ) => {
	const own = view?.tour ? view.mats : cache.material;
	const key = `${ name }:${ tier }`;
	let mat = own.get( key );

	if ( ! mat ) {
		mat = plainMaterial( name, tier );

		if ( ! mat ) {
			return material( 'foam', tier, view );
		}

		if ( view?.tour && TOUR_FADES.has( name ) ) {
			toFoam( mat, view.tour.real );
			mat.userData.look = LOOKS[ name ] || null;
		}

		own.set( key, mat );
	}

	view?.used.add( mat );

	return mat;
};

/** A soft rectangular contact shadow, baked once: the blurred footprint of the plinth, drawn on a plane just above the ground. */
const contactShadow = () => {
	if ( ! cache.contact ) {
		const canvas = canvasOf( 256 );
		const ctx = canvas.getContext( '2d' );

		// The rectangle is drawn far off the canvas and only its blurred shadow lands on it.
		ctx.shadowColor = 'rgba(0,0,0,0.55)';
		ctx.shadowBlur = 26;
		ctx.shadowOffsetX = 2000;
		ctx.fillStyle = '#000';
		ctx.fillRect( 32 - 2000, 32, 192, 192 );

		cache.contact = new THREE.CanvasTexture( canvas );
		cache.contact.colorSpace = THREE.SRGBColorSpace;
	}

	return cache.contact;
};

/**
 * Resolves once the GPU has finished everything queued so far, without making the page wait for it: a fence that is polled
 * (a call that asked the GPU for an answer, a program's uniforms say, would block the page for as long as the GPU is busy).
 * Where the GPU is software and compiling shaders, that is a second or more; on a real GPU it is the next frame.
 */
const gpuIdle = ( gl ) =>
	new Promise( ( resolve ) => {
		if ( typeof gl.fenceSync !== 'function' ) {
			resolve();

			return;
		}

		const fence = gl.fenceSync( gl.SYNC_GPU_COMMANDS_COMPLETE, 0 );

		gl.flush();

		const poll = () => {
			const status = gl.clientWaitSync( fence, 0, 0 );

			if ( status === gl.TIMEOUT_EXPIRED ) {
				window.setTimeout( poll, 16 );

				return;
			}

			gl.deleteSync( fence );
			resolve();
		};

		poll();
	} );

let roomModule = null;
let hdriModule = null;

/** The room environment (the soft image-based light phones use, and the fallback if the HDRI cannot load), fetched once. */
const loadRoom = () => roomModule || ( roomModule = import( 'three/addons/environments/RoomEnvironment.js' ).then( ( module ) => module.RoomEnvironment, () => null ) );

/** The daylight HDRI (an overcast sky), decoded once and shared by every view: an equirectangular texture, or null if it cannot be had. */
const loadHdri = () =>
	hdriModule ||
	( hdriModule = import( 'three/addons/loaders/HDRLoader.js' )
		.then( ( { HDRLoader } ) => new HDRLoader().loadAsync( HDRI ) )
		.then( ( tex ) => {
			tex.mapping = THREE.EquirectangularReflectionMapping;

			return tex;
		} )
		.catch( () => null ) );

/* ---- geometry: unit volumes with real bevels, walls with recessed windows, roofs ---- */

/**
 * A volume is drawn at its real size (so a bevel is the same width on every edge, a window the same size on every wall) and
 * then divided back down to a unit cube, because the runtime places, scales and morphs every volume by scaling a unit
 * body. Normals are carried across so the shading still agrees with the real shape.
 */
const unitize = ( geo, w, h, d ) => {
	const pos = geo.attributes.position;
	const nor = geo.attributes.normal;

	for ( let i = 0; i < pos.count; i++ ) {
		pos.setXYZ( i, pos.getX( i ) / w, pos.getY( i ) / h, pos.getZ( i ) / d );

		if ( nor ) {
			const x = nor.getX( i ) * w;
			const y = nor.getY( i ) * h;
			const z = nor.getZ( i ) * d;
			const len = Math.hypot( x, y, z ) || 1;

			nor.setXYZ( i, x / len, y / len, z / len );
		}
	}

	pos.needsUpdate = true;

	if ( nor ) {
		nor.needsUpdate = true;
	}

	geo.computeBoundingBox();
	geo.computeBoundingSphere();

	return geo;
};

/** Join non-indexed geometries into one, keeping the attributes they all have (position, normal and, if every one has it, uv and colour). */
const merge = ( geos ) => {
	const list = geos.filter( Boolean ).map( ( geo ) => ( geo.index ? geo.toNonIndexed() : geo ) );
	const merged = new THREE.BufferGeometry();

	if ( ! list.length ) {
		return null;
	}

	[ 'position', 'normal', 'uv', 'color' ].forEach( ( name ) => {
		if ( list.every( ( geo ) => geo.attributes[ name ] ) ) {
			const size = list[ 0 ].attributes[ name ].itemSize;
			const array = new Float32Array( list.reduce( ( sum, geo ) => sum + geo.attributes[ name ].array.length, 0 ) );
			let offset = 0;

			list.forEach( ( geo ) => {
				array.set( geo.attributes[ name ].array, offset );
				offset += geo.attributes[ name ].array.length;
			} );
			merged.setAttribute( name, new THREE.BufferAttribute( array, size ) );
		}
	} );

	return merged;
};

/** A plain box, non-indexed, centred at (x, y, z). */
const slab = ( w, h, d, x, y, z ) => new THREE.BoxGeometry( w, h, d ).translate( x, y, z );

/** Texture coordinates in world units: the pattern keeps its size however large the box is. */
const boxUv = ( geo, w, h, d, look, shiftBy = 0 ) => {
	const pos = geo.attributes.position;
	const nor = geo.attributes.normal;
	const uv = new Float32Array( pos.count * 2 );
	const shift = ( w * 7.13 + h * 3.77 + d * 5.19 + shiftBy ) % 1;

	for ( let i = 0; i < pos.count; i++ ) {
		const nx = Math.abs( nor.getX( i ) );
		const ny = Math.abs( nor.getY( i ) );
		const nz = Math.abs( nor.getZ( i ) );
		let a;
		let b;
		let spanA;
		let spanB;

		if ( ny >= nx && ny >= nz ) {
			a = pos.getX( i );
			b = pos.getZ( i );
			spanA = w;
			spanB = d;
		} else if ( nx >= nz ) {
			a = pos.getZ( i );
			b = pos.getY( i );
			spanA = d;
			spanB = h;
		} else {
			a = pos.getX( i );
			b = pos.getY( i );
			spanA = w;
			spanB = h;
		}

		if ( look.turn || ( look.long && spanB > spanA ) ) {
			[ a, b ] = [ b, a ];
		}

		uv[ i * 2 ] = a / look.tile + shift;
		uv[ i * 2 + 1 ] = b / look.tile + shift;
	}

	geo.setAttribute( 'uv', new THREE.BufferAttribute( uv, 2 ) );
};

/** Scale the texture coordinates an extrusion made, which are in world units already. */
const scaleUv = ( geo, look, shift = 0 ) => {
	const uv = geo.attributes.uv;

	for ( let i = 0; i < uv.count; i++ ) {
		const a = look.turn ? uv.getY( i ) : uv.getX( i );
		const b = look.turn ? uv.getX( i ) : uv.getY( i );

		uv.setXY( i, a / look.tile + shift, b / look.tile + shift );
	}

	uv.needsUpdate = true;
};

const boxGeometry = ( w, h, d, look, tier ) => {
	const least = Math.min( w, h, d );
	// About 2% of the smallest side; thin plates keep a hairline, and no bevel eats more than 40% of a side.
	const radius = Math.min( clamp( 0.02 * least, 0.004, 0.035 ), 0.4 * least );
	const geo = new RoundedBoxGeometry( w, h, d, tier === 'phone' ? 1 : 2, radius );

	if ( look ) {
		boxUv( geo, w, h, d, look );
	}

	return unitize( geo, w, h, d );
};

const cylinderGeometry = ( w, h, look, tier ) => {
	const geo = new THREE.CylinderGeometry( w / 2, w / 2, h, tier === 'phone' ? 24 : 40 );

	if ( look ) {
		const pos = geo.attributes.position;
		const nor = geo.attributes.normal;
		const uv = geo.attributes.uv;
		const girth = Math.PI * w;

		for ( let i = 0; i < uv.count; i++ ) {
			if ( Math.abs( nor.getY( i ) ) > 0.9 ) {
				uv.setXY( i, pos.getX( i ) / look.tile, pos.getZ( i ) / look.tile );
			} else {
				const around = ( uv.getX( i ) * girth ) / look.tile;
				const up = ( pos.getY( i ) + h / 2 ) / look.tile;

				look.turn ? uv.setXY( i, up, around ) : uv.setXY( i, around, up );
			}
		}

		uv.needsUpdate = true;
	}

	return unitize( geo, w, h, w );
};

/**
 * The windows of a wall `span` wide and `height` tall, as openings [ { x, y, w, h } ] with x from the wall's middle and y
 * from its foot: a row per storey (about 0.78 tall), openings about 0.78 wide between piers about 0.2 wide, all
 * sized from the wall.
 */
const layoutWall = ( span, height ) => {
	const floors = clamp( Math.round( height / 0.78 ), 1, 4 );
	const storey = height / floors;
	const margin = clamp( span * 0.1, 0.11, 0.3 );
	const usable = span - 2 * margin;
	const out = [];

	if ( usable < 0.3 ) {
		return out;
	}

	const pier = 0.2;
	const count = Math.max( 1, Math.floor( ( usable + pier ) / ( 0.78 + pier ) ) );
	const width = ( usable - ( count - 1 ) * pier ) / count;
	const tall = clamp( storey * 0.46, 0.16, 0.42 );

	for ( let floor = 0; floor < floors; floor++ ) {
		const y = floor * storey + storey * 0.2;

		if ( y + tall > height - 0.06 ) {
			continue;
		}

		for ( let i = 0; i < count; i++ ) {
			out.push( { x: -usable / 2 + width / 2 + i * ( width + pier ), y, w: width, h: tall } );
		}
	}

	return out;
};

/** A wall panel: a rectangle `span` x `height`, `thick` deep (z from 0 to thick), with the openings cut through it. */
const panelGeometry = ( span, height, thick, openings ) => {
	const shape = new THREE.Shape();

	shape.moveTo( -span / 2, 0 );
	shape.lineTo( span / 2, 0 );
	shape.lineTo( span / 2, height );
	shape.lineTo( -span / 2, height );
	shape.closePath();

	openings.forEach( ( { x, y, w, h } ) => {
		const hole = new THREE.Path();

		hole.moveTo( x - w / 2, y );
		hole.lineTo( x - w / 2, y + h );
		hole.lineTo( x + w / 2, y + h );
		hole.lineTo( x + w / 2, y );
		hole.closePath();
		shape.holes.push( hole );
	} );

	return new THREE.ExtrudeGeometry( shape, { depth: thick, bevelEnabled: false, curveSegments: 1 } );
};

/**
 * The thin dark metal frame of an opening (jambs, head, a sill that sticks out a little and a mullion on a wide one) and its
 * glass, in the wall's own space: x along the wall, y up, the wall's outer face at z = face. `inset` is how far the frame
 * stands back from the face (0 for a window laid on the surface).
 */
const windowParts = ( { x, y, w, h }, face, inset, deep ) => {
	const t = 0.014;
	const cy = y + h / 2;
	const zc = face - inset - deep / 2;
	const bars = [
		slab( t, h, deep, x - w / 2 + t / 2, cy, zc ),
		slab( t, h, deep, x + w / 2 - t / 2, cy, zc ),
		slab( w, t, deep, x, y + h - t / 2, zc ),
		slab( w + 0.03, t, deep + 0.025, x, y + t / 2 - 0.004, zc + 0.0125 ),
	];

	if ( w > 0.55 ) {
		bars.push( slab( t * 0.8, h, deep * 0.8, x, cy, zc ) );
	}

	const glass = new THREE.PlaneGeometry( w - 2 * t, h - 2 * t ).translate( x, cy, face - inset - deep * 0.55 );

	return { bars, glass };
};

/** Move a part from a wall's own space onto a box: turned about the vertical to face outward, set at the box's foot. */
const onWall = ( geo, theta, depth, thick, h ) => geo.translate( 0, -h / 2, depth / 2 - thick ).rotateY( theta );

/**
 * A box with windows, built as a building: four walls with the windows cut through them, thin dark frames in each
 * opening and glass set back inside, a dark core behind the glass, and a flat roof slab that overhangs and reads as the
 * fascia. Every part is unitized so the runtime can scale it like any other volume.
 */
const buildingParts = ( w, h, d, look, tier ) => {
	const thick = Math.min( WALL, 0.3 * Math.min( w, d ) );
	const roof = Math.min( ROOF, 0.12 * h );
	const wall = h - roof;
	const panels = [];
	const frames = [];
	const panes = [];
	const walls = [
		{ theta: 0, span: w, depth: d },
		{ theta: Math.PI, span: w, depth: d },
		{ theta: Math.PI / 2, span: d - 2 * thick, depth: w },
		{ theta: -Math.PI / 2, span: d - 2 * thick, depth: w },
	];

	walls.forEach( ( { theta, span, depth }, index ) => {
		const openings = layoutWall( span, wall );
		const panel = panelGeometry( span, wall, thick, openings );

		scaleUv( panel, look, index * 0.23 );
		panels.push( onWall( panel, theta, depth, thick, h ) );

		openings.forEach( ( opening ) => {
			const part = windowParts( opening, thick, 0.005, 0.045 );

			frames.push( ...part.bars.map( ( bar ) => onWall( bar, theta, depth, thick, h ) ) );
			panes.push( onWall( part.glass, theta, depth, thick, h ) );
		} );
	} );

	const cap = new RoundedBoxGeometry( w + 2 * OVERHANG, roof, d + 2 * OVERHANG, tier === 'phone' ? 1 : 2, Math.min( 0.012, 0.4 * roof ) );

	boxUv( cap, w + 2 * OVERHANG, roof, d + 2 * OVERHANG, look, 0.37 );
	cap.translate( 0, h / 2 - roof / 2, 0 );

	const core = slab( w - 2 * thick + 0.004, wall, d - 2 * thick + 0.004, 0, -h / 2 + wall / 2, 0 );

	core.deleteAttribute( 'uv' );

	const make = ( geo ) => ( geo ? unitize( geo, w, h, d ) : null );
	const framed = merge( frames );

	framed?.deleteAttribute( 'uv' );

	return { walls: make( merge( panels ) ), cap: make( cap ), core: make( core ), frames: make( framed ), glass: make( merge( panes ) ) };
};

/** A door leaf standing in a thin box: a frame set round its largest face, a panel line and a handle. */
const doorParts = ( w, h, d ) => {
	const t = 0.016;
	const lift = 0.008;
	const bars = [];
	const alongZ = d < w; // The leaf's face looks along Z when the box is thinner in Z.
	const span = alongZ ? w : d;
	const face = ( alongZ ? d : w ) / 2;
	const at = ( s, y, z, sw, sh, sd ) => ( alongZ ? slab( sw, sh, sd, s, y, z ) : slab( sd, sh, sw, z, y, s ) );

	[ 1, -1 ].forEach( ( side ) => {
		const z = side * ( face + lift / 2 );

		bars.push( at( -span / 2 + t / 2, 0, z, t, h, lift ), at( span / 2 - t / 2, 0, z, t, h, lift ), at( 0, h / 2 - t / 2, z, span, t, lift ), at( 0, 0.02, z, t * 0.7, h - 0.06, lift ), at( span * 0.3, -h * 0.02, z + side * lift * 1.2, 0.012, 0.09, lift * 2.4 ) );
	} );

	return unitize( merge( bars ), w, h, d );
};

/**
 * A gabled block in two parts. The walls are the house profile (walls to 62% of the ridge, then the pitch). The roof is a
 * thin pitched slab laid over it that overhangs the eaves and the gable ends, with a deeper lip at the eave for the
 * fascia. Glass gets no roof slab: it stays one clear volume.
 */
const gableGeometry = ( w, h, d, look, roof ) => {
	const eave = 0.62 * h;
	const slope = ( h - eave ) / ( w / 2 );
	const t = Math.min( 0.04, 0.12 * h );
	const o = Math.min( 0.09, 0.04 * Math.max( w, d ) );
	const shape = new THREE.Shape();
	let depth = d;
	let lift = 0;

	if ( ! roof ) {
		// The whole house, one profile.
		shape.moveTo( -w / 2, 0 );
		shape.lineTo( w / 2, 0 );
		shape.lineTo( w / 2, eave );
		shape.lineTo( 0, h );
		shape.lineTo( -w / 2, eave );
	} else if ( roof === 'walls' ) {
		// Walls stop a roof thickness short of the pitch, where the slab takes over.
		shape.moveTo( -w / 2, 0 );
		shape.lineTo( w / 2, 0 );
		shape.lineTo( w / 2, eave - t );
		shape.lineTo( 0, h - t );
		shape.lineTo( -w / 2, eave - t );
	} else {
		const x = w / 2 + o;
		const yEave = h - slope * x;
		const fascia = t + 0.045;
		const lip = 0.018;
		const under = yEave - t + slope * lip;

		shape.moveTo( -x, yEave - fascia );
		shape.lineTo( -x + lip, yEave - fascia );
		shape.lineTo( -x + lip, under );
		shape.lineTo( 0, h - t );
		shape.lineTo( x - lip, under );
		shape.lineTo( x - lip, yEave - fascia );
		shape.lineTo( x, yEave - fascia );
		shape.lineTo( x, yEave );
		shape.lineTo( 0, h );
		shape.lineTo( -x, yEave );
		depth = d + 2 * o;
		lift = o;
	}

	shape.closePath();

	const geo = new THREE.ExtrudeGeometry( shape, { depth, bevelEnabled: false, curveSegments: 1 } );

	geo.translate( 0, -h / 2, -d / 2 - lift );

	if ( look ) {
		scaleUv( geo, look );
	} else {
		geo.deleteAttribute( 'uv' );
	}

	return unitize( geo, w, h, d );
};

/**
 * Windows laid on the long walls and the gable ends of a gabled block, below its eaves: the same frames and glass as a
 * building's, but standing proud of the wall (a block with a pitched roof is one extrusion and cannot be cut).
 */
const gableWindows = ( w, h, d ) => {
	const wall = 0.62 * h;
	const frames = [];
	const panes = [];
	const faces = [
		{ theta: 0, span: w, depth: d },
		{ theta: Math.PI, span: w, depth: d },
		{ theta: Math.PI / 2, span: d, depth: w },
		{ theta: -Math.PI / 2, span: d, depth: w },
	];

	faces.forEach( ( { theta, span, depth } ) => {
		layoutWall( span, wall - 0.04 ).forEach( ( opening ) => {
			const part = windowParts( opening, 0.02, 0, 0.02 );

			frames.push( ...part.bars.map( ( bar ) => onWall( bar, theta, depth, 0, h ) ) );
			panes.push( onWall( part.glass, theta, depth, 0, h ) );
		} );
	} );

	const framed = merge( frames );

	framed?.deleteAttribute( 'uv' );

	return { frames: framed && unitize( framed, w, h, d ), glass: panes.length ? unitize( merge( panes ), w, h, d ) : null };
};

/** A cached geometry for a volume's kind, size and material (the sizes are part of the key: a bevel and a texture depend on them). */
const sized = ( kind, v, tier, part = '' ) => {
	const w = round( v.w );
	const h = round( v.h );
	const d = kind === 'cylinder' ? w : round( v.d );
	const look = LOOKS[ v.material ] || null;
	const key = `${ kind }${ part }:${ w }:${ h }:${ d }:${ look ? v.material : '' }:${ tier }`;
	let geo = cache.geometry.get( key );

	if ( geo === undefined ) {
		if ( kind === 'cylinder' ) {
			geo = cylinderGeometry( w, h, look, tier );
		} else if ( kind === 'gable' ) {
			geo = gableGeometry( w, h, d, look, part );
		} else {
			geo = boxGeometry( w, h, d, look, tier );
		}

		cache.geometry.set( key, geo );
	}

	return geo;
};

/** The parts of a volume built from several (a building, a door, a gable's windows), made once per size and material. */
const partsOf = ( kind, v, tier ) => {
	const w = round( v.w );
	const h = round( v.h );
	const d = round( v.d );
	const key = `${ kind }:${ w }:${ h }:${ d }:${ v.material }:${ tier }`;
	let parts = cache.parts.get( key );

	if ( ! parts ) {
		const look = LOOKS[ v.material ] || null;

		parts = kind === 'building' ? buildingParts( w, h, d, look, tier ) : kind === 'door' ? { frames: doorParts( w, h, d ) } : gableWindows( w, h, d );
		cache.parts.set( key, parts );
	}

	return parts;
};

/** The balls of a unit tree crown, as [ x, y, z, radius ] in a cube (footprint 1 by 1, height 1), the first the big one in the middle. */
const CANOPY = [
	[ 0, 0.5, 0, 0.5 ],
	[ 0.2, 0.36, 0.14, 0.3 ],
	[ -0.2, 0.38, -0.12, 0.32 ],
	[ 0.06, 0.74, -0.06, 0.28 ],
	[ -0.14, 0.3, 0.2, 0.26 ],
];

const geometry = ( kind ) => {
	let geo = cache.geometry.get( kind );

	if ( geo ) {
		return geo;
	}

	if ( kind === 'wire' ) {
		geo = new THREE.EdgesGeometry( new THREE.BoxGeometry( 1, 1, 1 ) );
		new THREE.LineSegments( geo, new THREE.LineBasicMaterial() ).computeLineDistances();
	} else if ( kind === 'plane' ) {
		geo = new THREE.PlaneGeometry( 1, 1 );
	} else if ( kind === 'trunk' ) {
		geo = new THREE.CylinderGeometry( 0.032, 0.05, 1, 7 );
		geo.translate( 0, 0.5, 0 );
	} else if ( kind === 'crown' ) {
		geo = new THREE.SphereGeometry( 1, 14, 10 );
	} else if ( kind === 'body' ) {
		geo = new THREE.CapsuleGeometry( PERSON.radius, PERSON.body, 4, 10 );
		geo.translate( 0, PERSON.radius + PERSON.body / 2, 0 );
	} else if ( kind === 'head' ) {
		geo = new THREE.SphereGeometry( PERSON.head, 10, 8 );
		geo.translate( 0, PERSON.radius * 2 + PERSON.body + PERSON.head * 0.7, 0 );
	}

	cache.geometry.set( kind, geo );

	return geo;
};

/** The edges of a box at its real size, dashed to a length that does not depend on the box, then divided down to a unit box. */
const ghostGeometry = ( w, h, d ) => {
	const key = `ghost:${ round( w ) }:${ round( h ) }:${ round( d ) }`;
	let geo = cache.geometry.get( key );

	if ( ! geo ) {
		geo = new THREE.EdgesGeometry( new THREE.BoxGeometry( w, h, d ) );
		new THREE.LineSegments( geo ).computeLineDistances();
		unitize( geo, w, h, d );
		cache.geometry.set( key, geo );
	}

	return geo;
};

/** Whether a box becomes a building with windows: taller than 0.6, not a pier or a thin wall, in a material a window belongs in. */
const hasWindows = ( v ) =>
	( v.kind === 'box' || v.kind === 'gable' ) &&
	v.windows !== false &&
	( v.kind === 'gable' ? 0.62 * v.h : v.h ) > WINDOW_MIN_HEIGHT &&
	Math.min( v.w, v.d ) >= 0.9 &&
	! [ 'glass', 'wire', 'leaf', 'metal' ].includes( v.material );

/** A small box named for an entrance is a door (or a door in a little porch): its front face gets a frame, a panel line and a handle. */
const isDoor = ( v ) => v.kind === 'box' && /entrance|door/i.test( v.part || '' ) && Math.min( v.w, v.d ) < 0.4 && Math.max( v.w, v.d ) <= 1.4 && v.h > 0.3 && v.h <= 0.8;

const makeBody = ( slot ) => {
	if ( slot.wire ) {
		const line = new THREE.LineSegments( geometry( 'wire' ), material( 'wire', slot.tier, slot.view ) );
		line.frustumCulled = false;

		return line;
	}

	const { v, tier, view } = slot;
	const kind = v.kind === 'slab' ? 'box' : v.kind;
	const solid = v.material !== 'glass';
	const mat = material( v.material, tier, view );
	const body = new THREE.Group();
	const add = ( geo, m = mat, shadows = solid ) => {
		if ( geo ) {
			const mesh = new THREE.Mesh( geo, m );

			mesh.castShadow = shadows;
			mesh.receiveShadow = shadows;
			body.add( mesh );
		}
	};

	if ( kind === 'box' && hasWindows( v ) ) {
		const parts = partsOf( 'building', v, tier );

		add( parts.walls );
		add( parts.cap );
		add( parts.core, material( 'interior', tier, view ) );
		add( parts.frames, material( 'frame', tier, view ), false );
		add( parts.glass, material( 'pane', tier, view ), false );
	} else if ( kind === 'gable' && solid ) {
		add( sized( 'gable', v, tier, 'walls' ) );
		add( sized( 'gable', v, tier, 'roof' ) );

		if ( hasWindows( v ) ) {
			const parts = partsOf( 'gable', v, tier );

			add( parts.frames, material( 'frame', tier, view ), false );
			add( parts.glass, material( 'pane', tier, view ), false );
		}
	} else if ( kind === 'cylinder' && v.material === 'leaf' && v.h >= 0.18 ) {
		// A leaf cylinder that is not a flat patch of moss is a tree.
		CANOPY.forEach( ( [ x, y, z, r ], i ) => {
			const ball = new THREE.Mesh( geometry( 'crown' ), material( `leaf:${ i }`, tier, view ) );

			ball.position.set( x, y - 0.5, z );
			ball.scale.setScalar( r );
			ball.castShadow = ball.receiveShadow = true;
			body.add( ball );
		} );
	} else {
		add( sized( kind, v, tier ) );

		if ( isDoor( v ) ) {
			add( partsOf( 'door', v, tier ).frames, material( 'frame', tier, view ), false );
		}
	}

	return body;
};

/** The logical transform of a volume: its centre, rotation and scale. */
const baseOf = ( v ) => ( { x: v.x, y: v.y + v.h / 2, z: v.z, ry: v.rot * DEG, sx: v.w, sy: v.h, sz: v.d } );

/* ------------------------------------------------------------------ one volume of a model */

class Slot {
	constructor( v, view ) {
		this.group = new THREE.Group();
		this.body = null;
		this.key = '';
		this.view = view;
		this.tier = view.tier;
		this.base = baseOf( v );
		this.dir = new THREE.Vector3( 0, 1, 0 );
		this.drop = 0;
		this.delay = 0;
		this.present = 1;
		this.revealed = true;
		this.breath = false;
		this.label = null;
		this.labelSize = null;
		this.line = null;
		this.dot = null;
		this.set( v );
	}

	/** Take on a volume's kind, material, size, label and stage, swapping the body when the look changes. */
	set( v ) {
		this.v = v;
		this.part = v.part;
		this.stage = v.stage;
		this.ground = v.kind === 'slab' && v.y < 0.02 && ! v.part && ! v.stage;
		this.wire = v.kind === 'wire' || v.material === 'wire';

		const key = this.wire ? 'wire' : `${ v.kind === 'slab' ? 'box' : v.kind }:${ v.material }:${ round( v.w ) }:${ round( v.h ) }:${ round( v.d ) }:${ v.windows === false ? 0 : 1 }:${ v.part && isDoor( v ) ? 1 : 0 }`;

		if ( key !== this.key ) {
			this.key = key;

			if ( this.body ) {
				this.group.remove( this.body );
			}

			this.body = makeBody( this );
			this.group.add( this.body );
		}
	}
}

/* ------------------------------------------------------------------ one figure */

const views = new Set();
let frame = 0;
let modelsJson;

const schedule = () => {
	if ( ! frame ) {
		frame = window.requestAnimationFrame( tick );
	}
};

const tick = ( now ) => {
	frame = 0;
	let again = false;

	for ( const view of Array.from( views ) ) {
		if ( ! view.el.isConnected ) {
			view.dispose();
		} else if ( view.update( now ) ) {
			again = true;
		}
	}

	if ( again ) {
		schedule();
	}
};

const allModels = () => {
	if ( modelsJson === undefined ) {
		try {
			modelsJson = JSON.parse( document.getElementById( 'forma-models' )?.textContent || '{}' );
		} catch ( error ) {
			modelsJson = {};
		}
	}

	return modelsJson;
};

class View {
	constructor( el, data ) {
		this.el = el;
		this.data = data;
		this.b = data.behaviours || {};
		this.slug = data.id;
		this.title = data.title;
		this.postId = null;
		this.edit = editing();
		this.visible = false;
		this.started = false;
		this.dirty = true;
		this.last = 0;
		this.w = 0;
		this.h = 0;

		this.rot = 0;
		this.rotTarget = 0;
		this.velocity = 0;
		this.dragging = false;
		this.dragX = 0;
		this.spinUntil = 0;
		this.tourStep = 0;
		this.tk = null;
		this.peopleK = 0;

		this.progress = 0;
		this.progressTarget = 0;
		this.stage = 0;
		this.trigger = null;

		this.explodeTarget = 0;
		this.explodeValue = 0;
		this.toggleAnim = null;
		this.assembleStart = 0;
		this.assembling = false;
		this.morph = null;
		this.labelAlpha = 0;
		this.morphFade = 1;
		this.slots = [];
		this.centre = new THREE.Vector3();
		this.tmp = new THREE.Vector3();
		this.lost = false;
		this.compiling = false;
		this.tier = tierNow();
		this.small = this.tier === 'phone';
		this.treesWanted = Number( data.trees ) || 0;
		this.peopleWanted = Number( data.people ) || 0;
		this.envTarget = null;
		this.used = new Set();
		this.mats = new Map();
		this.tour = this.b.tour ? { real: { value: 0 } } : null;
		this.trees = null;
		this.crowd = null;
		this.treeSpots = [];
		this.peopleSpots = [];
		this.treeK = 0;

		this.buildScene();
		this.build( data.volumes || [] );
		this.bind();

		if ( this.tour ) {
			this.initTour();
		}

		this.setupScroll();
	}

	/* ---- scene ---- */

	buildScene() {
		const { tier, small } = this;

		this.renderer = new THREE.WebGLRenderer( { alpha: true, antialias: true, powerPreference: 'default' } );
		this.renderer.setClearColor( 0x000000, 0 );
		this.renderer.outputColorSpace = THREE.SRGBColorSpace;
		// Neutral keeps the colours of the textures (ACES greys them).
		this.renderer.toneMapping = THREE.NeutralToneMapping;
		this.renderer.toneMappingExposure = EXPOSURE;
		this.renderer.shadowMap.enabled = ! small;
		// PCF is the soft one: the light's radius blurs the edge of every shadow.
		this.renderer.shadowMap.type = THREE.PCFShadowMap;
		this.canvas = this.renderer.domElement;
		this.canvas.setAttribute( 'aria-hidden', 'true' );
		this.el.insertBefore( this.canvas, this.el.firstChild );

		this.scene = new THREE.Scene();
		this.camera = new THREE.PerspectiveCamera( 28, 1.6, 0.1, 100 );
		this.model = new THREE.Group();
		this.scene.add( this.model );

		// Soft light from the sky (the HDRI or the room, loaded in start()), plus a gentle fill and one key light.
		this.scene.add( new THREE.HemisphereLight( 0xffffff, 0xd2cfc6, 0.3 ) );

		const key = new THREE.DirectionalLight( 0xfff6ea, KEY );
		key.position.set( 6, 12, 6 );

		if ( ! small ) {
			key.castShadow = true;
			key.shadow.mapSize.set( SHADOW_SIZE[ tier ], SHADOW_SIZE[ tier ] );
			key.shadow.camera.left = key.shadow.camera.bottom = -7;
			key.shadow.camera.right = key.shadow.camera.top = 7;
			key.shadow.camera.near = 1;
			key.shadow.camera.far = 40;
			key.shadow.bias = -0.0004;
			key.shadow.normalBias = 0.03;
			key.shadow.radius = tier === 'desktop' ? 6 : 4;
		}

		this.scene.add( key );

		// Real shadows fall on the ground outside the plinth (tablets and desktops).
		const ground = new THREE.Mesh( geometry( 'plane' ), material( 'shadow' ) );
		ground.rotation.x = -Math.PI / 2;
		ground.position.y = 0.002;
		ground.scale.set( 60, 60, 1 );
		ground.receiveShadow = true;
		ground.visible = ! small;
		this.scene.add( ground );
		this.ground = ground;

		// A blurred contact shadow under the plinth, on every screen; its size follows the plinth (see finishModel).
		this.contact = new THREE.Mesh( geometry( 'plane' ), new THREE.MeshBasicMaterial( { map: contactShadow(), transparent: true, depthWrite: false, opacity: 0 } ) );
		this.contact.rotation.x = -Math.PI / 2;
		this.contact.position.y = 0.004;
		this.contact.scale.set( 9, 6, 1 );
		this.scene.add( this.contact );

		this.parts = document.createElement( 'div' );
		this.parts.className = 'forma-model__parts';
		this.parts.setAttribute( 'aria-hidden', 'true' );
		// Dotted leader lines run from each part label (a pill) to its volume; they share one SVG layer.
		this.leaders = document.createElementNS( SVG, 'svg' );
		this.leaders.setAttribute( 'class', 'forma-model__leaders' );
		this.parts.appendChild( this.leaders );
		this.el.appendChild( this.parts );

		this.resizer = new ResizeObserver( () => this.resize() );
		this.resizer.observe( this.el );
		this.resize();
	}

	resize() {
		if ( ! this.el.isConnected ) {
			this.dispose();

			return;
		}

		const w = this.el.clientWidth;
		const h = this.el.clientHeight;

		if ( ! w || ! h ) {
			return;
		}

		this.w = w;
		this.h = h;
		this.renderer.setPixelRatio( Math.min( window.devicePixelRatio || 1, window.innerWidth < 1024 ? 1.25 : 1.5 ) );
		this.renderer.setSize( w, h, false );
		this.placeCamera();
		this.invalidate();
	}

	placeCamera() {
		const preset = CAMERAS[ this.data.camera ] || CAMERAS.hero;
		const target = this.data.camera === 'top' ? TARGET_TOP : TARGET;
		const aspect = this.w / this.h;
		// Narrow views stand further back so the plinth always fits across.
		const distance = ( this.data.distance || 1 ) * Math.max( 1, 1.12 / aspect );

		// A model coming apart needs more room: the camera rises and backs off a little as it explodes.
		const lift = 0.6 * this.explodeValue;

		this.camExplode = this.explodeValue;
		this.camera.aspect = aspect;
		this.camera.zoom = 1 / ( 1 + 0.2 * this.explodeValue );
		this.camera.position.set(
			target.x + ( preset[ 0 ] - target.x ) * distance,
			target.y + ( preset[ 1 ] - target.y ) * distance + lift,
			target.z + ( preset[ 2 ] - target.z ) * distance
		);
		this.camera.lookAt( target.x, target.y + lift, target.z );
		this.camera.updateProjectionMatrix();
	}

	/* ---- model ---- */

	build( volumes ) {
		this.volumes = volumes;
		volumes.forEach( ( v ) => this.addSlot( v ) );
		this.finishModel();
		this.snapStages();
	}

	addSlot( v ) {
		const slot = new Slot( v, this );
		this.slots.push( slot );
		this.model.add( slot.group );

		return slot;
	}

	/** After the volumes change: explode directions, labels, breathing wire and assemble order. */
	finishModel() {
		const movers = this.slots.filter( ( slot ) => ! slot.ground );
		const box = new THREE.Box3();

		movers.forEach( ( slot ) => box.expandByPoint( this.tmp.set( slot.base.x, slot.base.y, slot.base.z ) ) );
		box.isEmpty() ? this.centre.set( 0, 0.8, 0 ) : box.getCenter( this.centre );

		const wires = this.slots.filter( ( slot ) => slot.wire ).length;

		this.slots.forEach( ( slot ) => {
			const dx = slot.base.x - this.centre.x;
			const dy = ( slot.base.y - this.centre.y ) * 1.4;
			const dz = slot.base.z - this.centre.z;
			const len = Math.hypot( dx, dy, dz );

			slot.dir.set( dx, dy, dz );
			len < 0.4 ? slot.dir.set( 0, 1, 0 ) : slot.dir.divideScalar( len );
			// A lone dashed volume (the empty plot, the studio pin) breathes; frames and outlines made of many do not.
			slot.breath = slot.wire && wires <= 2;
		} );

		// Volumes assemble from the bottom up; the stagger is capped so a big model never takes long.
		const order = this.slots.map( ( slot, index ) => ( { slot, index } ) ).sort( ( a, b ) => a.slot.base.y - b.slot.base.y || a.index - b.index );
		const step = Math.min( 0.26, 1.5 / Math.max( 1, order.length ) );

		order.forEach( ( entry, rank ) => ( entry.slot.delay = rank * step ) );

		this.sizeContact();
		this.plant();
		this.buildLabels();
	}

	/** The plinth (a ground slab with no label and no stage), or null. */
	plinth() {
		return this.volumes.find( ( v ) => v.kind === 'slab' && v.y < 0.02 && ! v.part && ! v.stage ) || null;
	}

	/** The contact shadow follows the plinth: a little wider than it, nudged away from the key light. */
	sizeContact() {
		const plinth = this.plinth();

		this.contact.visible = Boolean( plinth );

		if ( plinth ) {
			this.contact.position.set( plinth.x - 0.14, 0.004, plinth.z - 0.12 );
			this.contact.scale.set( plinth.w / 0.75, plinth.d / 0.75, 1 );
		}
	}

	/** How far a point of the plinth is from the nearest volume, 0 within one: a function of ( x, z ). */
	roomFn() {
		const plinth = this.plinth();
		const feet = this.volumes
			.filter( ( v ) => v !== plinth && v.kind !== 'wire' && v.material !== 'wire' )
			.map( ( v ) => ( { x: v.x, z: v.z, hw: v.w / 2, hd: ( v.kind === 'cylinder' ? v.w : v.d ) / 2, round: v.kind === 'cylinder', cos: Math.cos( v.rot * DEG ), sin: Math.sin( v.rot * DEG ) } ) );

		return ( x, z ) =>
			feet.reduce( ( least, f ) => {
				const dx = x - f.x;
				const dz = z - f.z;

				if ( f.round ) {
					return Math.min( least, Math.hypot( dx, dz ) - f.hw );
				}

				const lx = Math.abs( dx * f.cos - dz * f.sin ) - f.hw;
				const lz = Math.abs( dx * f.sin + dz * f.cos ) - f.hd;

				return Math.min( least, lx <= 0 && lz <= 0 ? 0 : Math.hypot( Math.max( lx, 0 ), Math.max( lz, 0 ) ) );
			}, 9 );
	}

	/**
	 * Decide where the model's trees and people stand on the plinth. Trees go to the edge, at the spots farthest from every
	 * volume (never closer than TREE_CLEARANCE) and spread apart; people stand a short way from the buildings, toward the
	 * front, spread apart. It is deterministic, so a model always grows the same ones. Phones plant none.
	 */
	plant() {
		this.treeSpots = [];
		this.peopleSpots = [];

		const plinth = this.plinth();
		const trees = this.small || ! plinth ? 0 : clamp( Math.round( this.treesWanted ), 0, MAX_TREES );
		const people = this.small || ! plinth ? 0 : clamp( Math.round( this.peopleWanted ), 0, MAX_PEOPLE );

		if ( ! trees && ! people ) {
			this.makeScatter();
			this.drawScatter();

			return;
		}

		const distance = this.roomFn();
		const rand = rngOf( 90 + this.volumes.length * 7 + Math.round( plinth.w * 10 ) );
		const apart = ( list, spot, least ) => list.every( ( other ) => Math.hypot( other.x - spot.x, other.z - spot.z ) > least );

		if ( trees ) {
			const spots = [];

			// Two rings of candidates around the plinth's edge.
			[ 0.26, 0.52 ].forEach( ( inset ) => {
				const hw = plinth.w / 2 - inset;
				const hd = plinth.d / 2 - inset;

				for ( let run = 0; run < 4 * hw + 4 * hd; run += 0.3 ) {
					let x;
					let z;

					if ( run < 2 * hw ) {
						x = -hw + run;
						z = -hd;
					} else if ( run < 2 * hw + 2 * hd ) {
						x = hw;
						z = -hd + ( run - 2 * hw );
					} else if ( run < 4 * hw + 2 * hd ) {
						x = hw - ( run - 2 * hw - 2 * hd );
						z = hd;
					} else {
						x = -hw;
						z = hd - ( run - 4 * hw - 2 * hd );
					}

					spots.push( { x: x + plinth.x, z: z + plinth.z } );
				}
			} );

			spots.forEach( ( spot ) => ( spot.room = distance( spot.x, spot.z ) ) );
			spots.sort( ( a, b ) => b.room - a.room );

			for ( const spot of spots ) {
				if ( this.treeSpots.length >= trees || spot.room < TREE_CLEARANCE ) {
					break;
				}

				if ( apart( this.treeSpots, spot, 1.7 ) ) {
					this.treeSpots.push( { x: spot.x, z: spot.z, size: 0.85 + rand() * 0.4, turn: rand() * Math.PI * 2, tint: 0.75 + rand() * 0.5 } );
				}
			}
		}

		if ( people ) {
			const spots = [];

			for ( let x = -plinth.w / 2 + 0.3; x <= plinth.w / 2 - 0.3; x += 0.35 ) {
				for ( let z = -plinth.d / 2 + 0.3; z <= plinth.d / 2 - 0.3; z += 0.35 ) {
					const room = distance( x + plinth.x, z + plinth.z );

					if ( room >= 0.15 && room <= 1.1 && apart( this.treeSpots, { x: x + plinth.x, z: z + plinth.z }, 0.6 ) ) {
						// About 0.4 from a building, and toward the front (+Z), where the doors face.
						spots.push( { x: x + plinth.x, z: z + plinth.z, score: -Math.abs( room - 0.4 ) + 0.35 * ( z / ( plinth.d / 2 ) ) } );
					}
				}
			}

			spots.sort( ( a, b ) => b.score - a.score );

			for ( const spot of spots ) {
				if ( this.peopleSpots.length >= people ) {
					break;
				}

				if ( apart( this.peopleSpots, spot, 1.15 ) ) {
					this.peopleSpots.push( { x: spot.x, z: spot.z, turn: rand() * Math.PI * 2, tone: PEOPLE_TONES[ this.peopleSpots.length % PEOPLE_TONES.length ] } );
				}
			}
		}

		this.groundTop = plinth.y + plinth.h;
		this.makeScatter();
		this.drawScatter();
	}

	/** The meshes of the planted trees (a trunk and a few crowns each) and of the people (a body and a head), made afresh for the spots a model has. */
	makeScatter() {
		const mesh = ( geo, mat ) => {
			const part = new THREE.Mesh( geo, mat );

			part.castShadow = part.receiveShadow = true;

			return part;
		};
		const group = () => {
			const made = new THREE.Group();

			this.model.add( made );

			return made;
		};

		this.trees?.clear();
		this.crowd?.clear();

		if ( this.treeSpots.length ) {
			this.trees = this.trees || group();
			this.treeSpots.forEach( ( tree, i ) => {
				tree.trunk = mesh( geometry( 'trunk' ), material( 'trunk', this.tier ) );
				tree.crowns = CROWNS.map( ( _, j ) => mesh( geometry( 'crown' ), material( `leaf:${ i * 2 + j }`, this.tier ) ) );
				this.trees.add( tree.trunk, ...tree.crowns );
			} );
		}

		if ( this.peopleSpots.length ) {
			this.crowd = this.crowd || group();
			this.peopleSpots.forEach( ( person ) => {
				person.body = mesh( geometry( 'body' ), material( `person:${ person.tone }`, this.tier ) );
				person.head = mesh( geometry( 'head' ), material( 'skin', this.tier ) );
				this.crowd.add( person.body, person.head );
			} );
		}
	}

	/** Place every tree and person at the current growth (treeK, 0 to 1), each growing from its own foot. */
	drawScatter() {
		const up = new THREE.Vector3( 0, 1, 0 );
		const around = new THREE.Vector3();
		const k = this.treeK;

		if ( this.trees ) {
			this.trees.visible = k > 0.002 && this.treeSpots.length > 0;

			this.treeSpots.forEach( ( tree ) => {
				const s = tree.size * k;

				tree.trunk.position.set( tree.x, this.groundTop, tree.z );
				tree.trunk.rotation.y = tree.turn;
				tree.trunk.scale.set( s, s * TRUNK, s );

				CROWNS.forEach( ( [ dx, dy, dz, r ], j ) => {
					around.set( dx, 0, dz ).applyAxisAngle( up, tree.turn );
					tree.crowns[ j ].position.set( tree.x + around.x * s, this.groundTop + dy * s, tree.z + around.z * s );
					tree.crowns[ j ].scale.set( r * s, r * s * 0.92, r * s );
				} );
			} );
		}

		if ( this.crowd ) {
			const people = this.tour ? this.peopleK : k;

			this.crowd.visible = people > 0.002 && this.peopleSpots.length > 0;

			this.peopleSpots.forEach( ( person ) => {
				[ person.body, person.head ].forEach( ( part ) => {
					part.position.set( person.x, this.groundTop, person.z );
					part.rotation.y = person.turn;
					part.scale.setScalar( people || 1e-4 );
				} );
			} );
		}
	}

	/* ---- the Home tour ---- */

	/**
	 * Set the tour up: find the section's cards, make the site (the ghost of the house, the contour slabs) and the dimension
	 * lines, and take the step the page is at. Reduced motion, and the editor, show the finished model, still.
	 */
	initTour() {
		this.tourRoot = this.el.closest( '.forma-tour' ) || this.el.closest( '.e-con.e-parent' );
		this.tourCards = this.tourRoot ? Array.from( this.tourRoot.querySelectorAll( '[data-tour-step]' ) ) : [];
		this.tk = { buildT: 0, real: 0, ghost: 1, dims: 0, contour: 0, fade: 1 };
		this.buildTotal = this.slots.reduce( ( most, slot ) => Math.max( most, slot.delay ), 0 ) + DROP;
		this.dimFront = -1;
		this.dimText = [ '', '' ];
		this.makeGhosts();
		this.makeContours();
		this.makeDims();

		if ( reduced() || this.edit ) {
			this.setTourStep( 3 );
			this.settleTour();
		} else {
			this.setTourStep( this.readTourStep() );
		}
	}

	/** The step the page is at: the card nearest the middle of the screen (of the part above the stage, on a tablet or a phone) (the stage's own progress when there are no cards). */
	readTourStep() {
		if ( ! this.tourCards.length ) {
			const p = this.progressTarget;

			return p < 0.25 ? 1 : p < 0.75 ? 2 : 3;
		}

		// Beside the model the cards cross the whole screen; below 1024px they scroll up in the part above the stage.
		const stage = window.innerWidth < 1024 ? this.el.closest( '.forma-tour__stage' ) : null;
		const middle = stage ? clamp( stage.getBoundingClientRect().top, 0, window.innerHeight ) / 2 : window.innerHeight / 2;
		let step = 1;
		let nearest = Infinity;

		this.tourCards.forEach( ( card ) => {
			const rect = card.getBoundingClientRect();
			const away = Math.abs( rect.top + rect.height / 2 - middle );

			if ( away < nearest ) {
				nearest = away;
				step = clamp( Number( card.dataset.tourStep ) || 1, 1, 3 );
			}
		} );

		return step;
	}

	setTourStep( step ) {
		if ( step === this.tourStep ) {
			return;
		}

		this.tourStep = step;

		if ( this.tourRoot ) {
			this.tourRoot.dataset.tourActive = String( step );
		}

		this.el.dispatchEvent( new CustomEvent( 'forma-model:tour', { bubbles: true, detail: { step } } ) );
		this.invalidate();
	}

	/** What each animated value of the tour is heading for at the current step. */
	tourTargets() {
		const step = this.tourStep;

		return { build: step >= 2 ? this.buildTotal : 0, real: step >= 3 ? 1 : 0, ghost: step === 1 ? 1 : 0, dims: step === 2 ? 1 : 0, contour: 1 };
	}

	/** Put every animated value of the tour at its target at once (a still frame). */
	settleTour() {
		const target = this.tourTargets();
		const tk = this.tk;

		tk.buildT = target.build;
		tk.real = target.real;
		tk.ghost = target.ghost;
		tk.dims = target.dims;
		tk.contour = target.contour;
		this.treeK = 1;
		this.applyTour();
	}

	/** Move the tour's animated values toward their targets. Returns whether any is still moving. */
	stepTour( dt, still ) {
		const target = this.tourTargets();
		const tk = this.tk;
		let busy = false;

		if ( still ) {
			this.settleTour();

			return false;
		}

		// The volumes drop in at the speed of an assembly, and lift away faster than they came.
		if ( Math.abs( target.build - tk.buildT ) > 1e-3 ) {
			tk.buildT = target.build > tk.buildT ? Math.min( target.build, tk.buildT + dt ) : Math.max( target.build, tk.buildT - dt * 2.4 );
			busy = true;
		} else {
			tk.buildT = target.build;
		}

		[ [ 'real', 2.2 ], [ 'ghost', 3 ], [ 'dims', 1.7 ], [ 'contour', 1.3 ] ].forEach( ( [ key, rate ] ) => {
			if ( Math.abs( target[ key ] - tk[ key ] ) > 2e-3 ) {
				tk[ key ] += ( target[ key ] - tk[ key ] ) * ( 1 - Math.exp( -dt * rate ) );
				busy = true;
			} else {
				tk[ key ] = target[ key ];
			}
		} );

		// A dimension line that has had to move to another corner of the building fades back in.
		if ( tk.fade < 1 ) {
			tk.fade = Math.min( 1, tk.fade + dt * 3.5 );
			busy = true;
		}

		this.applyTour();

		return busy;
	}

	/** Write the tour's values into the model: which volumes have landed, how real the materials are, who has arrived. */
	applyTour() {
		const tk = this.tk;

		this.slots.forEach( ( slot ) => {
			if ( slot.ground ) {
				slot.revealed = true;
				slot.drop = 0;

				return;
			}

			const t = ( tk.buildT - slot.delay ) / DROP;

			slot.revealed = t > 0;
			slot.drop = 4.5 * ( 1 - quartOut( clamp( t, 0, 1 ) ) );
		} );

		this.tour.real.value = tk.real;
		this.peopleK = tk.real;

		// The grain of the boards and the bumps of the plaster come in with the real materials, and the volume of glass clears.
		this.mats.forEach( ( mat ) => {
			if ( mat.userData.look ) {
				mat.normalScale.setScalar( mat.userData.look.normal * tk.real );
			}

			if ( mat.isMeshPhysicalMaterial && mat.transparent ) {
				mat.opacity = lerp( 1, 0.34, tk.real );
			}
		} );
	}

	/** The tour's explosion: gentle, over the last third of the section. */
	tourExplode() {
		return reduced() || this.edit ? 0 : 0.55 * cubicInOut( clamp( ( this.progress - 2 / 3 ) * 3, 0, 1 ) );
	}

	/** The ghost of the house: every volume's box as a dashed outline of its own, in Signal blue, kept where the volume belongs. */
	makeGhosts() {
		this.ghosts = new THREE.Group();
		this.ghostMat = new THREE.LineDashedMaterial( { color: '#2B3BFF', dashSize: 0.075, gapSize: 0.055, transparent: true, opacity: 0.6, depthWrite: false } );
		this.ghostLines = this.slots.map( ( slot ) => {
			const { v } = slot;

			if ( slot.ground || slot.wire || v.material === 'leaf' || v.material === 'water' || Math.max( v.w, v.kind === 'cylinder' ? v.w : v.d ) < 0.3 ) {
				return null;
			}

			const line = new THREE.LineSegments( ghostGeometry( v.w, v.h, v.kind === 'cylinder' ? v.w : v.d ), this.ghostMat );

			line.frustumCulled = false;
			this.ghosts.add( line );

			return line;
		} );
		this.model.add( this.ghosts );
	}

	/**
	 * The contour slabs of the site: a few thin slabs under the plinth, each wider than the one above it, so that the ground the
	 * model stands on is cut into steps like the layers of a contour model. They are travertine, so they show as white foam until
	 * the model is built.
	 */
	makeContours() {
		const plinth = this.plinth();

		if ( ! plinth ) {
			return;
		}

		this.contours = new THREE.Group();
		this.contourSlabs = CONTOURS.map( ( _, i ) => {
			const w = plinth.w + 2 * CONTOUR_STEP * ( i + 1 );
			const d = plinth.d + 2 * CONTOUR_STEP * ( i + 1 );
			const slab = new THREE.Mesh( sized( 'box', { w, h: CONTOUR_THICK, d, material: 'stone' }, this.tier ), material( 'stone', this.tier, this ) );

			slab.scale.set( w, CONTOUR_THICK, d );
			slab.position.x = plinth.x;
			slab.position.z = plinth.z;
			slab.receiveShadow = true;
			this.contours.add( slab );

			return slab;
		} );
		this.model.add( this.contours );
	}

	/** The SVG of the dimension lines, one group for each of the two sides of the building they stand on. */
	makeDims() {
		const svg = document.createElementNS( SVG, 'svg' );

		svg.setAttribute( 'class', 'forma-model__dims' );
		svg.setAttribute( 'aria-hidden', 'true' );
		svg.style.display = 'none';

		this.dimLines = [ 0, 1 ].map( () => {
			const g = document.createElementNS( SVG, 'g' );
			const make = ( tag, className ) => {
				const node = document.createElementNS( SVG, tag );

				node.setAttribute( 'class', className );
				g.appendChild( node );

				return node;
			};
			const line = { ext1: make( 'line', 'is-ext' ), ext2: make( 'line', 'is-ext' ), main: make( 'line', 'is-main' ), tick1: make( 'line', 'is-tick' ), tick2: make( 'line', 'is-tick' ), label: make( 'text', 'is-label' ) };

			line.main.setAttribute( 'pathLength', '1' );
			svg.appendChild( g );

			return line;
		} );

		this.el.insertBefore( svg, this.parts );
		this.dimsSvg = svg;
		this.dimsOn = false;
	}

	/** The building's footprint on the plinth (the boxes of its volumes, turned as they are), which the dimension lines measure. */
	dimBox() {
		if ( this.footprint === undefined ) {
			const plinth = this.plinth();
			const box = { x0: Infinity, x1: -Infinity, z0: Infinity, z1: -Infinity };

			this.volumes.forEach( ( v ) => {
				if ( v === plinth || v.kind === 'wire' || [ 'wire', 'leaf', 'water' ].includes( v.material ) ) {
					return;
				}

				const hw = v.w / 2;
				const hd = ( v.kind === 'cylinder' ? v.w : v.d ) / 2;
				const cos = Math.cos( v.rot * DEG );
				const sin = Math.sin( v.rot * DEG );

				[ [ -1, -1 ], [ 1, -1 ], [ 1, 1 ], [ -1, 1 ] ].forEach( ( [ sx, sz ] ) => {
					const lx = sx * hw;
					const lz = sz * hd;
					const x = v.x + lx * cos + lz * sin;
					const z = v.z - lx * sin + lz * cos;

					box.x0 = Math.min( box.x0, x );
					box.x1 = Math.max( box.x1, x );
					box.z0 = Math.min( box.z0, z );
					box.z1 = Math.max( box.z1, z );
				} );
			} );

			this.footprint = Number.isFinite( box.x0 ) ? box : null;
		}

		return this.footprint;
	}

	/** Everything about the tour that follows the camera, once a frame: the ghost, the contour slabs and the dimension lines. */
	updateTour() {
		const tk = this.tk;

		this.ghosts.visible = tk.ghost > 0.01;

		if ( this.ghosts.visible ) {
			this.ghostMat.opacity = 0.6 * tk.ghost;
			this.slots.forEach( ( slot, i ) => {
				const line = this.ghostLines[ i ];

				if ( line ) {
					const b = slot.base;

					line.position.set( b.x, b.y, b.z );
					line.rotation.y = b.ry;
					line.scale.set( b.sx, b.sy, b.sz );
				}
			} );
		}

		if ( this.contours ) {
			this.contourSlabs.forEach( ( slab, i ) => {
				const k = clamp( tk.contour * ( CONTOURS.length + 1 ) - i, 0, 1 );

				slab.visible = k > 0.001;
				slab.scale.y = CONTOUR_THICK * Math.max( k, 1e-3 );
				slab.position.y = -i * CONTOUR_THICK - ( CONTOUR_THICK * k ) / 2;
			} );
		}

		if ( this.lastPeopleK !== this.peopleK ) {
			this.lastPeopleK = this.peopleK;
			this.drawScatter();
		}

		this.updateDims();
	}

	/** Draw the dimension lines on the two sides of the building that face the camera, reading their lengths in metres. */
	updateDims() {
		const tk = this.tk;
		const show = tk.dims * tk.fade;

		if ( show < 0.002 ) {
			if ( this.dimsOn ) {
				this.dimsSvg.style.display = 'none';
				this.dimsOn = false;
			}

			return;
		}

		const box = this.dimBox();

		if ( ! box ) {
			return;
		}

		if ( ! this.dimsOn ) {
			this.dimsSvg.style.display = '';
			this.dimsOn = true;
		}

		this.model.updateMatrixWorld( true );

		const y = ( this.groundTop || 0.18 ) + 0.006;
		const cx = ( box.x0 + box.x1 ) / 2;
		const cz = ( box.z0 + box.z1 ) / 2;
		const corners = [ [ box.x0, box.z0 ], [ box.x1, box.z0 ], [ box.x1, box.z1 ], [ box.x0, box.z1 ] ];
		const at = ( x, z ) => {
			this.tmp.set( x, y, z );
			this.model.localToWorld( this.tmp );
			this.tmp.project( this.camera );

			return { x: ( this.tmp.x * 0.5 + 0.5 ) * this.w, y: ( -this.tmp.y * 0.5 + 0.5 ) * this.h };
		};
		const screen = corners.map( ( [ x, z ] ) => at( x, z ) );
		let front = 0;

		screen.forEach( ( p, i ) => {
			if ( p.y > screen[ front ].y ) {
				front = i;
			}
		} );

		// The corner nearest the bottom of the screen; one that is only just lower does not take over.
		if ( this.dimFront < 0 ) {
			this.dimFront = front;
		} else if ( front !== this.dimFront && screen[ front ].y > screen[ this.dimFront ].y + 16 ) {
			this.dimFront = front;
			tk.fade = 0;
		}

		const corner = corners[ this.dimFront ];

		[ ( this.dimFront + 3 ) % 4, ( this.dimFront + 1 ) % 4 ].forEach( ( other, n ) => {
			this.drawDim( n, corner, corners[ other ], cx, cz, at, show );
		} );
	}

	/** One dimension line along the edge a to b of the footprint, held off the building, with its extension lines, ticks and label. */
	drawDim( n, a, b, cx, cz, at, show ) {
		const line = this.dimLines[ n ];
		const alongX = Math.abs( a[ 1 ] - b[ 1 ] ) < 1e-6;
		const out = alongX ? [ 0, a[ 1 ] >= cz ? 1 : -1 ] : [ a[ 0 ] >= cx ? 1 : -1, 0 ];
		const away = ( p, d ) => at( p[ 0 ] + out[ 0 ] * d, p[ 1 ] + out[ 1 ] * d );
		const e1 = [ away( a, DIM_GAP ), away( a, DIM_OFFSET + DIM_OVER ) ];
		const e2 = [ away( b, DIM_GAP ), away( b, DIM_OFFSET + DIM_OVER ) ];
		const d1 = away( a, DIM_OFFSET );
		const d2 = away( b, DIM_OFFSET );
		const ext = clamp( show / 0.25, 0, 1 );
		const main = clamp( ( show - 0.15 ) / 0.65, 0, 1 );
		const mark = clamp( ( show - 0.75 ) / 0.25, 0, 1 );
		const set = ( node, p, q ) => {
			node.setAttribute( 'x1', p.x.toFixed( 1 ) );
			node.setAttribute( 'y1', p.y.toFixed( 1 ) );
			node.setAttribute( 'x2', q.x.toFixed( 1 ) );
			node.setAttribute( 'y2', q.y.toFixed( 1 ) );
		};
		const len = Math.hypot( d2.x - d1.x, d2.y - d1.y ) || 1;
		const ux = ( d2.x - d1.x ) / len;
		const uy = ( d2.y - d1.y ) / len;
		// A tick is a short slash at 45 degrees to the line.
		const tx = ( ux - uy ) * 0.7071 * 6;
		const ty = ( ux + uy ) * 0.7071 * 6;
		const side = Math.hypot( e1[ 1 ].x - e1[ 0 ].x, e1[ 1 ].y - e1[ 0 ].y ) || 1;
		const nx = ( e1[ 1 ].x - e1[ 0 ].x ) / side;
		const ny = ( e1[ 1 ].y - e1[ 0 ].y ) / side;
		const mx = ( d1.x + d2.x ) / 2 + nx * 11;
		const my = ( d1.y + d2.y ) / 2 + ny * 11;
		let angle = ( Math.atan2( d2.y - d1.y, d2.x - d1.x ) * 180 ) / Math.PI;

		if ( angle > 90 || angle < -90 ) {
			angle += 180;
		}

		set( line.ext1, e1[ 0 ], e1[ 1 ] );
		set( line.ext2, e2[ 0 ], e2[ 1 ] );
		set( line.main, d1, d2 );
		set( line.tick1, { x: d1.x - tx, y: d1.y - ty }, { x: d1.x + tx, y: d1.y + ty } );
		set( line.tick2, { x: d2.x - tx, y: d2.y - ty }, { x: d2.x + tx, y: d2.y + ty } );
		line.ext1.style.opacity = line.ext2.style.opacity = String( ext );
		line.main.style.strokeDashoffset = String( 1 - main );
		line.main.style.opacity = String( main > 0 ? 1 : 0 );
		line.tick1.style.opacity = line.tick2.style.opacity = String( mark );
		line.label.style.opacity = String( mark );
		line.label.setAttribute( 'transform', `translate(${ mx.toFixed( 1 ) } ${ my.toFixed( 1 ) }) rotate(${ angle.toFixed( 1 ) })` );

		const text = `${ ( Math.hypot( b[ 0 ] - a[ 0 ], b[ 1 ] - a[ 1 ] ) * METRES ).toFixed( 1 ) } m`;

		if ( text !== this.dimText[ n ] ) {
			this.dimText[ n ] = text;
			line.label.textContent = text;
		}
	}

	buildLabels() {
		this.parts.querySelectorAll( '.forma-model__part' ).forEach( ( label ) => label.remove() );
		this.leaders.textContent = '';

		this.slots.forEach( ( slot ) => {
			slot.label = slot.line = slot.dot = null;
			slot.labelSize = null;

			if ( slot.part && ! slot.retiring ) {
				const label = document.createElement( 'span' );
				label.className = 'forma-model__part';
				label.textContent = slot.part;
				this.parts.appendChild( label );

				slot.label = label;
				slot.line = document.createElementNS( SVG, 'line' );
				slot.dot = document.createElementNS( SVG, 'circle' );
				slot.dot.setAttribute( 'r', '3' );
				this.leaders.append( slot.line, slot.dot );
			}
		} );
	}

	/** Set every slot's stage visibility to the current progress without animating. */
	snapStages() {
		this.slots.forEach( ( slot ) => ( slot.present = this.stageTarget( slot ) ) );
	}

	stageTarget( slot ) {
		return ! this.b.stages || ! slot.stage || this.progress >= ( slot.stage - 1 ) / 6 - 1e-6 ? 1 : 0;
	}

	/* ---- behaviours ---- */

	bind() {
		const { el, b } = this;

		this.observer = new IntersectionObserver(
			( entries ) => {
				this.visible = entries[ entries.length - 1 ].isIntersecting;

				if ( this.visible ) {
					this.start();
				}

				this.last = 0;

				if ( this.visible ) {
					this.wake();
				}

				this.invalidate();
			},
			{ rootMargin: '120px' }
		);
		this.observer.observe( el );

		this.canvas.addEventListener( 'webglcontextlost', ( event ) => {
			event.preventDefault();
			this.lost = true;
		} );
		this.canvas.addEventListener( 'webglcontextrestored', () => {
			this.lost = false;
			// The sky's light lived in a render target the lost context took with it.
			this.envTarget = null;
			this.lightSky();
			this.invalidate();
		} );

		el.addEventListener( 'pointerenter', () => this.wake() );

		if ( b.drag ) {
			el.addEventListener( 'pointerdown', ( event ) => this.dragStart( event ) );
			el.addEventListener( 'pointermove', ( event ) => this.dragMove( event ) );
			el.addEventListener( 'pointerup', ( event ) => this.dragEnd( event ) );
			el.addEventListener( 'pointercancel', ( event ) => this.dragEnd( event ) );
		}

		if ( b.keyboard ) {
			el.tabIndex = 0;
			el.setAttribute( 'role', 'group' );
			el.setAttribute( 'aria-roledescription', '3D model' );
			el.setAttribute( 'aria-label', `Study model of ${ this.title }. Use the left and right arrow keys to turn it.` );
			el.addEventListener( 'keydown', ( event ) => {
				if ( event.key === 'ArrowLeft' || event.key === 'ArrowRight' ) {
					event.preventDefault();
					this.rotTarget += ( event.key === 'ArrowRight' ? 1 : -1 ) * 15 * DEG;
					this.invalidate();
				}
			} );
		}

		this.button = el.querySelector( '.forma-model__explode' );

		if ( this.button ) {
			this.button.addEventListener( 'click', () => this.toggleExplode() );
		}
	}

	dragStart( event ) {
		if ( ( event.pointerType === 'mouse' && event.button !== 0 ) || event.target.closest( 'button' ) ) {
			return;
		}

		this.dragging = true;
		this.dragX = event.clientX;
		this.velocity = 0;
		this.el.classList.add( 'is-dragging' );

		try {
			this.el.setPointerCapture( event.pointerId );
		} catch ( error ) {
			// The pointer may already be gone; dragging still works while it stays over the figure.
		}

		this.invalidate();
	}

	dragMove( event ) {
		if ( ! this.dragging ) {
			return;
		}

		const delta = ( event.clientX - this.dragX ) * 0.0085;

		this.dragX = event.clientX;
		this.rot += delta;
		this.rotTarget = this.rot;
		this.velocity = clamp( this.velocity * 0.4 + delta * 0.6, -0.12, 0.12 );
		this.invalidate();
	}

	dragEnd( event ) {
		if ( ! this.dragging ) {
			return;
		}

		this.wake();

		this.dragging = false;
		this.el.classList.remove( 'is-dragging' );

		try {
			this.el.releasePointerCapture( event.pointerId );
		} catch ( error ) {
			// Already released.
		}

		this.invalidate();
	}

	toggleExplode() {
		const to = this.explodeTarget ? 0 : 1;

		this.explodeTarget = to;
		this.button?.setAttribute( 'aria-pressed', to ? 'true' : 'false' );
		this.toggleAnim = { from: this.toggleValue(), to, t0: performance.now(), dur: reduced() ? 0 : 700 };
		this.invalidate();
	}

	toggleValue() {
		return this.toggleAnim ? this.toggleAnim.value ?? this.toggleAnim.from : this.explodeTarget;
	}

	/** Scroll-driven behaviours follow a ScrollTrigger; with no GSAP or reduced motion they stay at rest. */
	setupScroll( later = false ) {
		const { b } = this;

		if ( ! ( b.scrollOrbit || b.scrollExplode || b.stages ) ) {
			return;
		}

		if ( reduced() || this.edit ) {
			// A still frame: stages fully grown; in the editor orbit and explosion are shown part way.
			this.progress = this.progressTarget = b.stages ? 1 : this.edit ? 0.5 : 0;
			this.snapStages();

			return;
		}

		if ( ! window.gsap || ! window.ScrollTrigger ) {
			if ( ! later ) {
				window.addEventListener( 'load', () => this.setupScroll( true ), { once: true } );
			}

			return;
		}

		window.gsap.registerPlugin( window.ScrollTrigger );

		const section = this.data.scrollTrigger === 'section' ? this.el.closest( '.e-con.e-parent' ) : null;
		const whole = Boolean( section );

		this.trigger = window.ScrollTrigger.create( {
			trigger: section || this.el,
			start: whole ? 'top top' : 'top bottom',
			end: whole ? 'bottom bottom' : 'bottom top',
			onUpdate: ( self ) => this.setProgress( self.progress ),
			onRefresh: ( self ) => this.setProgress( self.progress, true ),
		} );
	}

	setProgress( value, snap = false ) {
		this.progressTarget = clamp( value, 0, 1 );

		if ( snap ) {
			this.progress = this.progressTarget;
			this.snapStages();
		}

		if ( this.tour && ! reduced() && ! this.edit ) {
			this.setTourStep( this.readTourStep() );
		}

		const stage = this.b.stages ? Math.min( 6, Math.floor( this.progressTarget * 6 + 1e-6 ) + 1 ) : 0;

		if ( stage !== this.stage ) {
			this.stage = stage;
			this.el.dispatchEvent( new CustomEvent( 'forma-model:stage', { bubbles: true, detail: { stage } } ) );
		}

		this.invalidate();
	}

	/* ---- swapping ---- */

	swapTo( id ) {
		const model = allModels()[ id ];

		if ( ! model ) {
			return;
		}

		if ( this.postId === null ) {
			this.postId = Object.keys( allModels() ).find( ( key ) => allModels()[ key ].slug === this.slug ) ?? '';
		}

		if ( String( this.postId ) === String( id ) ) {
			return;
		}

		this.postId = id;
		this.treesWanted = Number( model.trees ) || 0;
		this.peopleWanted = Number( model.people ) || 0;
		this.slug = model.slug;
		this.title = model.title;
		this.el.querySelector( '.screen-reader-text' )?.replaceChildren( `Study model of ${ model.title }` );
		this.morphTo( model.volumes || [] );
		this.el.dispatchEvent( new CustomEvent( 'forma-model:swap', { bubbles: true, detail: model } ) );
	}

	/**
	 * Morph into another model: volumes are matched by index; position, scale and rotation tween over 700ms, extra
	 * volumes scale in or out, and looks (shape, material, label) change at the midpoint.
	 */
	morphTo( volumes ) {
		this.finishMorph();

		const entries = [];
		const count = Math.max( volumes.length, this.slots.length );

		for ( let i = 0; i < count; i++ ) {
			const v = volumes[ i ];
			let slot = this.slots[ i ];

			if ( slot && v ) {
				entries.push( { slot, v, from: { ...slot.base }, to: baseOf( v ) } );
			} else if ( slot ) {
				slot.retiring = true;
				entries.push( { slot, v: null, from: { ...slot.base }, to: { ...slot.base, sx: 0, sy: 0, sz: 0 } } );
			} else {
				slot = this.addSlot( v );
				slot.base = { ...baseOf( v ), sx: 0, sy: 0, sz: 0 };
				slot.revealed = true;
				entries.push( { slot, v, from: { ...slot.base }, to: baseOf( v ), fresh: true } );
			}
		}

		this.morph = { t0: performance.now(), dur: reduced() ? 0 : 700, entries, volumes, swapped: false };
		this.invalidate();
	}

	stepMorph( now ) {
		const m = this.morph;
		const t = m.dur ? clamp( ( now - m.t0 ) / m.dur, 0, 1 ) : 1;
		const k = cubicInOut( t );

		this.morphFade = m.dur ? clamp( Math.abs( 2 * t - 1 ) * 1.5, 0, 1 ) : 1;

		if ( t >= 0.5 && ! m.swapped ) {
			this.applyLooks();
		}

		m.entries.forEach( ( { slot, from, to } ) => {
			const b = slot.base;
			const turn = ( ( ( to.ry - from.ry + Math.PI ) % ( 2 * Math.PI ) ) + 2 * Math.PI ) % ( 2 * Math.PI ) - Math.PI;

			b.x = lerp( from.x, to.x, k );
			b.y = lerp( from.y, to.y, k );
			b.z = lerp( from.z, to.z, k );
			b.ry = from.ry + turn * k;
			b.sx = lerp( from.sx, to.sx, k );
			b.sy = lerp( from.sy, to.sy, k );
			b.sz = lerp( from.sz, to.sz, k );
		} );

		if ( t >= 1 ) {
			this.finishMorph();
		}

		return this.morph !== null;
	}

	applyLooks() {
		this.morph.swapped = true;
		this.morph.entries.forEach( ( { slot, v } ) => v && slot.set( v ) );
		this.buildLabels();
	}

	finishMorph() {
		const m = this.morph;

		if ( ! m ) {
			return;
		}

		this.morph = null;
		this.morphFade = 1;

		if ( ! m.swapped ) {
			m.entries.forEach( ( { slot, v } ) => v && slot.set( v ) );
		}

		m.entries.forEach( ( { slot, to, v } ) => {
			slot.base = { ...to };

			if ( ! v ) {
				this.model.remove( slot.group );
				this.slots.splice( this.slots.indexOf( slot ), 1 );
			}
		} );

		this.volumes = m.volumes;
		this.finishModel();
		this.snapStages();
		this.slots.forEach( ( slot ) => ( slot.delay = 0 ) );
		this.invalidate();
	}

	/* ---- frame ---- */

	start() {
		if ( this.started ) {
			return;
		}

		this.started = true;
		this.assembling = this.b.assemble && ! reduced() && ! this.edit;
		this.assembleStart = performance.now();
		this.slots.forEach( ( slot ) => ( slot.revealed = ! this.assembling ) );

		// Fetch the sky and the maps of every surface the model uses, then compile the shaders where the browser can do it off the
		// main thread (KHR_parallel_shader_compile); the first frame waits for all of it, and the assembly starts when it is ready.
		// The planted trees and the people are drawn only once the model has landed, but their programs are compiled now with the rest.
		const unseen = [ this.trees, this.crowd ].filter( ( group ) => group && ! group.visible );
		const ready = () => {
			unseen.forEach( ( group ) => ( group.visible = false ) );
			this.compiling = false;
			this.assembleStart = performance.now();
			this.wake( 3000 );
			this.invalidate();
		};

		this.compiling = true;

		if ( this.b.swap ) {
			// A model that swaps to the others meets every surface sooner or later: have them all before the first frame.
			Object.keys( LOOKS ).forEach( ( name ) => material( name, this.tier, this ) );
		}

		Promise.all( [ this.fetchSky(), ...Array.from( this.used ).map( ( mat ) => mat.userData.ready ) ] ).then( async ( [ sky ] ) => {
			if ( ! views.has( this ) ) {
				return;
			}

			// Hand each map to the GPU in a task of its own, so the first frames do not stall on uploading them all.
			const maps = new Set();

			this.used.forEach( ( mat ) => [ mat.map, mat.normalMap, mat.roughnessMap ].forEach( ( tex ) => tex && maps.add( tex ) ) );

			for ( const tex of maps ) {
				if ( views.has( this ) ) {
					this.renderer.initTexture( tex );
					await new Promise( ( resolve ) => window.setTimeout( resolve, 0 ) );
				}
			}

			if ( ! views.has( this ) ) {
				return;
			}

			this.lightSky( sky );

			unseen.forEach( ( group ) => ( group.visible = true ) );

			if ( typeof this.renderer.compileAsync === 'function' ) {
				// Each program is asked for its uniforms (a round trip to the GPU for every one of them) in a task of its own, rather
				// than all inside the first frame.
				this.renderer.compileAsync( this.scene, this.camera ).then( async () => {
					await gpuIdle( this.renderer.getContext() );

					for ( const program of Array.from( this.renderer.info.programs || [] ) ) {
						if ( views.has( this ) ) {
							program.getUniforms();
							program.getAttributes();
							await new Promise( ( resolve ) => window.setTimeout( resolve, 0 ) );
						}
					}

					ready();
				}, ready );
			} else {
				ready();
			}
		} );
	}

	/** The sky to light the scene with: the HDRI on tablets and desktops (the room, which is lighter, on phones or if the HDRI cannot load). */
	fetchSky() {
		return ( this.small ? Promise.resolve( null ) : loadHdri() ).then( ( hdri ) => ( hdri ? { hdri } : loadRoom().then( ( Room ) => ( Room ? { Room } : null ) ) ) );
	}

	/** Light the scene from the sky, baked once into this renderer's PMREM. Failing, the key and the sky fill still light it. */
	lightSky( sky = this.sky ) {
		if ( ! sky || ! views.has( this ) ) {
			return;
		}

		this.sky = sky;

		try {
			const pmrem = new THREE.PMREMGenerator( this.renderer );

			this.envTarget?.dispose();

			if ( sky.hdri ) {
				this.envTarget = pmrem.fromEquirectangular( sky.hdri );
				this.scene.environmentIntensity = SKY.hdri;
			} else {
				const room = new sky.Room();

				this.envTarget = pmrem.fromScene( room, 0.04 );
				this.scene.environmentIntensity = SKY.room;
				room.dispose?.();
			}

			this.scene.environment = this.envTarget.texture;
			pmrem.dispose();
		} catch ( error ) {
			this.scene.environment = null;
		}
	}

	invalidate() {
		this.dirty = true;
		schedule();
	}

	/** Let a model that turns by itself go on turning for a while (the assembly, if any, comes first: `extra` ms more). */
	wake( extra = 0 ) {
		this.spinUntil = Math.max( this.spinUntil, performance.now() + IDLE_SPIN + extra );
		this.invalidate();
	}

	/** Advance and draw one frame. Returns whether another is needed. */
	update( now ) {
		if ( ! this.visible || ! this.started || this.compiling || this.lost || ! this.w ) {
			this.last = 0;

			return false;
		}

		const dt = this.last ? Math.min( 0.05, ( now - this.last ) / 1000 ) : 1 / 60;
		const f = dt * 60;
		const still = reduced();
		let busy = false;

		this.last = now;

		// Assemble: volumes drop in from above, bottom first.
		if ( this.assembling ) {
			const elapsed = ( performance.now() - this.assembleStart ) / 1000;
			let pending = false;

			this.slots.forEach( ( slot ) => {
				const t = ( elapsed - slot.delay ) / 0.9;

				slot.revealed = t >= 0;
				slot.drop = 4.5 * ( 1 - quartOut( clamp( t, 0, 1 ) ) );
				pending = pending || t < 1;
			} );

			this.assembling = pending;
			busy = true;

			if ( ! pending ) {
				this.slots.forEach( ( slot ) => ( slot.drop = 0 ) );
			}
		}

		// Callout labels wait for the model to finish assembling.
		const labelTarget = this.assembling ? 0 : 1;

		if ( Math.abs( labelTarget - this.labelAlpha ) > 1e-3 ) {
			this.labelAlpha = still ? labelTarget : this.labelAlpha + ( labelTarget - this.labelAlpha ) * ( 1 - Math.exp( -dt * 6 ) );
			busy = true;
		} else {
			this.labelAlpha = labelTarget;
		}

		// Rotation: drag with inertia, keyboard easing, idle spin.
		if ( this.dragging ) {
			busy = true;
		} else if ( Math.abs( this.velocity ) > 1e-4 ) {
			this.rot += this.velocity * f;
			this.rotTarget = this.rot;
			this.velocity *= Math.pow( 0.94, f );
			busy = true;
		} else if ( Math.abs( this.rotTarget - this.rot ) > 1e-4 ) {
			this.rot += ( this.rotTarget - this.rot ) * ( 1 - Math.exp( -dt * 14 ) );
			busy = true;
		}

		const scrolled = this.b.scrollOrbit || this.b.scrollExplode || this.b.stages;

		if ( ! still && ! scrolled && ! this.dragging && this.explodeValue < 0.02 && Math.abs( this.velocity ) < 1e-3 && performance.now() < this.spinUntil ) {
			this.rot += 0.002 * f;
			this.rotTarget += 0.002 * f;
			busy = true;
		}

		// Scroll progress, eased so a wheel notch glides.
		if ( Math.abs( this.progressTarget - this.progress ) > 5e-4 ) {
			this.progress += ( this.progressTarget - this.progress ) * ( 1 - Math.exp( -dt * 9 ) );
			busy = true;
		} else {
			this.progress = this.progressTarget;
		}

		if ( this.tour ) {
			busy = this.stepTour( dt, still || this.edit ) || busy;
		}

		// Explosion: the toggle button and the scroll, whichever is further.
		let toggle = this.explodeTarget;

		if ( this.toggleAnim ) {
			const a = this.toggleAnim;
			const t = a.dur ? clamp( ( performance.now() - a.t0 ) / a.dur, 0, 1 ) : 1;

			a.value = lerp( a.from, a.to, cubicInOut( t ) );
			toggle = a.value;

			if ( t >= 1 ) {
				this.toggleAnim = null;
			}

			busy = true;
		}

		this.explodeValue = Math.max( toggle, this.tour ? this.tourExplode() : this.b.scrollExplode ? this.progress : 0 );

		if ( this.camExplode !== this.explodeValue ) {
			this.placeCamera();
		}

		if ( this.morph ) {
			busy = this.stepMorph( performance.now() ) || busy;
		}

		// Stage volumes grow in and out.
		this.slots.forEach( ( slot ) => {
			const target = this.stageTarget( slot );

			if ( Math.abs( target - slot.present ) > 1e-3 ) {
				slot.present = still ? target : slot.present + ( target - slot.present ) * ( 1 - Math.exp( -dt * 12 ) );
				busy = true;
			} else {
				slot.present = target;
			}
		} );

		const breathing = ! still && this.slots.some( ( slot ) => slot.breath );

		// The baked contact shadow appears once the model has landed.
		this.contact.material.opacity = this.labelAlpha;

		// The trees and the people arrive once the model has landed, and go while it morphs into another.
		if ( this.treeSpots.length || this.peopleSpots.length ) {
			const target = this.assembling || this.morph ? 0 : 1;

			if ( Math.abs( target - this.treeK ) > 1e-3 ) {
				this.treeK = still ? target : this.treeK + ( target - this.treeK ) * ( 1 - Math.exp( -dt * 6 ) );
				this.drawScatter();
				busy = true;
			} else if ( this.treeK !== target ) {
				this.treeK = target;
				this.drawScatter();
			}
		}

		this.applySlots( now, breathing );
		this.model.rotation.y = this.rot + ( this.b.scrollOrbit ? this.progress * 1.5 * Math.PI : 0 );

		if ( this.tour ) {
			this.updateTour();
		}

		this.updateLabels();

		if ( this.dirty || busy || breathing ) {
			this.renderer.render( this.scene, this.camera );
			this.dirty = false;
		}

		return busy || breathing;
	}

	applySlots( now, breathing ) {
		const e = this.explodeValue * 1.6;
		const pulse = breathing ? 1 + 0.035 * Math.sin( now / 700 ) : 1;

		this.slots.forEach( ( slot ) => {
			const b = slot.base;
			const x = e && ! slot.ground ? e : 0;
			const k = slot.present * ( slot.breath ? pulse : 1 );

			slot.group.position.set( b.x + slot.dir.x * x, b.y + slot.dir.y * x + slot.drop + ( 1 - slot.present ) * 0.5, b.z + slot.dir.z * x );
			slot.group.rotation.y = b.ry;
			slot.group.scale.set( b.sx * k, b.sy * k, b.sz * k );
			slot.group.visible = k > 0.001 && slot.revealed;
		} );
	}

	/**
	 * Callout labels for the named parts: a pill beside and above each part, joined to the part's projected top
	 * centre by a dotted leader line ending in a dot. Shown at rest when the callouts behaviour is on (wide screens
	 * only) and whenever the model is coming apart.
	 */
	updateLabels() {
		const exploded = clamp( ( this.explodeValue - 0.25 ) / 0.35, 0, 1 );
		const rest = this.b.callouts && wideQuery.matches ? ( this.tour ? clamp( ( this.tk.real - 0.5 ) * 2, 0, 1 ) : 1 ) : 0;
		const amount = Math.max( exploded, rest ) * this.labelAlpha * this.morphFade;

		if ( amount <= 0 && ! this.labelsShown ) {
			return;
		}

		this.labelsShown = amount > 0;
		this.scene.updateMatrixWorld( true );

		// Pills sit outward from the middle of the model, so the callouts fan out around it.
		this.tmp.set( 0, 0.8, 0 ).project( this.camera );

		const cx = ( this.tmp.x * 0.5 + 0.5 ) * this.w;
		const cy = ( -this.tmp.y * 0.5 + 0.5 ) * this.h;
		const items = [];

		this.slots.forEach( ( slot ) => {
			if ( ! slot.label ) {
				return;
			}

			this.tmp.set( 0, 0.5, 0 );
			slot.group.localToWorld( this.tmp );
			this.tmp.project( this.camera );

			const shown = amount > 0 && slot.group.visible && this.tmp.z < 1;
			const opacity = shown ? String( amount ) : '0';

			slot.label.style.opacity = opacity;
			slot.line.style.opacity = opacity;
			slot.dot.style.opacity = opacity;

			if ( ! shown ) {
				return;
			}

			if ( ! slot.labelSize ) {
				slot.labelSize = { w: slot.label.offsetWidth || 120, h: slot.label.offsetHeight || 28 };
			}

			const { w, h } = slot.labelSize;
			const x = ( this.tmp.x * 0.5 + 0.5 ) * this.w;
			const y = ( -this.tmp.y * 0.5 + 0.5 ) * this.h;

			let nx = x - cx;
			let ny = y - cy;
			const len = Math.hypot( nx, ny ) || 1;

			nx /= len;
			ny /= len;

			const reach = 54 + 0.5 * ( Math.abs( nx ) * w + Math.abs( ny ) * h );

			items.push( { slot, x, y, w, h, px: x + nx * reach - w / 2, py: y + ny * reach - h / 2 } );
		} );

		// Push overlapping pills apart (a few relaxation passes), then keep them inside the figure.
		const inside = ( item ) => {
			item.px = clamp( item.px, 6, Math.max( 6, this.w - item.w - 6 ) );
			item.py = clamp( item.py, 6, Math.max( 6, this.h - item.h - 6 ) );
		};

		items.forEach( inside );

		for ( let pass = 0; pass < 6; pass++ ) {
			for ( let i = 0; i < items.length; i++ ) {
				for ( let j = i + 1; j < items.length; j++ ) {
					const a = items[ i ];
					const b = items[ j ];
					const overlapX = Math.min( a.px + a.w, b.px + b.w ) - Math.max( a.px, b.px ) + 8;
					const overlapY = Math.min( a.py + a.h, b.py + b.h ) - Math.max( a.py, b.py ) + 6;

					if ( overlapX > 0 && overlapY > 0 ) {
						const shift = overlapY / 2;
						const up = a.py + a.h / 2 <= b.py + b.h / 2 ? a : b;
						const down = up === a ? b : a;

						up.py -= shift;
						down.py += shift;
					}
				}
			}

			items.forEach( inside );
		}

		items.forEach( ( { slot, x, y, w, h, px, py } ) => {
			// The leader meets the pill at the edge nearest the point.
			let ex = clamp( x, px + h / 2, px + w - h / 2 );
			let ey = y < py ? py : py + h;

			if ( x < px || x > px + w ) {
				ex = x < px ? px : px + w;
				ey = py + h / 2;
			}

			slot.label.style.transform = `translate(${ Math.round( px ) }px, ${ Math.round( py ) }px)`;
			slot.line.setAttribute( 'x1', x.toFixed( 1 ) );
			slot.line.setAttribute( 'y1', y.toFixed( 1 ) );
			slot.line.setAttribute( 'x2', ex.toFixed( 1 ) );
			slot.line.setAttribute( 'y2', ey.toFixed( 1 ) );
			slot.dot.setAttribute( 'cx', x.toFixed( 1 ) );
			slot.dot.setAttribute( 'cy', y.toFixed( 1 ) );
		} );
	}

	dispose() {
		if ( ! views.has( this ) ) {
			return;
		}

		views.delete( this );
		this.observer?.disconnect();
		this.resizer?.disconnect();
		this.trigger?.kill();
		this.parts?.remove();
		this.dimsSvg?.remove();
		this.ghostMat?.dispose();
		this.canvas?.remove();
		this.contact?.material.dispose();
		this.envTarget?.dispose();
		this.mats.forEach( ( mat ) => mat.dispose() );
		this.renderer?.dispose();
		this.renderer?.forceContextLoss();
		this.el.formaView = null;

		if ( ! views.size ) {
			cache.geometry.forEach( ( geo ) => geo?.dispose() );
			cache.geometry.clear();
			cache.parts.forEach( ( parts ) => Object.values( parts ).forEach( ( geo ) => geo?.dispose() ) );
			cache.parts.clear();
			cache.material.forEach( ( mat ) => mat.dispose() );
			cache.material.clear();
			cache.textures.forEach( ( set ) => Object.values( set ).forEach( ( tex ) => tex?.dispose?.() ) );
			cache.textures.clear();
			hdriModule?.then( ( tex ) => tex?.dispose() );
			hdriModule = null;
			Object.values( cache.blank || {} ).forEach( ( tex ) => tex.dispose() );
			cache.blank = null;
			cache.contact?.dispose();
			cache.contact = null;
		}
	}
}

/* ------------------------------------------------------------------ mounting */

const fallback = ( el ) => el.classList.add( 'forma-model--fallback' );

const mount = ( el ) => {
	if ( ! el || el.formaView || el.dataset.formaMounted ) {
		return;
	}

	// An editor re-render replaces the figure; drop the views whose figures are gone.
	Array.from( views ).forEach( ( view ) => view.el.isConnected || view.dispose() );

	let data;

	try {
		data = JSON.parse( el.dataset.formaModel || '' );
	} catch ( error ) {
		return fallback( el );
	}

	if ( ! window.WebGLRenderingContext || views.size >= MAX_VIEWS ) {
		return fallback( el );
	}

	let view;

	try {
		view = new View( el, data );
	} catch ( error ) {
		return fallback( el );
	}

	el.formaView = view;
	el.dataset.formaMounted = '1';
	views.add( view );
	setupSwap( view );
	schedule();
};

/* ------------------------------------------------------------------ lazy mounting */

/*
 * A figure gets its renderer (a WebGL context, its shader programs and a scene) only when it is about to come on
 * screen. Every renderer compiles its own programs, so mounting all of a page's models at load put several of them on the
 * main thread before the first one had even been looked at. Until the visitor first scrolls (or touches, or presses a key)
 * only a figure that is really on screen is mounted (a quarter of it at least, so the stage of a tall section peeking in at
 * the fold is not); from then on the margin is generous so a model is ready well before it is seen. The editor, and browsers
 * without IntersectionObserver, mount straight away.
 */
const MOUNT_MARGIN = '600px 0px';
let mountObserver = null;
let firstLook = null;
let engaged = false;
const waiting = new Set();
const mountQueue = [];
let mountTimer = 0;

/** Mounts the queued figures one at a time, a moment apart, so two models never put their set-up in the same task. */
const drainMounts = () => {
	mountTimer = 0;

	const el = mountQueue.shift();

	if ( el ) {
		mount( el );
	}

	if ( mountQueue.length ) {
		mountTimer = window.setTimeout( drainMounts, 120 );
	}
};

const queueMount = ( el ) => {
	waiting.delete( el );
	mountQueue.push( el );

	if ( ! mountTimer ) {
		mountTimer = window.setTimeout( drainMounts, 0 );
	}
};

/** The visitor has started to look around: open the margin for everything still waiting. */
const engage = () => {
	if ( engaged ) {
		return;
	}

	engaged = true;
	[ 'scroll', 'wheel', 'touchstart', 'keydown' ].forEach( ( type ) => window.removeEventListener( type, engage, true ) );
	firstLook?.disconnect();
	waiting.forEach( ( el ) => mountObserver.observe( el ) );
};

const mountLater = ( el ) => {
	if ( ! el || el.formaView || el.dataset.formaMounted ) {
		return;
	}

	if ( editing() || ! ( 'IntersectionObserver' in window ) ) {
		mount( el );

		return;
	}

	if ( ! mountObserver ) {
		mountObserver = new IntersectionObserver(
			( entries ) =>
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						mountObserver.unobserve( entry.target );
						queueMount( entry.target );
					}
				} ),
			{ rootMargin: MOUNT_MARGIN }
		);
		firstLook = new IntersectionObserver(
			( entries ) =>
				entries.forEach( ( entry ) => {
					if ( entry.intersectionRatio >= 0.25 ) {
						firstLook.unobserve( entry.target );
						queueMount( entry.target );
					}
				} ),
			{ threshold: [ 0, 0.25 ] }
		);
		[ 'scroll', 'wheel', 'touchstart', 'keydown' ].forEach( ( type ) => window.addEventListener( type, engage, { capture: true, passive: true } ) );
	}

	waiting.add( el );
	( engaged ? mountObserver : firstLook ).observe( el );
};

/* ------------------------------------------------------------------ swapping from elsewhere on the page */

let hoverBound = false;
let scrollBound = false;
let scrollQueued = false;
const scrollers = new WeakMap();

const swapAll = ( mode, id ) => views.forEach( ( view ) => view.b.swap === mode && view.swapTo( id ) );

/**
 * The element that names the project for a pointer or focus on `node`: the closest marked ancestor or, inside a Nested
 * Accordion item, the marked panel of that item, so the item's header swaps the model too (the header is not an element
 * Elementor lets you mark).
 */
const swapSource = ( node ) => {
	if ( ! ( node instanceof Element ) ) {
		return null;
	}

	return node.closest( '[data-model-swap]' ) || node.closest( '.e-n-accordion-item' )?.querySelector( '[data-model-swap]' ) || null;
};

const onSwapIntent = ( event ) => {
	const target = swapSource( event.target );

	if ( target ) {
		swapAll( 'hover', target.dataset.modelSwap );
	}
};

/** The horizontal extent a swap element is read against: its clipping ancestor, else the window. */
const scrollerBox = ( node ) => {
	let box = scrollers.get( node );

	if ( box === undefined ) {
		box = null;

		for ( let parent = node.parentElement; parent && parent !== document.body; parent = parent.parentElement ) {
			if ( /(auto|scroll|hidden|clip)/.test( getComputedStyle( parent ).overflowX ) ) {
				box = parent;
				break;
			}
		}

		scrollers.set( node, box );
	}

	const rect = box ? box.getBoundingClientRect() : null;

	return rect ? { left: Math.max( 0, rect.left ), right: Math.min( window.innerWidth, rect.right ) } : { left: 0, right: window.innerWidth };
};

const swapByScroll = () => {
	scrollQueued = false;

	if ( ! Array.from( views ).some( ( view ) => view.b.swap === 'scroll' && view.visible ) ) {
		return;
	}

	let best = null;
	let bestDistance = Infinity;

	document.querySelectorAll( '[data-model-swap]' ).forEach( ( node ) => {
		const rect = node.getBoundingClientRect();

		if ( rect.bottom < 0 || rect.top > window.innerHeight || rect.right < 0 || rect.left > window.innerWidth ) {
			return;
		}

		const { left, right } = scrollerBox( node );
		const distance = Math.abs( rect.left + rect.width / 2 - ( left + right ) / 2 );

		if ( distance < bestDistance ) {
			best = node;
			bestDistance = distance;
		}
	} );

	if ( best ) {
		swapAll( 'scroll', best.dataset.modelSwap );
	}
};

const queueScrollSwap = () => {
	if ( ! scrollQueued ) {
		scrollQueued = true;
		window.requestAnimationFrame( swapByScroll );
	}
};

const setupSwap = ( view ) => {
	if ( view.b.swap === 'hover' && ! hoverBound ) {
		hoverBound = true;
		document.addEventListener( 'pointerover', onSwapIntent );
		document.addEventListener( 'focusin', onSwapIntent );
	}

	if ( view.b.swap === 'scroll' && ! scrollBound ) {
		scrollBound = true;
		// Capture catches scrolling inside carousels and scrollers as well as the window.
		document.addEventListener( 'scroll', queueScrollSwap, { passive: true, capture: true } );
		window.addEventListener( 'resize', queueScrollSwap );
		window.addEventListener( 'load', queueScrollSwap, { once: true } );
	}

	// A model mounted after the page has loaded starts on its own project; ask which one is in front now.
	if ( view.b.swap === 'scroll' ) {
		queueScrollSwap();
	}
};

/* ------------------------------------------------------------------ start up */

const boot = () => {
	document.querySelectorAll( '.forma-model[data-forma-model]' ).forEach( mountLater );

	// Inside Elementor's editor the widget re-renders on every change; mount (and dispose the old) each time.
	const hook = () =>
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/forma-study-model.default', ( $scope ) => {
			mountLater( $scope[ 0 ]?.querySelector( '.forma-model[data-forma-model]' ) );
		} );

	if ( window.elementorFrontend?.hooks ) {
		hook();
	} else if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', () => window.elementorFrontend?.hooks && hook() );
	}
};

window.FormaModel = { views };
wideQuery.addEventListener( 'change', () => views.forEach( ( view ) => view.invalidate() ) );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
} else {
	boot();
}
