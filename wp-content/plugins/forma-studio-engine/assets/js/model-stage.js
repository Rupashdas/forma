/**
 * FORMA — model stage.
 *
 * Draws every `.forma-model` figure as a Three.js study model: a list of volumes (boxes, gabled blocks, cylinders,
 * slabs and dashed outlines) on a plinth, lit by one soft key light. Each figure owns one small canvas (at most six
 * per page) and renders on demand. Behaviours come from the figure's `data-forma-model` JSON: assemble, drag, keyboard,
 * scroll orbit, exploded view, growth stages and swapping to another project's model.
 *
 * GSAP and ScrollTrigger (classic globals) only drive the scroll behaviours; without them those simply do not run.
 * Without WebGL the figure shows its photograph (class forma-model--fallback).
 */
import * as THREE from 'three';

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

/* ------------------------------------------------------------------ shared geometry, materials, textures */

const cache = { geometry: new Map(), material: new Map(), texture: null };

const geometry = ( kind ) => {
	let geo = cache.geometry.get( kind );

	if ( geo ) {
		return geo;
	}

	if ( kind === 'cylinder' ) {
		geo = new THREE.CylinderGeometry( 0.5, 0.5, 1, 48 );
	} else if ( kind === 'gable' ) {
		// A house profile: walls to 62% of the ridge height, then the pitched roof. Unit size, centred.
		const shape = new THREE.Shape();
		shape.moveTo( -0.5, 0 );
		shape.lineTo( 0.5, 0 );
		shape.lineTo( 0.5, 0.62 );
		shape.lineTo( 0, 1 );
		shape.lineTo( -0.5, 0.62 );
		shape.closePath();
		geo = new THREE.ExtrudeGeometry( shape, { depth: 1, bevelEnabled: false } );
		geo.translate( 0, -0.5, -0.5 );
	} else if ( kind === 'wire' ) {
		geo = new THREE.EdgesGeometry( new THREE.BoxGeometry( 1, 1, 1 ) );
		new THREE.LineSegments( geo, new THREE.LineBasicMaterial() ).computeLineDistances();
	} else if ( kind === 'plane' ) {
		geo = new THREE.PlaneGeometry( 1, 1 );
	} else {
		geo = new THREE.BoxGeometry( 1, 1, 1 );
	}

	cache.geometry.set( kind, geo );

	return geo;
};

const material = ( name ) => {
	let mat = cache.material.get( name );

	if ( mat ) {
		return mat;
	}

	const standard = ( color, extra = {} ) => new THREE.MeshStandardMaterial( { color, roughness: 0.9, metalness: 0, ...extra } );

	if ( name === 'shade' ) {
		mat = standard( '#C6C4BA' );
	} else if ( name === 'ink' ) {
		mat = standard( '#1A1A19', { roughness: 0.75 } );
	} else if ( name === 'glass' ) {
		mat = standard( '#FBFAF6', { transparent: true, opacity: 0.35, depthWrite: false, roughness: 0.4 } );
	} else if ( name === 'wire' ) {
		mat = new THREE.LineDashedMaterial( { color: '#2B3BFF', dashSize: 0.045, gapSize: 0.03 } );
	} else if ( name === 'shadow' ) {
		mat = new THREE.ShadowMaterial( { opacity: 0.2 } );
	} else {
		mat = standard( '#FBFAF6' );
	}

	cache.material.set( name, mat );

	return mat;
};

/** A soft elliptical contact shadow, baked once, for small screens where real shadows are off. */
const contactShadow = () => {
	if ( ! cache.texture ) {
		const canvas = document.createElement( 'canvas' );
		canvas.width = canvas.height = 128;
		const ctx = canvas.getContext( '2d' );
		const grad = ctx.createRadialGradient( 64, 64, 8, 64, 64, 64 );
		grad.addColorStop( 0, 'rgba(0,0,0,0.34)' );
		grad.addColorStop( 0.55, 'rgba(0,0,0,0.16)' );
		grad.addColorStop( 1, 'rgba(0,0,0,0)' );
		ctx.fillStyle = grad;
		ctx.fillRect( 0, 0, 128, 128 );
		cache.texture = new THREE.CanvasTexture( canvas );
		cache.texture.colorSpace = THREE.SRGBColorSpace;
	}

	return cache.texture;
};

const makeBody = ( slot ) => {
	if ( slot.wire ) {
		const line = new THREE.LineSegments( geometry( 'wire' ), material( 'wire' ) );
		line.frustumCulled = false;

		return line;
	}

	const mesh = new THREE.Mesh( geometry( slot.v.kind === 'slab' ? 'box' : slot.v.kind ), material( slot.v.material ) );
	mesh.castShadow = slot.v.material !== 'glass';
	mesh.receiveShadow = slot.v.material !== 'glass';

	return mesh;
};

/** The logical transform of a volume: its centre, rotation and scale. */
const baseOf = ( v ) => ( { x: v.x, y: v.y + v.h / 2, z: v.z, ry: v.rot * DEG, sx: v.w, sy: v.h, sz: v.d } );

/* ------------------------------------------------------------------ one volume of a model */

class Slot {
	constructor( v ) {
		this.group = new THREE.Group();
		this.body = null;
		this.key = '';
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

	/** Take on a volume's kind, material, label and stage, swapping the body when the look changes. */
	set( v ) {
		this.v = v;
		this.part = v.part;
		this.stage = v.stage;
		this.ground = v.kind === 'slab' && v.y < 0.02 && ! v.part && ! v.stage;
		this.wire = v.kind === 'wire' || v.material === 'wire';

		const key = this.wire ? 'wire' : `${ v.kind === 'slab' ? 'box' : v.kind }:${ v.material }`;

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

		this.buildScene();
		this.build( data.volumes || [] );
		this.bind();
		this.setupScroll();
	}

	/* ---- scene ---- */

	buildScene() {
		const small = window.innerWidth < 768;

		this.renderer = new THREE.WebGLRenderer( { alpha: true, antialias: true, powerPreference: 'default' } );
		this.renderer.setClearColor( 0x000000, 0 );
		this.renderer.outputColorSpace = THREE.SRGBColorSpace;
		this.renderer.shadowMap.enabled = ! small;
		this.renderer.shadowMap.type = THREE.PCFShadowMap;
		this.canvas = this.renderer.domElement;
		this.canvas.setAttribute( 'aria-hidden', 'true' );
		this.el.insertBefore( this.canvas, this.el.firstChild );

		this.scene = new THREE.Scene();
		this.camera = new THREE.PerspectiveCamera( 28, 1.6, 0.1, 100 );
		this.model = new THREE.Group();
		this.scene.add( this.model );

		this.scene.add( new THREE.HemisphereLight( 0xffffff, 0xc9c7bf, 2.4 ) );

		const key = new THREE.DirectionalLight( 0xffffff, 2.6 );
		key.position.set( 6, 12, 6 );

		if ( ! small ) {
			key.castShadow = true;
			key.shadow.mapSize.set( 1024, 1024 );
			key.shadow.camera.left = key.shadow.camera.bottom = -7;
			key.shadow.camera.right = key.shadow.camera.top = 7;
			key.shadow.camera.near = 1;
			key.shadow.camera.far = 40;
			key.shadow.bias = -0.0004;
			key.shadow.normalBias = 0.03;
			key.shadow.radius = 4;
		}

		this.scene.add( key );

		const ground = new THREE.Mesh( geometry( 'plane' ), small ? new THREE.MeshBasicMaterial( { map: contactShadow(), transparent: true, depthWrite: false } ) : material( 'shadow' ) );
		ground.rotation.x = -Math.PI / 2;
		ground.position.y = 0.002;
		ground.scale.set( small ? 10 : 60, small ? 7.4 : 60, 1 );
		ground.receiveShadow = ! small;
		this.scene.add( ground );
		this.ground = ground;
		this.small = small;

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
		const slot = new Slot( v );
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

		this.buildLabels();
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
			this.invalidate();
		} );

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
	}

	invalidate() {
		this.dirty = true;
		schedule();
	}

	/** Advance and draw one frame. Returns whether another is needed. */
	update( now ) {
		if ( ! this.visible || ! this.started || this.lost || ! this.w ) {
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

		if ( ! still && ! scrolled && ! this.dragging && this.explodeValue < 0.02 && Math.abs( this.velocity ) < 1e-3 ) {
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

		this.explodeValue = Math.max( toggle, this.b.scrollExplode ? this.progress : 0 );

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

		// The baked contact shadow (small screens) appears once the model has landed.
		if ( this.small ) {
			this.ground.material.opacity = this.labelAlpha;
		}

		this.applySlots( now, breathing );
		this.model.rotation.y = this.rot + ( this.b.scrollOrbit ? this.progress * 1.5 * Math.PI : 0 );
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
		const rest = this.b.callouts && wideQuery.matches ? 1 : 0;
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
		this.canvas?.remove();
		this.small && this.ground?.material.dispose();
		this.renderer?.dispose();
		this.renderer?.forceContextLoss();
		this.el.formaView = null;

		if ( ! views.size ) {
			cache.geometry.forEach( ( geo ) => geo.dispose() );
			cache.geometry.clear();
			cache.material.forEach( ( mat ) => mat.dispose() );
			cache.material.clear();
			cache.texture?.dispose();
			cache.texture = null;
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
};

/* ------------------------------------------------------------------ start up */

const boot = () => {
	document.querySelectorAll( '.forma-model[data-forma-model]' ).forEach( mount );

	// Inside Elementor's editor the widget re-renders on every change; mount (and dispose the old) each time.
	const hook = () =>
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/forma-study-model.default', ( $scope ) => {
			mount( $scope[ 0 ]?.querySelector( '.forma-model[data-forma-model]' ) );
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
