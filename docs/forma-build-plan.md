# FORMA — Elementor Pro Architecture Studio: Spec & Build Plan

> Supersedes `forma-elementor-architecture-studio-planning.md` (kept for reference). Approved 2026-10-03.
> Changes from the original: ProElements instead of Elementor Free, no custom meta fields (native WordPress data +
> per-project Elementor bodies), the existing scaffold is rebuilt rather than patched, a named visual direction, a
> reproducible WP-CLI build, and InfinityFree deployment.

## 1. Goal

A flagship studio site for the portfolio at devrupash.com that proves, in a two-minute look, that the developer can
build an **agency-grade editorial site in Elementor Pro and extend Elementor itself with code** — while every page
stays editable by someone who never opens the code.

What Forma proves that Arvale (Elementor Pro + WooCommerce) does not:

- Custom post type + taxonomies driving Theme Builder templates, Loop Grid and Taxonomy Filter.
- **Extending native Elementor widgets** with a custom control panel (Forma Motion), not just adding new widgets.
- GSAP + ScrollTrigger storytelling that is purposeful, conditional and reduced-motion safe.
- Premium editorial art direction that does not read as a template.

Non-goals: WooCommerce, a blog/journal, multilingual, an SPA-style router, Three.js/WebGL, custom meta fields.

## 2. Decisions

| Topic | Decision | Why |
|---|---|---|
| Brand | **FORMA** — "Architecture · Interiors · Objects", fictional studio based in Lisbon, est. 2011 | Plan brief |
| Builder | Elementor 4.2 + **ProElements 4.2** (GPL Pro build), Hello Elementor + `forma-hello-child` | Theme Builder, Loop Grid, Taxonomy Filter, Popups, Counter, Gallery, Sticky |
| Elementor elements | Classic containers + widgets (not the V4 atomic editor) | Pro widgets and Theme Builder are classic; stable API |
| Content data | **No custom meta fields.** Title, excerpt, featured image, post date, two taxonomies; everything else in each project's Elementor body | Owner's call: content is edited visually; nothing needs a field |
| Fallback if a field ever becomes necessary | Secure Custom Fields, registered in code | Owner's call |
| Custom code | `forma-studio-engine` plugin, rebuilt: PSR-4 autoloader, modules, no build step | Existing scaffold has a fatal (`get_post_ID()`), deprecated Elementor hooks, always-on asset loading |
| Motion | GSAP 3.13+ (ScrollTrigger, SplitText) self-hosted, loaded only on pages that use it | Free since 3.13; no third-party request |
| Page transitions | CSS cross-document **View Transitions**, no JS; project image morphs card → hero | Non-blocking, progressive; unsupported browsers navigate normally |
| Forms | Contact Form 7 + **Defer Forms for CF7** + **Nice Select for WP** (owner's own plugins) | Shows the owner's WordPress.org plugins in a real build |
| SEO | Small SEO module in the plugin (meta description, OG/Twitter, JSON-LD) | ~1,500 fewer inodes than an SEO plugin; shows skill |
| Images | Curated Unsplash/Pexels, **each batch approved before download**, credits in attachment meta and on a Colophon page | Legal licence, consistent art direction |
| People | Team shown typographically (name, role, year joined) plus studio/workspace photos | No stock faces presented as staff |
| Awards / press | Fictional award and publication names only | No misattribution to real organisations |
| Hosting | InfinityFree, **forma.freedev.app** (same as arvale.freedev.app) | See §13 |

## 3. Plugin & theme stack (final)

Keep: Elementor, ProElements, Contact Form 7, Defer Forms for CF7, Nice Select for WP, **forma-studio-engine**,
Hello Elementor + **forma-hello-child**.

Deactivate now, exclude from deploy (inode budget + focus): Essential Addons, Essential Blocks, Templately,
Twenty Twenty-Three/Four/Five. Deleting them locally only with the owner's confirmation.

Inode estimate: core 3.3k + Elementor 3.0k + ProElements 1.3k + forms ~0.4k + theme/plugin ~0.2k + uploads ~0.5k
≈ **9k** of InfinityFree's ~30k.

## 4. Content model

| Data | Stored as | Used by |
|---|---|---|
| Project name | Post title | Cards, index, single H1 |
| Standfirst (1–2 sentences) | Excerpt | Cards (hover), single standfirst, meta description |
| Hero / card image | Featured image | Cards, single hero, OG image |
| Type | Taxonomy `project_type` (hierarchical): Residential, Interiors, Hospitality, Workplace, Cultural, Objects | Taxonomy Filter, card label, term archive `/projects/type/{slug}/` |
| Location | Taxonomy `project_location` (flat): "Kyoto, Japan" | Card, single title block |
| Year | **Post date = completion date** | Card, title block ("Y" format), default chronological order |
| Project number ("No. 07") | Custom dynamic tag: chronological position, zero-padded | Single title block, index |
| Featured on Home | Loop Grid query → Manual selection | Home showcase |
| Client, area, status, team, narrative, gallery, drawing/built comparison, awards | **The project's Elementor body** | Single page |

CPT `forma_project`: rewrite slug `projects`, archive `/projects/`, supports title, editor, excerpt, thumbnail,
revisions; `show_in_rest`; added to Elementor's CPT support. Internal seed bookkeeping (`_forma_seed` on seeded
posts/templates, `_forma_credit` on attachments) is hidden meta, not content fields.

### Project roster (11)

| No. | Project | Type | Location | Year | Notes |
|---|---|---|---|---|---|
| 01 | Forma Pavilion | Cultural | Venice, Italy | 2019 | Biennale timber pavilion |
| 02 | Concrete Garden | Cultural | Lisbon, Portugal | 2020 | Public garden + pavilion, drawing/built |
| 03 | Terra Residence | Residential | Mallorca, Spain | 2021 | Rammed-earth house |
| 04 | Atelier 27 | Interiors | Copenhagen, Denmark | 2022 | Warehouse → ceramics studio, drawing/built |
| 05 | House of Light | Interiors | Oslo, Norway | 2022 | Apartment renovation |
| 06 | Monolith House | Residential | Isle of Harris, Scotland | 2023 | Stone house on the coast |
| 07 | Axis Workspace | Workplace | Rotterdam, Netherlands | 2023 | 2,150 m² office |
| 08 | Casa Nera | Residential | Comporta, Portugal | 2024 | Black timber house, drawing/built |
| 09 | The Quiet Hotel | Hospitality | Kyoto, Japan | 2025 | 24-room hotel |
| 10 | Plinth Series | Objects | Lisbon, Portugal | 2025 | Travertine furniture collection |
| 11 | Northline Residence | Residential | Tromsø, Norway | 2026 | Status: in construction |

Each project: realistic client, area, status, team, three-part narrative (The site / The idea / The result),
6 images (featured + 5). No lorem ipsum anywhere on the site.

## 5. Site map & Elementor templates

**Theme Builder**

| Template | Condition | Contents |
|---|---|---|
| Header | Entire site | Wordmark · coordinates "38°43′N 9°08′W" · nav (Projects, Studio, Services, Process, Contact) · below 1024px a Menu button → Popup |
| Footer | Entire site | Ink band: giant FORMA wordmark (char reveal) · address · email · nav · social · credit line + Colophon link |
| Menu popup | Opened by the header Menu button | Full-screen Ink sheet, numbered nav in Bodoni, address, close; Esc closes, focus returns |
| Single Project | `forma_project` singular | §7.3 |
| Projects Archive | `forma_project` archive + `project_type` terms | §7.2 |
| 404 | 404 | "This room hasn't been built yet." + 3 project links |

**Saved reusable components** (Pro *Template* widget, edit once → updates everywhere): Closing CTA, Recognition
ledger (Home + Studio), Project card (Loop Item), Project card wide (alternate Loop Item).

**Pages:** Home · Projects (archive) · Studio · Services · Process · Contact · Colophon (credits, fonts, privacy, demo
disclosure). Menus: Primary, Footer.

## 6. Visual design system — "Drawing Set"

The site reads like an architect's drawing set: a hairline column grid, sheet numbering, title blocks for facts,
figure captions ("Fig. 03 — Entrance court"), restrained red-pen markers.

**Colours (Elementor Global Colors)**

| Token | Hex | Use |
|---|---|---|
| Plaster | `#E6E4DF` | Page |
| Paper | `#F3F2EF` | Raised surfaces, form fields |
| Ink | `#121212` | Text, dark sections, footer |
| Graphite | `#5C5B57` | Secondary text (≈5.3:1 on Plaster) |
| Line | `rgba(18,18,18,.16)` (`#E6E4DF` on Ink: `rgba(230,228,223,.18)`) | Hairlines |
| Redline | `#B8321E` | Markers, active filter, focus ring, cursor — never large fills |

**Type** — self-hosted variable woff2 in the child theme, registered with Elementor as a "Forma" font group; Google
Fonts loading disabled.

| Global Font | Family | Size | Line / tracking |
|---|---|---|---|
| Display XL (wordmark, hero) | Bodoni Moda 400 | clamp(72px, 18vw, 320px) | .85 / -0.04em |
| Display L (page H1) | Bodoni Moda 400 | clamp(48px, 8vw, 144px) | .92 / -0.03em |
| Heading (H2) | Bodoni Moda 400 | clamp(36px, 4.5vw, 72px) | 1.0 / -0.02em |
| Statement | Bodoni Moda 400 (italic for single accent words) | clamp(26px, 3vw, 44px) | 1.2 |
| Subheading (H3) | Archivo 500 | clamp(20px, 1.8vw, 26px) | 1.25 |
| Body | Archivo 400 | 17px (16px mobile) | 1.6, max 68ch |
| Label | Archivo 500, width 125, uppercase | 12px | 1.3 / 0.08em |
| Meta | Archivo 400 | 13px | 1.4 |

**Layout:** full width with gutter clamp(16px, 3.2vw, 48px); 12-column grid; section padding clamp(80px, 12vw, 200px).
Radius 0 everywhere. Buttons: 1px Ink outline, Label type, arrow that slides on hover; text links underline from left.
Imagery: no rounded corners, no shadows, no gradients; captions in Label/Meta style.

**Breakpoints (Elementor):** Mobile ≤767 · Tablet ≤1023 · Laptop ≤1439 · Desktop 1440+. Each section below states
its mobile behaviour; layouts are redesigned, not shrunk.

## 7. Page specifications

### 7.1 Home

| # | Section | Build | Motion | Mobile |
|---|---|---|---|---|
| 1 | **Hero** — FORMA wordmark full width; "Architecture / Interiors / Objects" stacked labels left; title block right (Lisbon · est. 2011 · coordinates); inset image; "Scroll" marker | Native containers, Heading, Image | Wordmark chars rise; labels lines; image clip-reveal on load, then **expands from inset to full-bleed** scrubbed over the hero | Wordmark 18vw, labels in one row, image 4:5 below text, no scrub |
| 2 | **Statement** — one large paragraph + "About the studio" link | Text in Statement font | Line-by-line masked reveal | Same, smaller |
| 3 | **Selected work** — 5 projects, asymmetric: wide feature, offset pair, full-bleed, narrow + caption | Loop Grid (Manual selection) + alternate template with column span | Images clip-wipe in; parallax on full-bleed; cursor "View" | Single column, alternating 4:5 / 3:2 |
| 4 | **Marquee** — "Architecture — Interiors — Objects —" | Forma Marquee | CSS loop, pause on hover/focus | Same, slower |
| 5 | **The Making of a Space** — 6 panels: survey, sketch, model, drawing, site, light (image, No., label, one line) | Forma Scroll Story (horizontal) on Ink | Pinned horizontal scroll with progress rail; cursor "Scroll" | Vertical stack, no pin |
| 6 | **Practice** — 6 services as a numbered typographic list, links to Services | Native containers | Rows fade up, staggered | Stacked |
| 7 | **Index** — all 11 projects: No., name, type, location, year | Forma Project Index | Image follows pointer on row hover; keyboard focus shows it beside the row | Inline thumbnail per row |
| 8 | **Recognition** — ledger of fictional awards (year, award, project) | Saved Recognition ledger | Rows fade up | Two-line rows |
| 9 | **Quote** — client quote in Statement italic | Native | Word reveal | Same |
| 10 | **Closing CTA** — "Let's build something quiet." + email + button | Saved Closing CTA | Line reveal | Same |

### 7.2 Projects (archive template)

- H1 "Projects" + count + one-line intro; title-block row.
- **Taxonomy Filter** (project_type) — Label style, active term underlined in Redline, URL-addressable.
- **Loop Grid**, 2 columns, alternate template every 3rd item spanning both columns (landscape), portrait cards
  otherwise → editorial rhythm, not a 3-column card grid. **Load more** on click, 6 per page.
- Card: image (View Transition name), No., title, type · location · year; hover: image scales 3%, excerpt slides in;
  cursor "View". Touch: excerpt hidden, whole card is one link.
- Term archives reuse the template; H1 becomes the term name.

### 7.3 Single Project (Theme Builder)

1. Title block: No. · Type · Location · Year (dynamic tags, hairline grid).
2. H1 title (Display L) + excerpt standfirst.
3. Hero: featured image, full-bleed, **morphs from the card via View Transitions**; clip-reveal when arriving directly.
4. **Post Content** — the project's own Elementor body, seeded from one of three layout variants so projects
   don't repeat:
   - Facts table (Client / Area / Status / Team / Photography) — 5 columns → 2 on mobile.
   - The site / The idea — two columns.
   - Full-bleed image with parallax; image pair (portrait + landscape, offset).
   - Pull quote; Gallery (Pro Gallery, justified, lightbox, captions).
   - **Drawing / Built** comparison on 3 projects (Before/After widget; the "drawing" is a line rendering generated
     locally from the photo by the seeder).
   - The result + recognition line.
5. **Next Project** — full-bleed image + huge title, wraps to the first; cursor "Next".
6. Closing CTA (saved).

### 7.4 Studio
H1 statement + studio photo · Philosophy: 3 numbered principles in hairline columns · Numbers: 4 Pro Counters
(15 years, 64 built works, 11 countries, 23 people) · Team: typographic table (8 rows) + 2 workspace photos ·
Approach text + image · Recognition ledger (saved) · Press list (fictional publications) · Closing CTA.

### 7.5 Services
H1 + intro · Six services (Architecture, Interior Design, Hospitality, Workplace, Art Direction, Furniture & Objects)
as a Nested Accordion list (number, name, summary; panel: deliverables + typical timeline) beside a **sticky** image
column (Pro sticky) · Ways to work with us: Full service / Interiors only / Feasibility study · FAQ (Nested Accordion,
FAQPage JSON-LD from the SEO module) · Closing CTA.

### 7.6 Process
H1 "Six stages, one conversation." · **Scroll Story — sticky-steps layout** on Ink: Discovery, Concept, Development,
Documentation, Construction, Completion — sticky stage number + progress rail, each with duration, what happens,
what you receive, image · Closing CTA. Mobile: plain vertical sequence, rail hidden.

### 7.7 Contact
H1 "Start a conversation." · giant email link · CF7 inquiry form (name, email, project type [Nice Select], budget
range [Nice Select], location, timeline, message, consent) deferred by Defer Forms · studio address, hours, phone,
social · static location image (no map embed) · Colophon link. Labels visible, errors announced, success message.

### 7.8 404 and Colophon
404 per §5. Colophon: typefaces, photography credits (generated from attachment credits), privacy note, and the
statement that FORMA is a fictional studio designed and built by Rupash Das.

## 8. `forma-studio-engine` architecture

```text
forma-studio-engine/
├── forma-studio-engine.php      # header, constants, requirement checks, autoloader, boot
├── uninstall.php                # removes nothing user-created; clears plugin options only
├── readme.txt
├── src/
│   ├── Plugin.php               # registers modules whose dependencies exist
│   ├── Autoloader.php           # PSR-4 for Forma\Engine\
│   ├── Contracts/Module.php     # is_available(), register()
│   ├── Support/Assets.php       # register everything, enqueue on demand
│   ├── Projects/                # PostType, Taxonomies, ProjectNumber helper
│   ├── Elementor/
│   │   ├── Module.php           # widget category "Forma", widgets, tags, extension
│   │   ├── Widgets/             # ScrollStory, ProjectIndex, BeforeAfter, Marquee, NextProject
│   │   ├── Tags/ProjectNumber.php
│   │   └── Motion/Extension.php # "Forma Motion" controls on every element
│   ├── Motion/                  # enqueues GSAP + runtime only when an element used Forma Motion
│   ├── Cursor/                  # follower + labels, fine pointers only
│   ├── Transitions/             # @view-transition CSS, per-project transition names
│   ├── Media/                   # trims image sizes, WebP on upload, eager/fetchpriority for hero
│   ├── Seo/                     # meta description, OG/Twitter, JSON-LD (Organization, WebSite, CreativeWork,
│   │                            # BreadcrumbList, FAQPage)
│   ├── Seed/
│   │   ├── Setup.php            # options, permalinks, Elementor settings/breakpoints, CPT support
│   │   ├── Content.php          # terms, 11 projects, pages, menus
│   │   ├── Images.php           # imports approved files from the manifest, credits, featured images
│   │   ├── Drawings.php         # line renderings for Drawing / Built
│   │   └── Elementor/           # Builder.php (ported from Arvale) + one file per document:
│   │                            # Kit, Header, Footer, MenuPopup, Components, Home, ProjectsArchive,
│   │                            # SingleProject, ProjectBodies, Studio, Services, Process, Contact, NotFound, Colophon
│   └── Cli/Command.php          # wp forma setup|content|images|drawings|design [--only=…]|all|reset
├── assets/css/  assets/js/      # one file per widget/module, vanilla JS, no jQuery
├── assets/vendor/gsap/          # gsap, ScrollTrigger, SplitText (minified)
└── data/projects.php            # project copy and layout variant; data/images.php (manifest + credits)
```

Rules: no `wp_ajax` endpoints needed (Pro's Loop Grid/Taxonomy Filter handle loading); every widget setting
sanitised/escaped on output; `defined( 'ABSPATH' ) || exit;` everywhere; modules degrade to native Elementor when JS is
off; no asset loads on a page that doesn't render its widget; seeding is idempotent and safe to rerun.

### 8.1 Custom widgets (only what Pro lacks)

| Widget | Controls | Behaviour & accessibility |
|---|---|---|
| **Scroll Story** | Layout (horizontal pin / sticky steps), repeater (image, number, label, title, text, meta), progress rail, Ink/Plaster | Horizontal: pinned ScrollTrigger, panel width responsive; sticky steps: progress follows active step. Real `<ol>`; content fully readable with JS off, reduced motion or <1024px (vertical) |
| **Project Index** | Query (all / type / manual), order, columns shown, image size | `<ol>` of links; pointer-following image (desktop, fine pointer); focus shows image beside row; touch shows thumbnails; `aria-hidden` on decorative preview |
| **Before / After** | Two images, labels, start position, orientation | Native `<input type="range">` overlay (keyboard, touch, screen-reader value "Drawing 40%"); both images have alt |
| **Marquee** | Items, separator, speed, direction, size, pause on hover | CSS transform loop, duplicated copy `aria-hidden`; paused for reduced motion; pause on hover/focus |
| **Next Project** | Label, image size, wrap to first | Server-side next-by-date lookup; whole block is one link with accessible name "Next project: Casa Nera" |

Custom dynamic tag: **Project Number** ("07"), used in templates.

### 8.2 Forma Motion extension

Adds a **Forma Motion** section to the Advanced tab of containers and every widget:

| Control | Values |
|---|---|
| Entrance | none, fade-up, lines, words, chars (SplitText with masks), clip-up, clip-left (image wipe), scale-in |
| Scroll effect | none, parallax (speed −30…30), expand (inset → full-bleed, scrubbed) |
| Delay / stagger | seconds |
| Cursor label | text (e.g. View, Drag, Next) |
| Shared transition | project image (adds the View Transition name for the current project) |

Rendering adds `data-fm*` attributes; the Motion module enqueues GSAP + `motion.js` only when at least one element
on the page rendered with Forma Motion. Runtime rules: an inline head snippet adds `fm-ready` only when JS runs and
motion is allowed, so content is never hidden if JS fails; `prefers-reduced-motion` → no runtime, no parallax, no
pin; disabled inside the Elementor editor except for a static preview; ScrollTrigger refresh after fonts/images
load; animations use transform/opacity/clip-path only.

### 8.3 Cursor, transitions, header

- Cursor: native cursor stays visible; an 8px follower trails it and expands to an 88px Redline disc with the label
  over `[data-cursor]` targets. Only for `(hover: hover) and (pointer: fine)`; no trailing for reduced motion.
- Transitions: `@view-transition { navigation: auto; }`, 300ms root crossfade, project image morph; disabled for
  reduced motion.
- Header (theme `site.js`, ~20 lines): condenses after 60px, hides on scroll down, shows on scroll up and on focus.

## 9. Child theme `forma-hello-child`

Holds: `style.css` header, `assets/css/site.css` (tokens mirrored from the Kit, base typography, focus ring, link
and button treatments, CF7/Nice Select styling, hairline grid utility), `assets/fonts/` + font registration with
Elementor, `assets/js/site.js` (header behaviour, `fm-ready` snippet). Image sizes belong to the plugin's Media
module. Removes the old
`archive-forma_project.php` and `single-forma_project.php` (Theme Builder owns templates). No CPTs, widgets,
GSAP or business logic.

## 10. Images

- About 80 unique photos: 11 projects × 6, plus home/studio/contact ~14; Services and Process reuse project/studio
  images.
- Workflow per batch: proposed list (project, source page, photographer, licence, size) → **owner approves** →
  downloaded to `Local Sites/forma/images-src/` (outside the web root) → `wp forma images` imports, resizes the
  original to max 2400px, converts to WebP, stores credit, sets featured images and alt text.
- Sizes kept: thumbnail, 480, 960, 1600, 2400 (custom); 1536/2048/medium_large disabled.
- Hero/LCP image eager with `fetchpriority="high"`; everything else lazy; explicit width/height everywhere (no CLS).

## 11. Quality bars

- **Accessibility:** one H1 per page, no skipped levels, skip link, visible Redline focus ring, labelled controls,
  accessible popup (Esc, focus return), AA contrast, touch targets ≥44px, reduced-motion parity.
- **Performance:** Elementor optimized DOM/asset loading, inline SVG icons (Font Awesome disabled), Google Fonts
  disabled, conditional GSAP. Lighthouse measured locally: Desktop Perf 90+, Mobile Perf 75+, A11y 95+, BP 95+
  (HTTPS excepted locally), SEO 100. Scores reported as measured.
- **SEO:** meta descriptions from excerpts, OG image from featured image, `/projects/{slug}/` URLs, breadcrumbs in
  JSON-LD, sitemap from WordPress core.
- **Security:** sanitise input, escape output, capability checks, no unauthenticated write paths.
- **Responsive:** checked at 375, 768, 1280, 1440 on every template; no horizontal overflow.

## 12. Build phases

| # | Phase | Output | Verify |
|---|---|---|---|
| 0 | Environment | Local site started, ProElements copied from the Arvale site and active, unused plugins deactivated, WP-CLI working | wp-admin loads, `wp plugin list` |
| 1 | Plugin + theme skeleton | Rebuilt `forma-studio-engine` boot/autoloader/modules, child theme base | `php -l` all files, activation with `WP_DEBUG` on: no notices |
| 2 | Content model + copy | CPT, taxonomies, 11 projects, pages, menus via `wp forma content` | Counts via CLI; `/projects/casa-nera/` resolves |
| 3 | Fonts + GSAP (approved downloads) | Fonts in theme, GSAP in plugin vendor | Network panel: no Google Fonts requests |
| 4 | Images (approved batches) | ~80 WebP images attached with credits; drawings generated | Media count, inode estimate |
| 5 | Widgets, Motion, Cursor, Transitions, SEO | 5 widgets, extension, modules | Test page per widget in the editor and front end; reduced motion; no console errors |
| 6a | Kit + Header/Footer/Popup + **Home** | Design system in Elementor, Home complete | **Owner visual review** at desktop + mobile |
| 6b | Projects archive + single + 11 project bodies | All project URLs from templates | Filter, load more, morph transition, next-project wrap |
| 6c | Studio, Services, Process, Contact, 404, Colophon | Every URL in §5 | CF7 submits locally |
| 7 | QA | Responsive, keyboard, reduced motion, a11y audit, Lighthouse, PHP log + console clean | Results recorded in this file |
| 8 | Deploy | forma.freedev.app | Live smoke test of every template |
| 9 | Portfolio | `content/projects/forma.md` + screenshots in `my-portfolio` | Owner reviews before publishing |

## 13. InfinityFree deployment

- No SSH/WP-CLI on the host → everything is seeded locally.
- DB: `wp search-replace http://forma.local https://forma.freedev.app --export` (plus the JSON-escaped
  `http:\/\/forma.local` form Elementor stores) → SQL split into <10 MB parts → owner imports via phpMyAdmin.
- Files: owner uploads by FTP; exclude `docs/`, `images-src/`, deactivated plugins and default themes.
- After import: regenerate Elementor CSS, resave permalinks, check `wp-config.php` DB credentials (owner enters them).
- CPU/entry-process limits: no heavy cron; browser caching headers in `.htaccess`.

## 14. Open items needing the owner

1. Start the Forma site in Local (and stop Arvale's if ports clash).
2. Approve downloads: Bodoni Moda + Archivo (Google Fonts), GSAP (cdnjs/npm), then each image batch.
3. Confirm deleting (not just deactivating) Essential Addons, Essential Blocks and Templately locally.
4. Optional: a Git repository (`Rupashdas/forma`, theme + plugin + docs only), as Arvale has.
5. Phase 8: InfinityFree account, forma.freedev.app subdomain, DB credentials, FTP upload.

## 15. Definition of done

Looks like a real premium studio, not a template · every page editable in Elementor · projects fully dynamic through
the CPT, taxonomies, Loop Grid and Theme Builder · Forma Motion works on native widgets and respects reduced motion ·
child theme / plugin / Elementor responsibilities stay separate · mobile designed, not shrunk · quality bars in §11
met or honestly reported · live on forma.freedev.app · case study added to devrupash.com.
