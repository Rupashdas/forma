# FORMA — Premium Architecture & Interior Studio
## Elementor Portfolio Project — Build Planning Specification

**Project Type:** Premium Architecture / Interior Design Studio Website  
**Primary Goal:** Build a flagship Elementor portfolio project that demonstrates advanced Elementor development, responsive layout engineering, dynamic WordPress content, custom CSS, animation, performance, and reusable component architecture.

**Brand:** FORMA  
**Positioning:** Architecture / Interior / Spaces / Objects  
**Design Direction:** Minimal, editorial, architectural, premium, Awwwards-inspired  
**Primary Builder:** Elementor  
**Recommended Base Theme:** Hello Elementor  
**Elementor Target:** Elementor Free first; architecture should remain compatible with Elementor Pro if Pro is later available.

---

# 1. Portfolio Objective

This project should NOT look like a generic Elementor template.

The goal is to demonstrate that the developer can:

- Build a complete premium website with Elementor.
- Create complex responsive layouts.
- Build reusable design systems.
- Use WordPress dynamically rather than hard-coding every project.
- Integrate ACF and custom post types.
- Create custom Elementor widgets when the free version is insufficient.
- Build subtle, purposeful GSAP interactions.
- Maintain performance and accessibility.
- Separate presentation from functionality using a child theme and companion plugin where appropriate.

The finished project should be suitable for:

- Elementor portfolio
- Fiverr portfolio
- Upwork proposals
- Personal developer portfolio
- LinkedIn case study
- Job applications for Elementor/WordPress developer roles

---

# 2. Recommended WordPress Architecture

## Core

- WordPress
- Hello Elementor
- Elementor Free
- Advanced Custom Fields (ACF)
- Custom Post Type for Projects

## Optional

- Elementor Pro
- SEO plugin
- Image optimization plugin
- Cache/performance plugin

## Custom Development

Create a custom child theme only where theme-level presentation or template overrides are genuinely required.

Create a companion plugin for custom functionality.

---

# 3. Child Theme

## Recommended Name

**Forma Hello Child**

Suggested slug:

`forma-hello-child`

### Use the child theme for

- Theme-level CSS
- Small template overrides
- `theme.json` customization if needed
- Typography defaults that belong to the theme
- WordPress hooks that are presentation-specific
- Small frontend enhancements that should survive Elementor/plugin changes

### Do NOT put these inside the child theme

- Project CPT registration
- ACF business logic
- Custom Elementor widgets
- GSAP application logic
- AJAX business logic
- Portfolio data structures
- Plugin integrations

Those belong in the companion plugin.

---

# 4. Companion Plugin

## Plugin Name

**Forma Studio Engine**

Suggested slug:

`forma-studio-engine`

Suggested text domain:

`forma-studio-engine`

Plugin responsibility:

> All reusable project-specific functionality that should remain available independently of the theme.

---

# 5. Forma Studio Engine Architecture

Recommended structure:

```text
forma-studio-engine/
├── forma-studio-engine.php
├── uninstall.php
├── readme.txt
│
├── includes/
│   ├── class-plugin.php
│   ├── class-assets.php
│   ├── class-project-post-type.php
│   ├── class-project-taxonomy.php
│   ├── class-elementor-integration.php
│   ├── class-ajax.php
│   └── helpers.php
│
├── widgets/
│   ├── class-project-grid.php
│   ├── class-project-meta.php
│   ├── class-project-gallery.php
│   ├── class-before-after.php
│   ├── class-animated-heading.php
│   ├── class-marquee.php
│   └── class-project-filter.php
│
├── assets/
│   ├── css/
│   │   ├── frontend.css
│   │   └── widgets.css
│   └── js/
│       ├── frontend.js
│       ├── gsap-init.js
│       ├── project-grid.js
│       ├── page-transition.js
│       └── cursor.js
│
└── templates/
    ├── project-card.php
    └── project-meta.php
```

The exact structure can be adjusted during implementation, but responsibilities must remain separated.

---

# 6. Elementor Strategy

The project should demonstrate both:

1. Native Elementor capability.
2. Custom Elementor development where Elementor Free does not provide enough control.

## Prefer Native Elementor For

- Containers
- Flexbox layouts
- Grid layouts
- Headings
- Text
- Images
- Buttons
- Icon lists
- Image galleries
- Accordions
- Basic forms if available
- Responsive controls
- Global styles
- Global fonts
- Custom CSS where supported
- Reusable sections/components

## Custom Widgets For

If using Elementor Free, build custom widgets inside Forma Studio Engine:

### Project Grid Widget

Features:

- Dynamic project loading
- Featured image
- Project title
- Category
- Location
- Year
- Hover state
- Variable column count
- Responsive columns
- Optional animation

### Project Filter Widget

Features:

- Category filtering
- AJAX filtering if useful
- Active filter state
- URL query support if practical
- Animated transitions

### Project Meta Widget

Displays:

- Client
- Location
- Year
- Area
- Type
- Status
- Architect

### Project Gallery Widget

Features:

- Dynamic gallery
- Masonry/grid mode
- Lightbox
- Image captions
- Lazy loading

### Before / After Widget

Features:

- Two images
- Drag comparison slider
- Responsive behavior
- Optional labels

### Animated Heading Widget

Features:

- Word reveal
- Character reveal
- Line reveal
- Scroll-triggered animation
- Reduced-motion fallback

### Marquee Widget

Features:

- Infinite horizontal text
- Adjustable speed
- Direction
- Pause on hover
- Responsive behavior

---

# 7. Dynamic Content Architecture

Create a custom post type:

`forma_project`

Label:

**Projects**

Suggested taxonomy:

`project_category`

Examples:

- Residential
- Commercial
- Hospitality
- Interior
- Objects

Optional taxonomy:

`project_location`

---

# 8. Project Fields

Use ACF for structured project information.

Recommended fields:

```text
Project Title
Client
Location
Year
Area
Project Type
Architect
Designer
Status
Featured Image
Project Gallery
Hero Image
Short Description
Long Description
Challenge
Approach
Result
Awards
Before Image
After Image
Project URL
Featured Project
```

Do not hard-code project information inside Elementor pages.

The Projects archive and single project pages should be data-driven.

---

# 9. Website Structure

## Primary Pages

### Home

Sections:

1. Full-screen hero
2. Intro statement
3. Featured projects
4. Selected project showcase
5. Studio philosophy
6. Services
7. Process
8. Awards / recognition
9. Editorial statement
10. CTA
11. Footer

---

## Projects

Purpose:

Show dynamic portfolio capability.

Include:

- Page intro
- Category filters
- Project grid
- Hover interactions
- Load more or pagination
- Smooth transitions

Project card should include:

- Image
- Project title
- Category
- Location
- Year

---

## Single Project

This should be one of the strongest pages in the portfolio.

Structure:

1. Project hero
2. Project title
3. Metadata
4. Intro
5. Full-width image
6. Project narrative
7. Image sequence
8. Detail gallery
9. Before / after if applicable
10. Design approach
11. Result
12. Next project
13. CTA

---

## Studio

Sections:

- Studio introduction
- Philosophy
- Team
- Selected numbers
- Approach
- Awards
- Press / recognition

---

## Services

Possible services:

- Architecture
- Interior Design
- Hospitality Design
- Residential Design
- Commercial Design
- Art Direction
- Furniture / Objects

---

## Process

Create an editorial timeline:

1. Discovery
2. Concept
3. Development
4. Documentation
5. Construction
6. Completion

Use scroll-based storytelling.

---

## Contact

Include:

- Contact information
- Inquiry form
- Studio location
- Social links
- Large editorial CTA

---

# 10. Visual Design System

## Design Principles

- Large typography
- Generous whitespace
- Strong image hierarchy
- Minimal UI
- Editorial layouts
- Asymmetric compositions
- Thin borders
- Subtle hover states
- Very limited decorative elements

Avoid:

- Excessive gradients
- Generic card layouts
- Overuse of glassmorphism
- Excessive shadows
- Random animations
- Template-looking sections

---

# 11. Typography

Recommended direction:

### Display Font

Use a sophisticated serif or high-fashion editorial font.

### UI / Body Font

Use a clean modern sans-serif.

The final combination should create:

**Editorial + Architectural + Digital**

Typography should be controlled through Elementor Global Fonts where possible.

---

# 12. Homepage Hero Concept

Hero should immediately communicate premium quality.

Example composition:

```text
FORMA

ARCHITECTURE
INTERIORS
OBJECTS

[Large architectural image]

SCROLL TO EXPLORE
```

Potential interaction:

- Initial typography reveal
- Image clip-path reveal
- Subtle image scale
- Mouse movement
- Scroll-triggered transition

Animation should feel expensive rather than flashy.

---

# 13. GSAP Strategy

Use **GSAP** for advanced motion.

Recommended companion plugin integration:

```text
GSAP
ScrollTrigger
```

Do not animate every Elementor element.

Use GSAP only where it adds storytelling or interaction value.

## Animations

### Hero

- Text reveal
- Image reveal
- Image scale
- Scroll-linked movement

### Project Grid

- Image scale on hover
- Metadata reveal
- Cursor interaction
- Optional subtle displacement

### Project Page

- Image reveal
- Section entrance
- Text reveal
- Parallax-like movement

### Process

- Scroll-driven progress
- Horizontal movement
- Step transitions

### Footer

- Large typography reveal

---

# 14. Reduced Motion

Respect:

```css
prefers-reduced-motion
```

When reduced motion is enabled:

- Disable major GSAP transitions.
- Remove unnecessary parallax.
- Keep content immediately readable.
- Preserve functional interactions.

Accessibility is part of the portfolio quality.

---

# 15. Custom Cursor

Optional premium interaction:

- Small cursor dot
- Expanded cursor on project hover
- Text such as:

VIEW PROJECT
DRAG
EXPLORE

Do not make the cursor unusable on mobile.

Disable or simplify it on touch devices.

---

# 16. Page Transition

Optional:

- Simple full-screen transition layer
- GSAP fade/clip animation
- No blocking animation longer than necessary

Do not create a heavy SPA-like navigation system.

WordPress navigation must remain reliable.

---

# 17. Image Strategy

Architecture websites depend heavily on imagery.

Use:

- WebP/AVIF where supported
- Correct image dimensions
- Lazy loading
- Responsive image sizes
- Hero image optimization
- Avoid loading full-resolution images when unnecessary

Do not use huge images simply because they look good locally.

---

# 18. Responsive Design

Design intentionally for:

### Desktop

1440px+

### Laptop

1024–1439px

### Tablet

768–1023px

### Mobile

320–767px

Do not simply shrink desktop.

Re-design layouts where necessary.

Examples:

- Horizontal desktop galleries can become vertical mobile galleries.
- Large typography scales down intelligently.
- Hover-only interactions need touch alternatives.
- Custom cursor disappears on touch.
- Complex grids become simpler stacks.

---

# 19. Elementor Skills This Project Must Demonstrate

The final project should visibly demonstrate:

- Container system
- Flexbox
- CSS Grid
- Global styles
- Global fonts
- Responsive controls
- Advanced spacing
- Positioning
- Z-index
- Custom CSS
- Reusable components
- Dynamic content
- Custom widgets
- Template architecture
- WordPress integration

If Elementor Pro is available, additionally demonstrate:

- Theme Builder
- Dynamic Tags
- Loop Grid
- Loop Carousel
- Popup
- Forms
- Motion Effects
- Custom Code where appropriate

But the project must remain architecturally useful even with Elementor Free.

---

# 20. Child Theme vs Plugin Decision Rule

Use this rule throughout the project:

### Child Theme

Use for:

> Presentation and theme-level customization.

### Companion Plugin

Use for:

> Functionality and reusable features.

### Elementor

Use for:

> Page composition and visual editing.

### ACF

Use for:

> Structured project content.

### GSAP

Use for:

> Motion and interaction.

This separation should be maintained consistently.

---

# 21. Performance Requirements

Target:

- Fast initial render
- Minimal plugin overhead
- Conditional asset loading
- No unnecessary JavaScript
- No unnecessary CSS
- Optimized images
- Avoid layout shift
- Avoid animation jank

The companion plugin should load widget assets only when required where practical.

Example:

Do not load the project filter JavaScript on the Contact page.

---

# 22. SEO Requirements

Implement:

- Semantic HTML
- One clear H1 per page
- Proper heading hierarchy
- Descriptive image alt text
- SEO-friendly project URLs
- Breadcrumb support if appropriate
- Open Graph metadata via SEO plugin
- Schema support through SEO plugin/custom implementation where justified

Project URLs:

```text
/projects/
projects/project-name/
```

---

# 23. Accessibility Requirements

Implement:

- Keyboard navigation
- Visible focus states
- Semantic buttons/links
- Proper labels
- Accessible forms
- Alt text
- Sufficient contrast
- Reduced-motion support
- Touch-friendly controls

Do not make visual effects interfere with accessibility.

---

# 24. Security Requirements

For custom plugin code:

- Sanitize input
- Escape output
- Validate permissions
- Use nonces for AJAX
- Validate AJAX requests
- Avoid direct file access
- Use WordPress APIs
- Never trust frontend values

Example:

```php
defined( 'ABSPATH' ) || exit;
```

---

# 25. Sample Portfolio Content

Create approximately 8–12 fictional projects.

Example names:

1. Casa Nera
2. Monolith House
3. Atelier 27
4. Terra Residence
5. The Quiet Hotel
6. Axis Workspace
7. Concrete Garden
8. House of Light
9. Forma Pavilion
10. Northline Residence

Each project should have realistic:

- Client
- Location
- Year
- Area
- Category
- Description
- Gallery

Avoid lorem ipsum.

---

# 26. Homepage Project Showcase

Show 5–6 projects.

Use different visual treatments:

- Large feature
- Two-column pair
- Full-width project
- Asymmetric image
- Editorial list

The project grid should not look like a standard Elementor three-column card grid.

---

# 27. Unique Interaction Concept

Create a section called:

**The Making of a Space**

Desktop:

- Horizontal scroll sequence
- Project images
- Short text labels
- Timeline indicators

Mobile:

- Vertical story sequence

This section can become one of the strongest visual signatures of the site.

---

# 28. Portfolio Case Study Requirements

After development, document:

## Problem

A premium architecture studio needed an editorial portfolio experience.

## Solution

Built a dynamic WordPress + Elementor system with:

- Custom project CPT
- ACF
- Custom Elementor widgets
- GSAP
- Responsive layouts
- Performance optimization
- Accessibility

## Technical Stack

```text
WordPress
Elementor
Hello Elementor
ACF
PHP
JavaScript
GSAP
ScrollTrigger
CSS
```

## Developer Contribution

Clearly explain that the work included:

- Elementor architecture
- Custom WordPress development
- Dynamic content
- Custom widgets
- Animation
- Responsive implementation
- Performance
- Accessibility

---

# 29. Suggested Development Phases

## Phase 1 — Foundation

- Install WordPress
- Install Hello Elementor
- Install Elementor
- Create Forma Hello Child
- Create Forma Studio Engine
- Configure Git repository
- Establish coding standards

## Phase 2 — Design System

- Typography
- Colors
- Spacing
- Container widths
- Buttons
- Links
- Image treatments
- Responsive rules

## Phase 3 — Dynamic Content

- Create Project CPT
- Create taxonomy
- Configure ACF fields
- Add sample projects
- Create dynamic templates

## Phase 4 — Elementor Build

Build:

- Header
- Footer
- Home
- Projects
- Studio
- Services
- Process
- Contact

## Phase 5 — Custom Widgets

Build only widgets that provide meaningful portfolio value:

- Project Grid
- Project Meta
- Project Gallery
- Before/After
- Project Filter
- Animated Heading
- Marquee

## Phase 6 — Motion

Implement:

- GSAP
- ScrollTrigger
- Hero animation
- Project interactions
- Scroll storytelling
- Page transitions
- Cursor

## Phase 7 — Responsive

Test:

- Desktop
- Laptop
- Tablet
- Mobile

## Phase 8 — Optimization

- Image optimization
- Asset loading
- JS optimization
- CSS cleanup
- Layout shift checks
- Accessibility checks

## Phase 9 — QA

Test:

- Chrome
- Firefox
- Safari if available
- Mobile browsers
- Keyboard navigation
- Reduced motion
- Contact forms
- Project filtering
- Dynamic project pages

## Phase 10 — Portfolio Packaging

Prepare:

- Screenshots
- Desktop mockups
- Mobile mockups
- Technical overview
- Feature list
- Case study
- GitHub repository if appropriate
- Fiverr portfolio entry
- Upwork portfolio entry
- LinkedIn case study

---

# 30. Git Repository Structure

Recommended:

```text
forma-wordpress/
├── wp-content/
│   ├── themes/
│   │   └── forma-hello-child/
│   │
│   └── plugins/
│       └── forma-studio-engine/
│
├── docs/
│   ├── architecture.md
│   ├── widgets.md
│   ├── animation.md
│   └── deployment.md
│
└── README.md
```

Do not commit:

- WordPress core
- uploads
- secrets
- database credentials
- production `.env`
- unnecessary generated files

---

# 31. Definition of Done

The project is complete when:

- The website looks like a real premium architecture studio.
- It does not look like a generic Elementor template.
- Projects are dynamically managed.
- Elementor is used extensively and intelligently.
- Elementor Free limitations are solved through custom widgets where necessary.
- Child theme and plugin responsibilities are clearly separated.
- GSAP is integrated cleanly.
- Animations are subtle and purposeful.
- Mobile experience is intentionally designed.
- Accessibility is respected.
- Performance is acceptable.
- Code is organized and maintainable.
- The project can be shown to a client as a professional Elementor build.
- The project can be explained technically in a portfolio case study.

---

# 32. AI Build Instructions

When another AI is asked to build this project:

1. Follow this specification as the source of truth.
2. Do not replace Elementor with another page builder.
3. Do not turn the project into a generic template.
4. Prefer native Elementor functionality before writing custom code.
5. When Elementor Free cannot provide a required feature, implement it in **Forma Studio Engine** rather than hacking around Elementor.
6. Keep reusable functionality inside the plugin.
7. Keep theme-level presentation inside **Forma Hello Child**.
8. Use ACF for structured project data.
9. Use GSAP only for meaningful interaction.
10. Respect reduced motion.
11. Optimize assets and images.
12. Build responsive layouts intentionally.
13. Use semantic, accessible markup.
14. Escape and sanitize all dynamic values.
15. Do not hard-code project data into templates.
16. Avoid unnecessary dependencies.
17. Keep frontend JavaScript modular.
18. Use WordPress APIs rather than custom replacements when possible.
19. Explain important architectural decisions in code comments and documentation.
20. Before adding a feature, determine whether it belongs to Elementor, the child theme, the companion plugin, ACF, or WordPress core.

---

# 33. Final Portfolio Positioning

The project should position the developer as:

> **Advanced Elementor & WordPress Developer**

Not merely:

> Elementor Website Designer

The portfolio should demonstrate the ability to combine:

**Elementor + WordPress + PHP + ACF + Custom Widgets + GSAP + Responsive UI + Performance**

That combination is the primary value of this project.
