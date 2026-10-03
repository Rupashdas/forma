# FORMA Plan 1 — Environment, Plugin Foundation & Content Model

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or
> superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A working local FORMA site with ProElements active, a rebuilt `forma-studio-engine` plugin (Projects post
type, taxonomies, project numbers, media rules, WP-CLI build) and a clean child theme, seeded with 11 projects,
6 pages and 2 menus by `wp forma all`.

**Architecture:** The plugin is a set of small modules behind a PSR-4 autoloader (ported from Arvale Core). All
content comes from `data/*.php` and is written through WordPress APIs by idempotent seeders run from WP-CLI.
Integration tests run inside WordPress through `wp eval-file`, each in a rolled-back database transaction.

**Tech Stack:** WordPress 6.8+, PHP 8.1+ (Local runs 8.2.29), Elementor 4.3.3, ProElements 4.2.3, Hello Elementor 3.5.1,
WP-CLI 2.12 (Local's bundled phar), MySQL 8.4 on port 10035.

**Spec:** `docs/forma-build-plan.md` (sections 2, 3, 4, 8, 9, 12 phases 0–2).

**Later plans (written when this one is done):** Plan 2 fonts + GSAP + images (needs approved downloads) ·
Plan 3 widgets, Forma Motion, cursor, transitions, SEO · Plan 4 Elementor design build (Kit, header/footer, Home →
owner review, then remaining templates/pages) · Plan 5 QA, deploy, portfolio entry.

## Global Constraints

- Every PHP file starts with `defined( 'ABSPATH' ) || exit;` (after the namespace line where there is one).
- PHP 8.1 minimum (`Requires PHP: 8.1`), WordPress 6.5 minimum; WordPress coding style (tabs, `array()`, Yoda
  comparisons, spaces inside parentheses).
- Plugin namespace `Forma\Engine\`, constants `FORMA_ENGINE_VERSION|FILE|PATH|URL`, text domain
  `forma-studio-engine`. Theme text domain `forma-hello-child`, constant `FORMA_THEME_VERSION`.
- No custom meta fields. The only meta the plugin writes is hidden bookkeeping: `_forma_seed` (seeded
  posts/templates) and, in Plan 2, `_forma_credit` (attachments).
- Copy is British English, no lorem ipsum, no placeholder text; awards, publications and clients are fictional.
- Escape all output, sanitise all input, use WordPress APIs (no raw SQL except the test transaction).
- All WP-CLI commands are run with Bash from `C:\Users\DevRupash\Local Sites\forma\app\public` as `../../bin/wp …`
  (wrapper created in Task 0).
- Tests: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php [Filter]`; exit code 1 on
  failure. The `tests/` folder is excluded from deploy.
- Commits: the Forma folder is not a Git repository yet (spec §14 item 4). Each task ends with a checkpoint; if the
  owner has approved a repository by then, commit with the message given.

## File map

```text
Local Sites/forma/
├── bin/wp                                   # WP-CLI wrapper (Task 0)
├── _archive/                                # old scaffold, moved out of the web root (Task 0)
└── app/public/wp-content/
    ├── plugins/forma-studio-engine/
    │   ├── forma-studio-engine.php          # header, constants, autoloader, boot hooks (Task 1)
    │   ├── uninstall.php · readme.txt       # (Task 1)
    │   ├── src/Autoloader.php               # PSR-4 (Task 1)
    │   ├── src/Contracts/Module.php         # module interface (Task 1)
    │   ├── src/Plugin.php                   # module list + CLI registration (Tasks 1, 2, 4, 7)
    │   ├── src/Projects/Projects.php        # CPT + taxonomies (Task 2)
    │   ├── src/Projects/ProjectNumber.php   # "07" numbering (Task 3)
    │   ├── src/Media/Media.php              # sizes, WebP, 2400px cap (Task 4)
    │   ├── src/Seed/Setup.php               # options, permalinks, Elementor settings (Task 7)
    │   ├── src/Seed/Content.php             # terms, projects, pages, front page, menus (Task 8)
    │   ├── src/Cli/Command.php              # wp forma setup|content|all (Tasks 7, 8)
    │   ├── data/site.php                    # types, pages, menus, site name (Task 6)
    │   ├── data/projects.php                # 11 projects (Task 6)
    │   └── tests/run.php · TestCase.php · *Test.php
    └── themes/forma-hello-child/
        ├── style.css · functions.php        # (Task 5)
        └── assets/css/site.css              # tokens + base styles (Task 5)
```

---

### Task 0: Environment and tooling

**Files:**
- Create: `C:\Users\DevRupash\Local Sites\forma\bin\wp`
- Modify: `app/public/wp-config.php` (debug constants, via WP-CLI)
- Move: `wp-content/plugins/forma-studio-engine` → `forma/_archive/forma-studio-engine-scaffold`
- Move: `wp-content/themes/forma-hello-child` → `forma/_archive/forma-hello-child-scaffold`
- Copy: `front-commerce/.../plugins/pro-elements` → `wp-content/plugins/pro-elements`

**Interfaces:**
- Produces: `../../bin/wp` (WP-CLI against the Forma DB, no Imagick startup warning) used by every later task.

- [ ] **Step 1: Create the WP-CLI wrapper**

```bash
mkdir -p "/c/Users/DevRupash/Local Sites/forma/bin"
cat > "/c/Users/DevRupash/Local Sites/forma/bin/wp" <<'EOF'
#!/usr/bin/env bash
# WP-CLI for the Forma Local site: Local's PHP 8.2 with Forma's php.ini (MySQL on port 10035).
# Local's php.ini loads php_imagick.dll, which this PHP build lacks; a filtered copy avoids the startup warning.
HERE="$(cd "$(dirname "$0")" && pwd)"
PHP="/c/Users/DevRupash/AppData/Roaming/Local/lightning-services/php-8.2.29+0/bin/win64/php.exe"
INI="C:/Users/DevRupash/AppData/Roaming/Local/run/xdfDC1zWt/conf/php/php.ini"
PHAR="C:/Program Files (x86)/Local/resources/extraResources/bin/wp-cli/wp-cli.phar"
grep -vi "imagick" "$INI" > "$HERE/.php-cli.ini"
exec "$PHP" -c "$HERE/.php-cli.ini" "$PHAR" --path="C:/Users/DevRupash/Local Sites/forma/app/public" "$@"
EOF
chmod +x "/c/Users/DevRupash/Local Sites/forma/bin/wp"
```

- [ ] **Step 2: Verify the wrapper**

Run: `../../bin/wp --version && ../../bin/wp option get home`
Expected: `WP-CLI 2.12.0` and `http://forma.local`, with no "Unable to load dynamic library" line.

- [ ] **Step 3: Turn on debug logging (local only)**

```bash
../../bin/wp config set WP_DEBUG true --raw
../../bin/wp config set WP_DEBUG_LOG true --raw
../../bin/wp config set WP_DEBUG_DISPLAY false --raw
```

Expected: three `Success: Updated the constant …` / `Added the constant …` lines.

- [ ] **Step 4: Move the old scaffold out of the web root**

```bash
mkdir -p "/c/Users/DevRupash/Local Sites/forma/_archive"
mv wp-content/plugins/forma-studio-engine "/c/Users/DevRupash/Local Sites/forma/_archive/forma-studio-engine-scaffold"
mv wp-content/themes/forma-hello-child "/c/Users/DevRupash/Local Sites/forma/_archive/forma-hello-child-scaffold"
```

Expected: `../../bin/wp plugin list` no longer lists `forma-studio-engine`; `../../bin/wp theme list` no longer
lists `forma-hello-child`; Hello Elementor is still the active theme.

- [ ] **Step 5: Install ProElements and deactivate unused plugins**

```bash
cp -r "/c/Users/DevRupash/Local Sites/front-commerce/app/public/wp-content/plugins/pro-elements" wp-content/plugins/
../../bin/wp plugin activate pro-elements
../../bin/wp plugin deactivate essential-addons-for-elementor-lite essential-blocks templately
```

- [ ] **Step 6: Verify the environment**

Run: `../../bin/wp plugin list --fields=name,status,version && ../../bin/wp option get permalink_structure`
Expected: `elementor active 4.3.3`, `pro-elements active 4.2.3`, `contact-form-7 active`,
`defer-forms-for-contact-form-7 active`; Essential Addons, Essential Blocks, Templately `inactive`;
permalink structure `/%postname%/` (if empty, run `../../bin/wp rewrite structure '/%postname%/'`).
Then open `http://forma.local/wp-admin/` in the browser: no fatal error, Elementor → no "ProElements requires" notice.

- [ ] **Step 7: Checkpoint** — no repository yet; nothing to commit.

---

### Task 1: Plugin bootstrap and test harness

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/forma-studio-engine.php`
- Create: `wp-content/plugins/forma-studio-engine/uninstall.php`
- Create: `wp-content/plugins/forma-studio-engine/readme.txt`
- Create: `wp-content/plugins/forma-studio-engine/src/Autoloader.php`
- Create: `wp-content/plugins/forma-studio-engine/src/Contracts/Module.php`
- Create: `wp-content/plugins/forma-studio-engine/src/Plugin.php`
- Create: `wp-content/plugins/forma-studio-engine/tests/run.php`
- Create: `wp-content/plugins/forma-studio-engine/tests/TestCase.php`
- Test: `wp-content/plugins/forma-studio-engine/tests/BootTest.php`

**Interfaces:**
- Produces: `Forma\Engine\Contracts\Module` (`static id(): string`, `is_available(): bool`, `register(): void`);
  `Forma\Engine\Plugin::boot()`, `Plugin::module( string $id ): ?Module`, `Plugin::activate()`, private const
  `MODULES` (array of module class names, extended by later tasks); action `forma_engine_loaded`;
  `Forma\Engine\Tests\TestCase` with `set_up()`, `tear_down()`, `assert_same()`, `assert_true()`, `fail()`.

- [ ] **Step 1: Write the test harness**

`tests/TestCase.php`:

```php
<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

/**
 * Tiny integration test base. Each test runs inside a database transaction that is rolled back afterwards,
 * so tests can create posts, terms and options without touching the site's real content.
 */
abstract class TestCase {

	/**
	 * Run one test method; returns null on success or a failure message.
	 */
	public static function run( string $class, string $method ): ?string {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );
		$test = new $class();

		try {
			$test->set_up();
			$test->$method();
			return null;
		} catch ( \Throwable $e ) {
			return $e->getMessage() . ' @ ' . self::origin( $e );
		} finally {
			$test->tear_down();
			$wpdb->query( 'ROLLBACK' );
			wp_cache_flush();
		}
	}

	/**
	 * File:line of the first frame inside a *Test.php file, so failures point at the assertion.
	 */
	private static function origin( \Throwable $e ): string {
		$frames = array_merge(
			array(
				array(
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				),
			),
			$e->getTrace()
		);

		foreach ( $frames as $frame ) {
			if ( isset( $frame['file'] ) && str_ends_with( $frame['file'], 'Test.php' ) ) {
				return basename( $frame['file'] ) . ':' . $frame['line'];
			}
		}

		return basename( $e->getFile() ) . ':' . $e->getLine();
	}

	protected function set_up(): void {}

	protected function tear_down(): void {}

	protected function assert_same( mixed $expected, mixed $actual, string $message = '' ): void {
		if ( $expected !== $actual ) {
			$this->fail( trim( $message . ' — expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) ) );
		}
	}

	protected function assert_true( bool $condition, string $message ): void {
		if ( ! $condition ) {
			$this->fail( $message );
		}
	}

	protected function fail( string $message ): never {
		throw new AssertionFailed( $message );
	}
}

final class AssertionFailed extends \Exception {}
```

`tests/run.php`:

```php
<?php
/**
 * Integration test runner. Runs inside WordPress through WP-CLI:
 *
 *     ../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php [Filter]
 *
 * Filter matches "ClassName::method" by substring. Exits with code 1 when anything fails.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/TestCase.php';

foreach ( glob( __DIR__ . '/*Test.php' ) as $forma_test_file ) {
	require_once $forma_test_file;
}

$forma_filter = $args[0] ?? '';
$forma_passed = 0;
$forma_failed = 0;

foreach ( get_declared_classes() as $forma_class ) {
	if ( ! is_subclass_of( $forma_class, Forma\Engine\Tests\TestCase::class ) ) {
		continue;
	}

	$forma_short = ( new ReflectionClass( $forma_class ) )->getShortName();

	foreach ( get_class_methods( $forma_class ) as $forma_method ) {
		$forma_name = $forma_short . '::' . $forma_method;

		if ( ! str_starts_with( $forma_method, 'test_' ) || ( $forma_filter && ! str_contains( $forma_name, $forma_filter ) ) ) {
			continue;
		}

		$forma_error = Forma\Engine\Tests\TestCase::run( $forma_class, $forma_method );

		if ( null === $forma_error ) {
			++$forma_passed;
			WP_CLI::log( "PASS  {$forma_name}" );
		} else {
			++$forma_failed;
			WP_CLI::log( "FAIL  {$forma_name}\n      {$forma_error}" );
		}
	}
}

WP_CLI::log( sprintf( '%d passed, %d failed', $forma_passed, $forma_failed ) );

if ( $forma_failed > 0 ) {
	WP_CLI::halt( 1 );
}
```

- [ ] **Step 2: Write the failing test**

`tests/BootTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class BootTest extends TestCase {

	public function test_plugin_constants_are_defined(): void {
		$this->assert_true( defined( 'FORMA_ENGINE_VERSION' ), 'FORMA_ENGINE_VERSION is defined (is the plugin active?)' );
		$this->assert_same( '1.0.0', FORMA_ENGINE_VERSION );
		$this->assert_true( is_dir( FORMA_ENGINE_PATH . 'src' ), 'FORMA_ENGINE_PATH points at the plugin folder' );
	}

	public function test_autoloader_resolves_plugin_classes(): void {
		$this->assert_true( class_exists( \Forma\Engine\Plugin::class ), 'Forma\Engine\Plugin autoloads' );
		$this->assert_true( interface_exists( \Forma\Engine\Contracts\Module::class ), 'Module contract autoloads' );
	}

	public function test_boot_fires_loaded_action(): void {
		$this->assert_true( did_action( 'forma_engine_loaded' ) > 0, 'forma_engine_loaded has fired' );
	}
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `FAIL  BootTest::test_plugin_constants_are_defined` … `0 passed, 3 failed`, exit code 1.

- [ ] **Step 4: Write the plugin bootstrap**

`forma-studio-engine.php`:

```php
<?php
/**
 * Plugin Name:       Forma Studio Engine
 * Description:       Functionality for the FORMA studio site: the Projects post type, custom Elementor widgets and the Forma Motion extension, GSAP motion, view transitions, media and SEO, plus the WP-CLI build.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  elementor
 * Author:            Rupash Das
 * Author URI:        https://devrupash.com
 * License:           GPL-2.0-or-later
 * Text Domain:       forma-studio-engine
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMA_ENGINE_VERSION', '1.0.0' );
define( 'FORMA_ENGINE_FILE', __FILE__ );
define( 'FORMA_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORMA_ENGINE_URL', plugin_dir_url( __FILE__ ) );

require_once FORMA_ENGINE_PATH . 'src/Autoloader.php';

Forma\Engine\Autoloader::register( 'Forma\\Engine\\', FORMA_ENGINE_PATH . 'src/' );

add_action( 'plugins_loaded', array( Forma\Engine\Plugin::class, 'boot' ), 20 );

register_activation_hook( __FILE__, array( Forma\Engine\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
```

`src/Autoloader.php`:

```php
<?php

namespace Forma\Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal PSR-4 autoloader, so the plugin ships without a vendor/ directory
 * (fewer files on inode-limited hosting, no build step to deploy).
 */
final class Autoloader {

	public static function register( string $prefix, string $base_dir ): void {
		spl_autoload_register(
			static function ( string $class ) use ( $prefix, $base_dir ) {
				if ( ! str_starts_with( $class, $prefix ) ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

				if ( is_readable( $file ) ) {
					require $file;
				}
			}
		);
	}
}
```

`src/Contracts/Module.php`:

```php
<?php

namespace Forma\Engine\Contracts;

defined( 'ABSPATH' ) || exit;

interface Module {

	/**
	 * Stable key for the module, used by Plugin::module().
	 */
	public static function id(): string;

	/**
	 * Whether the module's dependencies are present.
	 */
	public function is_available(): bool;

	/**
	 * Attach hooks. Called once, only when available.
	 */
	public function register(): void;
}
```

`src/Plugin.php`:

```php
<?php

namespace Forma\Engine;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Module classes in load order. Each decides for itself whether its dependencies exist.
	 *
	 * @var array<class-string<Module>>
	 */
	private const MODULES = array();

	/** @var array<string, Module> */
	private static array $modules = array();

	public static function boot(): void {
		foreach ( self::MODULES as $class ) {
			$module = new $class();

			if ( ! $module->is_available() ) {
				continue;
			}

			$module->register();
			self::$modules[ $class::id() ] = $module;
		}

		/**
		 * Fires after Forma Studio Engine has registered its modules.
		 *
		 * @param array<string, Module> $modules Active modules keyed by id.
		 */
		do_action( 'forma_engine_loaded', self::$modules );
	}

	public static function module( string $id ): ?Module {
		return self::$modules[ $id ] ?? null;
	}

	public static function activate(): void {
		flush_rewrite_rules();
	}
}
```

`uninstall.php`:

```php
<?php
/**
 * Forma Studio Engine stores no options of its own. Projects, pages and media belong to the site owner
 * and are deliberately left in place when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
```

`readme.txt`:

```text
=== Forma Studio Engine ===
Contributors: rupashdas
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later

Functionality for the FORMA studio site, a portfolio project by Rupash Das (https://devrupash.com).

== Description ==

* Projects post type with Type and Location taxonomies, numbered by completion date.
* Custom Elementor widgets and the Forma Motion extension for native widgets.
* Media rules for inode-limited hosting (fewer sizes, WebP).
* WP-CLI build: `wp forma all` recreates the site's settings and content.

FORMA is a fictional studio. Presentation lives in the Forma Hello Child theme.
```

- [ ] **Step 5: Activate and run the tests**

Run: `../../bin/wp plugin activate forma-studio-engine && ../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `Success: Activated 1 of 1 plugins.` then `3 passed, 0 failed`.

- [ ] **Step 6: Lint**

Run: `for f in $(find wp-content/plugins/forma-studio-engine -name "*.php"); do /c/Users/DevRupash/AppData/Roaming/Local/lightning-services/php-8.2.29+0/bin/win64/php.exe -n -l "$f" | grep -v "^No syntax errors"; done; echo lint-done`
Expected: only `lint-done`.

- [ ] **Step 7: Checkpoint** — commit message if a repository exists: `feat(engine): plugin bootstrap, autoloader and test harness`.

---

### Task 2: Projects post type and taxonomies

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/src/Projects/Projects.php`
- Modify: `wp-content/plugins/forma-studio-engine/src/Plugin.php` (MODULES, activate)
- Test: `wp-content/plugins/forma-studio-engine/tests/ProjectsTest.php`

**Interfaces:**
- Consumes: `Contracts\Module`, `Plugin::MODULES`.
- Produces: `Forma\Engine\Projects\Projects` with constants `POST_TYPE = 'forma_project'`,
  `TYPE_TAX = 'project_type'`, `LOCATION_TAX = 'project_location'`; `static register_types(): void`.
  URLs: `/projects/`, `/projects/{slug}/`, `/projects/type/{term}/`, `/projects/location/{term}/`.

- [ ] **Step 1: Write the failing test**

`tests/ProjectsTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class ProjectsTest extends TestCase {

	public function test_post_type_is_public_with_archive_and_elementor_support(): void {
		$type = get_post_type_object( 'forma_project' );

		$this->assert_true( null !== $type, 'forma_project is registered' );
		$this->assert_true( $type->public, 'forma_project is public' );
		$this->assert_true( $type->show_in_rest, 'forma_project is in the REST API' );
		$this->assert_same( 'projects', $type->has_archive );
		$this->assert_same( 'projects', $type->rewrite['slug'] ?? null );

		foreach ( array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'elementor' ) as $feature ) {
			$this->assert_true( post_type_supports( 'forma_project', $feature ), "forma_project supports {$feature}" );
		}

		$this->assert_true( ! post_type_supports( 'forma_project', 'comments' ), 'forma_project has no comments' );
	}

	public function test_taxonomies_are_attached(): void {
		$taxonomies = get_object_taxonomies( 'forma_project' );
		sort( $taxonomies );

		$this->assert_same( array( 'project_location', 'project_type' ), $taxonomies );
		$this->assert_true( get_taxonomy( 'project_type' )->hierarchical, 'project_type is hierarchical' );
		$this->assert_true( ! get_taxonomy( 'project_location' )->hierarchical, 'project_location is flat' );
		$this->assert_true( get_taxonomy( 'project_type' )->show_admin_column, 'project_type shows in the admin list' );
	}

	public function test_urls_route_to_the_right_queries(): void {
		$this->assert_true( $GLOBALS['wp_rewrite']->using_permalinks(), 'pretty permalinks are on (Task 0 step 6)' );

		$this->assert_true( str_contains( $this->route( 'projects/' ), 'post_type=forma_project' ), 'projects/ → archive' );
		$this->assert_true( str_contains( $this->route( 'projects/casa-nera/' ), 'forma_project=' ), 'projects/casa-nera/ → single' );
		$this->assert_true( str_contains( $this->route( 'projects/type/residential/' ), 'project_type=' ), 'projects/type/residential/ → type archive' );
		$this->assert_true( str_contains( $this->route( 'projects/location/kyoto-japan/' ), 'project_location=' ), 'projects/location/… → location archive' );
	}

	/**
	 * The query string of the first rewrite rule matching $path, the way WP::parse_request() matches.
	 */
	private function route( string $path ): string {
		foreach ( $GLOBALS['wp_rewrite']->rewrite_rules() as $regex => $query ) {
			if ( preg_match( "#^{$regex}#", $path ) ) {
				return $query;
			}
		}

		return '';
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php ProjectsTest`
Expected: `FAIL  ProjectsTest::test_post_type_is_public_with_archive_and_elementor_support — forma_project is registered`, `0 passed, 3 failed`.

- [ ] **Step 3: Implement the module**

`src/Projects/Projects.php`:

```php
<?php

namespace Forma\Engine\Projects;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * The Projects post type and its two taxonomies. A project's year is its post date (the completion date);
 * everything else about a project is composed in its Elementor body, so there are no custom fields.
 */
final class Projects implements Module {

	public const POST_TYPE    = 'forma_project';
	public const TYPE_TAX     = 'project_type';
	public const LOCATION_TAX = 'project_location';

	public static function id(): string {
		return 'projects';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_action( 'init', array( self::class, 'register_types' ) );
	}

	/**
	 * Taxonomies are registered before the post type so their /projects/type/… and /projects/location/… rewrite
	 * rules sit above the post type's attachment rules, which would otherwise swallow those URLs.
	 */
	public static function register_types(): void {
		register_taxonomy(
			self::TYPE_TAX,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Project types', 'forma-studio-engine' ),
					'singular_name' => __( 'Project type', 'forma-studio-engine' ),
					'all_items'     => __( 'All types', 'forma-studio-engine' ),
					'edit_item'     => __( 'Edit type', 'forma-studio-engine' ),
					'add_new_item'  => __( 'Add new type', 'forma-studio-engine' ),
					'search_items'  => __( 'Search types', 'forma-studio-engine' ),
					'not_found'     => __( 'No types found.', 'forma-studio-engine' ),
					'menu_name'     => __( 'Types', 'forma-studio-engine' ),
				),
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'projects/type',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::LOCATION_TAX,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'                       => __( 'Locations', 'forma-studio-engine' ),
					'singular_name'              => __( 'Location', 'forma-studio-engine' ),
					'all_items'                  => __( 'All locations', 'forma-studio-engine' ),
					'edit_item'                  => __( 'Edit location', 'forma-studio-engine' ),
					'add_new_item'               => __( 'Add new location', 'forma-studio-engine' ),
					'search_items'               => __( 'Search locations', 'forma-studio-engine' ),
					'not_found'                  => __( 'No locations found.', 'forma-studio-engine' ),
					'separate_items_with_commas' => __( 'One location, written as "City, Country".', 'forma-studio-engine' ),
				),
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'projects/location',
					'with_front' => false,
				),
			)
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'                  => __( 'Projects', 'forma-studio-engine' ),
					'singular_name'         => __( 'Project', 'forma-studio-engine' ),
					'add_new_item'          => __( 'Add new project', 'forma-studio-engine' ),
					'edit_item'             => __( 'Edit project', 'forma-studio-engine' ),
					'new_item'              => __( 'New project', 'forma-studio-engine' ),
					'view_item'             => __( 'View project', 'forma-studio-engine' ),
					'view_items'            => __( 'View projects', 'forma-studio-engine' ),
					'search_items'          => __( 'Search projects', 'forma-studio-engine' ),
					'not_found'             => __( 'No projects found.', 'forma-studio-engine' ),
					'not_found_in_trash'    => __( 'No projects found in the bin.', 'forma-studio-engine' ),
					'all_items'             => __( 'All projects', 'forma-studio-engine' ),
					'archives'              => __( 'Projects', 'forma-studio-engine' ),
					'featured_image'        => __( 'Project image', 'forma-studio-engine' ),
					'set_featured_image'    => __( 'Set project image', 'forma-studio-engine' ),
					'remove_featured_image' => __( 'Remove project image', 'forma-studio-engine' ),
					'use_featured_image'    => __( 'Use as project image', 'forma-studio-engine' ),
					'item_published'        => __( 'Project published.', 'forma-studio-engine' ),
					'item_updated'          => __( 'Project updated.', 'forma-studio-engine' ),
				),
				'description'   => __( 'Buildings, interiors and objects by the studio. The publish date is the completion date.', 'forma-studio-engine' ),
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => 'projects',
				'rewrite'       => array(
					'slug'       => 'projects',
					'with_front' => false,
				),
				'menu_position' => 5,
				'menu_icon'     => 'dashicons-building',
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'elementor' ),
			)
		);
	}
}
```

In `src/Plugin.php`, replace the empty module list and the activation method:

```php
	private const MODULES = array(
		Projects\Projects::class,
	);
```

```php
	public static function activate(): void {
		Projects\Projects::register_types();
		flush_rewrite_rules();
	}
```

- [ ] **Step 4: Flush rewrite rules and run the tests**

Run: `../../bin/wp rewrite flush && ../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `Success: Rewrite rules flushed.` then `6 passed, 0 failed`.

- [ ] **Step 5: Check the admin**

Open `http://forma.local/wp-admin/edit.php?post_type=forma_project`: "Projects" menu with Types and Locations
submenus. Open `http://forma.local/wp-admin/post-new.php?post_type=forma_project`: an "Edit with Elementor"
button is shown. Close without saving.

- [ ] **Step 6: Checkpoint** — commit message: `feat(engine): Projects post type with type and location taxonomies`.

---

### Task 3: Project numbers

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/src/Projects/ProjectNumber.php`
- Modify: `wp-content/plugins/forma-studio-engine/src/Projects/Projects.php` (cache flush hooks)
- Test: `wp-content/plugins/forma-studio-engine/tests/ProjectNumberTest.php`

**Interfaces:**
- Consumes: `Projects::POST_TYPE`.
- Produces: `Forma\Engine\Projects\ProjectNumber::for_post( int $post_id ): string` ("01"…, '' for anything that
  is not a published project) and `ProjectNumber::flush(): void`. Used by Plan 3's dynamic tag and Project Index.

- [ ] **Step 1: Write the failing test**

`tests/ProjectNumberTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Projects\ProjectNumber;

defined( 'ABSPATH' ) || exit;

final class ProjectNumberTest extends TestCase {

	protected function set_up(): void {
		ProjectNumber::flush();
	}

	public function test_numbers_follow_completion_date_oldest_first(): void {
		$late  = $this->project( '1990-06-01' );
		$early = $this->project( '1990-01-01' );
		$mid   = $this->project( '1990-03-01' );

		$this->assert_same( '01', ProjectNumber::for_post( $early ) );
		$this->assert_same( '02', ProjectNumber::for_post( $mid ) );
		$this->assert_same( '03', ProjectNumber::for_post( $late ) );
	}

	public function test_drafts_and_other_post_types_have_no_number(): void {
		$draft = $this->project( '1990-01-01', 'draft' );
		$page  = (int) wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Not a project',
				'post_status' => 'publish',
			)
		);

		$this->assert_same( '', ProjectNumber::for_post( $draft ) );
		$this->assert_same( '', ProjectNumber::for_post( $page ) );
	}

	public function test_numbers_update_when_an_older_project_is_added(): void {
		$second = $this->project( '1990-06-01' );
		$this->assert_same( '01', ProjectNumber::for_post( $second ) );

		$this->project( '1990-01-01' );
		$this->assert_same( '02', ProjectNumber::for_post( $second ) );
	}

	private function project( string $date, string $status = 'publish' ): int {
		return (int) wp_insert_post(
			array(
				'post_type'   => 'forma_project',
				'post_title'  => 'Test project ' . $date,
				'post_status' => $status,
				'post_date'   => $date . ' 10:00:00',
			)
		);
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php ProjectNumberTest`
Expected: three FAIL lines with `Class "Forma\Engine\Projects\ProjectNumber" not found`.

- [ ] **Step 3: Implement**

`src/Projects/ProjectNumber.php`:

```php
<?php

namespace Forma\Engine\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * A project's sheet number ("07"): its position among published projects, oldest completion date first.
 * Derived on the fly, so adding or re-dating a project renumbers everything without stored data.
 */
final class ProjectNumber {

	/** @var array<int, int>|null Post ID => 1-based position, memoised per request. */
	private static ?array $positions = null;

	public static function for_post( int $post_id ): string {
		$positions = self::positions();

		return isset( $positions[ $post_id ] ) ? sprintf( '%02d', $positions[ $post_id ] ) : '';
	}

	public static function flush(): void {
		self::$positions = null;
	}

	/**
	 * @return array<int, int>
	 */
	private static function positions(): array {
		if ( null === self::$positions ) {
			$ids = get_posts(
				array(
					'post_type'        => Projects::POST_TYPE,
					'post_status'      => 'publish',
					'posts_per_page'   => -1,
					'orderby'          => array(
						'date' => 'ASC',
						'ID'   => 'ASC',
					),
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);

			self::$positions = array();

			foreach ( array_values( $ids ) as $index => $id ) {
				self::$positions[ (int) $id ] = $index + 1;
			}
		}

		return self::$positions;
	}
}
```

In `src/Projects/Projects.php`, extend `register()`:

```php
	public function register(): void {
		add_action( 'init', array( self::class, 'register_types' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( ProjectNumber::class, 'flush' ) );
		add_action( 'deleted_post', array( ProjectNumber::class, 'flush' ) );
	}
```

- [ ] **Step 4: Run the tests**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `9 passed, 0 failed`.

- [ ] **Step 5: Checkpoint** — commit message: `feat(engine): derive project numbers from completion dates`.

---

### Task 4: Media rules

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/src/Media/Media.php`
- Modify: `wp-content/plugins/forma-studio-engine/src/Plugin.php` (MODULES)
- Test: `wp-content/plugins/forma-studio-engine/tests/MediaTest.php`

**Interfaces:**
- Produces: image size `forma-960` (960px wide, uncropped); `Media::MAX_EDGE = 2400`; generated sizes saved as WebP.
  Plan 2's image importer relies on these. Sizes medium (480) and large (1600) are options set in Task 7.

- [ ] **Step 1: Write the failing test**

`tests/MediaTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class MediaTest extends TestCase {

	public function test_unused_sizes_are_not_generated(): void {
		$sizes = apply_filters(
			'intermediate_image_sizes_advanced',
			array_fill_keys( array( 'thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048', 'forma-960' ), array() ),
			array(),
			0
		);

		$this->assert_same( array( 'thumbnail', 'medium', 'large', 'forma-960' ), array_keys( $sizes ) );
	}

	public function test_960_size_is_registered(): void {
		$this->assert_true( has_image_size( 'forma-960' ), 'forma-960 is registered' );
		$this->assert_same( 960, wp_get_additional_image_sizes()['forma-960']['width'] ?? null );
	}

	public function test_originals_are_capped_at_2400px(): void {
		$this->assert_same( 2400, apply_filters( 'big_image_size_threshold', 2560, array( 4000, 3000 ), '', 0 ) );
	}

	public function test_jpeg_and_png_are_saved_as_webp(): void {
		$formats = apply_filters( 'image_editor_output_format', array(), '', 'image/jpeg' );

		$this->assert_same( 'image/webp', $formats['image/jpeg'] ?? null );
		$this->assert_same( 'image/webp', $formats['image/png'] ?? null );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php MediaTest`
Expected: `0 passed, 4 failed` (sizes unfiltered, `forma-960` not registered, threshold 2560, no WebP mapping).

- [ ] **Step 3: Implement**

`src/Media/Media.php`:

```php
<?php

namespace Forma\Engine\Media;

use Forma\Engine\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Leaner uploads for inode-limited hosting: generate only the sizes the layouts request
 * (150 thumbnail, 480 medium, 960, 1600 large, originals capped at 2400) and write them as WebP.
 */
final class Media implements Module {

	public const MAX_EDGE = 2400;

	/** Sizes no FORMA template or Elementor layout requests. */
	private const UNUSED_SIZES = array( 'medium_large', '1536x1536', '2048x2048' );

	public static function id(): string {
		return 'media';
	}

	public function is_available(): bool {
		return true;
	}

	public function register(): void {
		add_image_size( 'forma-960', 960, 0 );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'drop_unused_sizes' ) );
		add_filter( 'big_image_size_threshold', static fn() => self::MAX_EDGE );
		add_filter( 'image_editor_output_format', array( $this, 'webp_output' ) );
		add_filter( 'image_size_names_choose', array( $this, 'size_names' ) );
	}

	public function drop_unused_sizes( array $sizes ): array {
		return array_diff_key( $sizes, array_flip( self::UNUSED_SIZES ) );
	}

	/**
	 * @param array<string, string> $formats Source mime type => output mime type.
	 */
	public function webp_output( array $formats ): array {
		if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formats['image/jpeg'] = 'image/webp';
			$formats['image/png']  = 'image/webp';
		}

		return $formats;
	}

	/**
	 * Offer the 960 size in the editor's size pickers (Elementor's Image widget reads this list).
	 */
	public function size_names( array $names ): array {
		return $names + array( 'forma-960' => __( 'Half width (960)', 'forma-studio-engine' ) );
	}
}
```

In `src/Plugin.php`:

```php
	private const MODULES = array(
		Projects\Projects::class,
		Media\Media::class,
	);
```

- [ ] **Step 4: Run the tests**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `13 passed, 0 failed`.

- [ ] **Step 5: Checkpoint** — commit message: `feat(engine): trim image sizes, cap originals at 2400px, save WebP`.

---

### Task 5: Child theme rebuild

**Files:**
- Create: `wp-content/themes/forma-hello-child/style.css`
- Create: `wp-content/themes/forma-hello-child/functions.php`
- Create: `wp-content/themes/forma-hello-child/assets/css/site.css`

**Interfaces:**
- Produces: stylesheet handle `forma-site`; CSS custom properties `--forma-plaster`, `--forma-paper`, `--forma-ink`,
  `--forma-graphite`, `--forma-line`, `--forma-line-on-ink`, `--forma-redline`, `--forma-gutter`, `--forma-section`,
  `--forma-ease`, `--forma-font-display`, `--forma-font-sans` (plugin CSS in Plan 3 uses these names).

- [ ] **Step 1: Write the failing check**

Run: `curl -s http://forma.local/ | grep -c "forma-site-css"`
Expected: `0` (the child theme does not exist yet).

- [ ] **Step 2: Create the theme**

`style.css`:

```css
/*
Theme Name:        Forma Hello Child
Theme URI:         https://forma.freedev.app
Description:       Child theme of Hello Elementor for FORMA, a fictional architecture and interiors studio. Presentation only: design tokens, typefaces, base styles and small front-end behaviour. Functionality lives in the Forma Studio Engine plugin.
Author:            Rupash Das
Author URI:        https://devrupash.com
Template:          hello-elementor
Version:           1.0.0
Requires at least: 6.5
Requires PHP:      8.1
License:           GPL-2.0-or-later
Text Domain:       forma-hello-child
*/

/* Styles live in assets/css/site.css. */
```

`functions.php`:

```php
<?php
/**
 * Forma Hello Child — presentation only.
 *
 * Post types, widgets, motion, media and SEO live in the Forma Studio Engine plugin;
 * page layouts live in Elementor.
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMA_THEME_VERSION', '1.0.0' );

add_action( 'wp_enqueue_scripts', 'forma_theme_assets', 20 );

/**
 * Site stylesheet, versioned by file time so edits bust browser caches.
 */
function forma_theme_assets(): void {
	$path = '/assets/css/site.css';

	wp_enqueue_style(
		'forma-site',
		get_stylesheet_directory_uri() . $path,
		array(),
		FORMA_THEME_VERSION . '.' . filemtime( get_stylesheet_directory() . $path )
	);
}
```

`assets/css/site.css`:

```css
/* ==========================================================================
   FORMA — theme-level presentation
   Tokens mirror the Elementor Kit's Global Colors and Fonts so theme and plugin
   CSS share one vocabulary. Layouts themselves are built in Elementor.
   ========================================================================== */

:root {
	--forma-plaster: #e6e4df;
	--forma-paper: #f3f2ef;
	--forma-ink: #121212;
	--forma-graphite: #5c5b57;
	--forma-line: rgba(18, 18, 18, 0.16);
	--forma-line-on-ink: rgba(230, 228, 223, 0.18);
	--forma-redline: #b8321e;

	--forma-gutter: clamp(16px, 3.2vw, 48px);
	--forma-section: clamp(80px, 12vw, 200px);
	--forma-ease: cubic-bezier(0.22, 1, 0.36, 1);

	--forma-font-display: "Bodoni Moda", "Bodoni 72", Didot, Georgia, serif;
	--forma-font-sans: "Archivo", "Helvetica Neue", Arial, sans-serif;
}

html {
	-webkit-text-size-adjust: 100%;
}

body {
	background-color: var(--forma-plaster);
	color: var(--forma-ink);
	font-family: var(--forma-font-sans);
	font-size: 17px;
	line-height: 1.6;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	text-rendering: optimizeLegibility;
}

@media (max-width: 767px) {
	body {
		font-size: 16px;
	}
}

::selection {
	background-color: var(--forma-ink);
	color: var(--forma-plaster);
}

a {
	color: inherit;
}

img {
	height: auto;
	max-width: 100%;
}

/* One focus treatment everywhere: the Redline ring, only for keyboard focus. */
:focus-visible {
	outline: 2px solid var(--forma-redline);
	outline-offset: 3px;
}

.skip-link.screen-reader-text:focus {
	background-color: var(--forma-ink);
	color: var(--forma-plaster);
	font-family: var(--forma-font-sans);
	font-size: 14px;
	padding: 12px 20px;
	z-index: 100000;
}

@media (prefers-reduced-motion: reduce) {
	*,
	*::before,
	*::after {
		animation-duration: 0.01ms !important;
		animation-iteration-count: 1 !important;
		scroll-behavior: auto !important;
		transition-duration: 0.01ms !important;
	}
}
```

- [ ] **Step 3: Activate and verify**

Run: `../../bin/wp theme activate forma-hello-child && curl -s http://forma.local/ | grep -c "forma-site-css" && curl -s -o /dev/null -w "%{http_code}\n" http://forma.local/wp-content/themes/forma-hello-child/assets/css/site.css`
Expected: `Success: Switched to 'Forma Hello Child' theme.`, then `1`, then `200`.

- [ ] **Step 4: Lint**

Run: `/c/Users/DevRupash/AppData/Roaming/Local/lightning-services/php-8.2.29+0/bin/win64/php.exe -n -l wp-content/themes/forma-hello-child/functions.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Checkpoint** — commit message: `feat(theme): rebuild Forma Hello Child with design tokens and base styles`.

---

### Task 6: Content data

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/data/site.php`
- Create: `wp-content/plugins/forma-studio-engine/data/projects.php`
- Test: `wp-content/plugins/forma-studio-engine/tests/DataTest.php`

**Interfaces:**
- Produces: `data/site.php` returns `array{ blog: array{name,description}, types: array<slug, array{name,description}>,
  pages: array<slug, array{title,excerpt}>, front_page: string, menus: array<key, array{name,location,items: list<string>}> }`
  where an item is `archive:{post_type}` or `page:{slug}`.
  `data/projects.php` returns a list (chronological) of `array{ slug, title, type, location, date (Y-m-d), excerpt
  (≤160 chars), facts: array{Client,Area,Status,Team}, labels: list{3 strings}, site, idea, result,
  quote: array{text,cite}, recognition, layout: 'a'|'b'|'c', drawing: bool, featured: int (0 or 1–5) }`.
  Plan 4's ProjectBodies seeder reads every key.

- [ ] **Step 1: Write the failing test**

`tests/DataTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

defined( 'ABSPATH' ) || exit;

final class DataTest extends TestCase {

	private array $projects;
	private array $site;

	protected function set_up(): void {
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
		$this->site     = require FORMA_ENGINE_PATH . 'data/site.php';
	}

	public function test_eleven_projects_with_unique_slugs_in_date_order(): void {
		$this->assert_same( 11, count( $this->projects ) );

		$slugs = array_column( $this->projects, 'slug' );
		$this->assert_same( $slugs, array_values( array_unique( $slugs ) ), 'slugs are unique' );

		$dates = array_column( $this->projects, 'date' );
		$sorted = $dates;
		sort( $sorted );
		$this->assert_same( $sorted, $dates, 'projects are listed oldest first' );
	}

	public function test_every_project_is_complete(): void {
		$keys = array( 'slug', 'title', 'type', 'location', 'date', 'excerpt', 'facts', 'labels', 'site', 'idea', 'result', 'quote', 'recognition', 'layout', 'drawing', 'featured' );

		foreach ( $this->projects as $project ) {
			$slug = $project['slug'];

			$this->assert_same( $keys, array_keys( $project ), "{$slug} keys" );
			$this->assert_true( isset( $this->site['types'][ $project['type'] ] ), "{$slug} type '{$project['type']}' exists" );
			$this->assert_true( 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $project['date'] ), "{$slug} date is Y-m-d" );
			$this->assert_true( 1 === preg_match( '/^[^,]+, [^,]+$/', $project['location'] ), "{$slug} location is 'City, Country'" );
			$this->assert_true( mb_strlen( $project['excerpt'] ) <= 160, "{$slug} excerpt fits a meta description" );
			$this->assert_same( array( 'Client', 'Area', 'Status', 'Team' ), array_keys( $project['facts'] ), "{$slug} facts" );
			$this->assert_same( 3, count( $project['labels'] ), "{$slug} has three section labels" );
			$this->assert_same( array( 'text', 'cite' ), array_keys( $project['quote'] ), "{$slug} quote" );
			$this->assert_true( in_array( $project['layout'], array( 'a', 'b', 'c' ), true ), "{$slug} layout variant" );

			foreach ( array( 'title', 'excerpt', 'site', 'idea', 'result' ) as $field ) {
				$this->assert_true( '' !== trim( $project[ $field ] ), "{$slug} {$field} is written" );
			}
		}
	}

	public function test_five_featured_projects_numbered_one_to_five(): void {
		$featured = array_values( array_filter( array_column( $this->projects, 'featured' ) ) );
		sort( $featured );

		$this->assert_same( array( 1, 2, 3, 4, 5 ), $featured );
	}

	public function test_three_projects_have_the_drawing_comparison(): void {
		$drawing = array_values( array_column( array_filter( $this->projects, static fn( array $p ) => $p['drawing'] ), 'slug' ) );

		$this->assert_same( array( 'concrete-garden', 'atelier-27', 'casa-nera' ), $drawing );
	}

	public function test_site_pages_and_menus_reference_real_pages(): void {
		$this->assert_same( array( 'home', 'studio', 'services', 'process', 'contact', 'colophon' ), array_keys( $this->site['pages'] ) );
		$this->assert_true( isset( $this->site['pages'][ $this->site['front_page'] ] ), 'front page is a seeded page' );

		foreach ( $this->site['menus'] as $menu ) {
			foreach ( $menu['items'] as $item ) {
				[ $kind, $value ] = explode( ':', $item, 2 );
				$ok = 'archive' === $kind ? 'forma_project' === $value : isset( $this->site['pages'][ $value ] );
				$this->assert_true( $ok, "menu item {$item} resolves" );
			}
		}
	}

	public function test_no_placeholder_copy(): void {
		$all = (string) wp_json_encode( array( $this->projects, $this->site ) );

		foreach ( array( 'lorem', 'ipsum', 'placeholder', 'TODO', 'TBD', 'example.com' ) as $word ) {
			$this->assert_true( false === stripos( $all, $word ), "no '{$word}' in the copy" );
		}
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php DataTest`
Expected: FAIL lines with `Failed opening required '…/data/projects.php'`.

- [ ] **Step 3: Write `data/site.php`**

```php
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
```

- [ ] **Step 4: Write `data/projects.php`**

```php
<?php
/**
 * The studio's projects, oldest first. Read by the seeders only: at runtime a project is its post title,
 * excerpt, featured image, post date, taxonomies and Elementor body.
 *
 * featured: position (1–5) in the Home "Selected work" showcase, 0 = not featured.
 * layout:   which of the three project-body layouts the design seeder uses (Plan 4).
 * drawing:  whether the body includes the Drawing / Built comparison.
 */

defined( 'ABSPATH' ) || exit;

$forma_labels = array( 'The site', 'The idea', 'The result' );

return array(
	array(
		'slug'        => 'forma-pavilion',
		'title'       => 'Forma Pavilion',
		'type'        => 'cultural',
		'location'    => 'Venice, Italy',
		'date'        => '2019-05-08',
		'excerpt'     => 'A summer pavilion for talks in Venice, stacked from 412 identical larch beams that went on to become a school library.',
		'facts'       => array(
			'Client' => "Fondazione Ca' Lume",
			'Area'   => '140 m²',
			'Status' => 'Temporary, May–October 2019',
			'Team'   => 'Inês Carvalho, Kenji Aoki, Sami Haddad',
		),
		'labels'      => $forma_labels,
		'site'        => 'A gravel courtyard behind a former rope works in Castello, open to the lagoon wind on one side and enclosed by brick on the other three. The pavilion had to be assembled in eleven days by a crew of six, without cranes, and leave no mark on the ground when it left.',
		'idea'        => 'We designed one beam and repeated it. 412 lengths of untreated larch, each 2.4 metres long, are stacked in alternating directions so the walls breathe and filter the light like a woven screen. Nothing is glued or nailed: the stack is held by its own weight and eight steel rods. Inside, a single round opening frames the sky above the speakers.',
		'result'      => 'Over fourteen weeks the pavilion hosted sixty talks and a weekly night market. In October it was taken down in four days, and every beam was reused for the reading room of a primary school on the Giudecca.',
		'quote'       => array(
			'text' => 'It smelled of timber all summer. People came for the talks and stayed for the light.',
			'cite' => "Giulia Moretti, Programme Director, Fondazione Ca' Lume",
		),
		'recognition' => 'Shortlisted, Temporary Structures Prize 2019',
		'layout'      => 'a',
		'drawing'     => false,
		'featured'    => 0,
	),
	array(
		'slug'        => 'concrete-garden',
		'title'       => 'Concrete Garden',
		'type'        => 'cultural',
		'location'    => 'Lisbon, Portugal',
		'date'        => '2020-09-21',
		'excerpt'     => 'A sunken public garden and shade pavilion on a former tram depot in Marvila, where cast concrete is softened by water and fig trees.',
		'facts'       => array(
			'Client' => 'Marvila Parish Council',
			'Area'   => '1,800 m² site, 120 m² pavilion',
			'Status' => 'Completed 2020',
			'Team'   => 'Tomás Ribeiro, Marta Sousa, Oskar Lindqvist',
		),
		'labels'      => $forma_labels,
		'site'        => 'A hard, sloping yard left behind when the tram depot closed: tarmac, retaining walls and no shade at all. The neighbourhood had almost no public green space, and on summer afternoons the ground reached 45°C.',
		'idea'        => 'We lowered the garden a metre into the ground to find cooler air, and kept the old retaining walls as the edges of new terraces. A long concrete roof, cast in board-marked timber formwork, shades the north side. Rainwater from the roof feeds a narrow channel that runs the length of the garden and waters the fig and olive trees planted beside it.',
		'result'      => 'The garden is now the busiest public space in the parish. The shaded terraces stay up to 14°C cooler than the street on summer afternoons, and the pavilion hosts markets, classes and a Sunday tango.',
		'quote'       => array(
			'text' => 'We expected a park. We got a room outdoors.',
			'cite' => 'Ana Ferreira, Councillor, Marvila Parish Council',
		),
		'recognition' => 'Winner, Iberian Public Space Award 2021',
		'layout'      => 'b',
		'drawing'     => true,
		'featured'    => 5,
	),
	array(
		'slug'        => 'terra-residence',
		'title'       => 'Terra Residence',
		'type'        => 'residential',
		'location'    => 'Mallorca, Spain',
		'date'        => '2021-04-12',
		'excerpt'     => 'A family house of rammed earth and lime in the hills above Deià, built from the soil dug out of its own foundations.',
		'facts'       => array(
			'Client' => 'Private client',
			'Area'   => '520 m²',
			'Status' => 'Completed 2021',
			'Team'   => 'Inês Carvalho, Marta Sousa, Sami Haddad',
		),
		'labels'      => $forma_labels,
		'site'        => 'A terraced olive grove on a steep slope facing the sea, crossed by dry-stone walls that are protected by law. Every lorry that reaches the site has to climb a single-lane road with fourteen hairpin bends.',
		'idea'        => 'Instead of bringing material up the mountain, we built with what was already there. The soil excavated for the foundations was mixed with lime and compacted into walls 60 centimetres thick, in layers that read like the strata of the hill. The house steps down the terraces in four volumes, each sitting between the old stone walls rather than over them.',
		'result'      => 'The earth walls keep the rooms between 21 and 24°C all year without air conditioning. The olive grove is still farmed, and the family presses its own oil in the lowest volume of the house.',
		'quote'       => array(
			'text' => 'The house looks as if it was dug out of the hill, and in a sense it was.',
			'cite' => 'The owners',
		),
		'recognition' => 'Commended, Mediterranean Houses Award 2022',
		'layout'      => 'c',
		'drawing'     => false,
		'featured'    => 0,
	),
	array(
		'slug'        => 'atelier-27',
		'title'       => 'Atelier 27',
		'type'        => 'interiors',
		'location'    => 'Copenhagen, Denmark',
		'date'        => '2022-02-17',
		'excerpt'     => 'A 1920s harbour warehouse in Nordhavn turned into a ceramics studio, kiln room and shop for a growing tableware brand.',
		'facts'       => array(
			'Client' => 'Atelier 27 Ceramics',
			'Area'   => '640 m²',
			'Status' => 'Completed 2022',
			'Team'   => 'Lea Brandt, Oskar Lindqvist, Beatriz Nunes',
		),
		'labels'      => $forma_labels,
		'site'        => 'One long, dark floor with a concrete frame, small windows and a century of layered paint. The brand needed throwing, glazing, two kilns, storage and a shop to share one room without dust from the studio reaching the shelves.',
		'idea'        => 'We kept the warehouse as we found it and added one new element: a long wall of pale oak shelving that divides the floor lengthways. Production runs along the windows; the shop sits on the other side, where customers watch the work through gaps between the shelves. New rooflights over the glazing tables bring north light deep into the plan.',
		'result'      => 'Atelier 27 doubled its production in the first year. The shelving wall holds three thousand pieces at a time, and on Saturdays the studio side opens for public throwing classes.',
		'quote'       => array(
			'text' => 'We make and sell in the same room now, and customers can see the clay on our hands.',
			'cite' => 'Mette Lund, Founder, Atelier 27',
		),
		'recognition' => '',
		'layout'      => 'a',
		'drawing'     => true,
		'featured'    => 4,
	),
	array(
		'slug'        => 'house-of-light',
		'title'       => 'House of Light',
		'type'        => 'interiors',
		'location'    => 'Oslo, Norway',
		'date'        => '2022-11-03',
		'excerpt'     => 'A dark nineteenth-century flat in Frogner opened up to the low northern light with white oak, lime plaster and one deep window seat.',
		'facts'       => array(
			'Client' => 'Private client',
			'Area'   => '165 m²',
			'Status' => 'Completed 2022',
			'Team'   => 'Lea Brandt, Beatriz Nunes',
		),
		'labels'      => $forma_labels,
		'site'        => 'A third-floor flat divided into seven small rooms, facing a narrow courtyard. In winter the sun clears the roofline opposite for only a few hours, and most rooms needed the lights on by early afternoon.',
		'idea'        => 'We removed four partition walls to make one run of rooms along the courtyard, then used every surface to carry light further in. Walls are finished in a pale lime plaster that softens reflections; floors are white-oiled oak. The old window bay became a deep seat lined in oak, the warmest place in the flat on a winter afternoon.',
		'result'      => 'Daylight now reaches the back of the kitchen, eleven metres from the window, and the owners measured a forty per cent fall in their winter lighting bill.',
		'quote'       => array(
			'text' => 'We spend the whole of February in that window seat.',
			'cite' => 'The owners',
		),
		'recognition' => '',
		'layout'      => 'b',
		'drawing'     => false,
		'featured'    => 0,
	),
	array(
		'slug'        => 'monolith-house',
		'title'       => 'Monolith House',
		'type'        => 'residential',
		'location'    => 'Isle of Harris, Scotland',
		'date'        => '2023-05-22',
		'excerpt'     => 'A low stone house on the west coast of Harris, built to sit through Atlantic storms with its back to the wind and its face to the beach.',
		'facts'       => array(
			'Client' => 'Private client',
			'Area'   => '285 m²',
			'Status' => 'Completed 2023',
			'Team'   => 'Tomás Ribeiro, Kenji Aoki, Oskar Lindqvist',
		),
		'labels'      => $forma_labels,
		'site'        => 'An exposed headland above a white-sand beach, where winter winds regularly pass 100 km/h. Planning required the house to be invisible from the single-track road behind it, and local stone was the only material the community wanted to see.',
		'idea'        => 'The house is one wall of local gneiss, 54 metres long, that rises from the ground on the road side and shelters everything behind it. Towards the sea the rooms open through deep timber-lined frames. The roof is planted with machair grasses from the dunes, so from the road the house reads as a fold in the land.',
		'result'      => 'The stone holds the day’s heat into the night, and a ground-source heat pump covers the rest. Neighbours say they only notice the house when its lights come on.',
		'quote'       => array(
			'text' => 'On a storm night the house is completely silent.',
			'cite' => 'The owners',
		),
		'recognition' => 'Winner, Northern Landscape House Prize 2023',
		'layout'      => 'c',
		'drawing'     => false,
		'featured'    => 3,
	),
	array(
		'slug'        => 'axis-workspace',
		'title'       => 'Axis Workspace',
		'type'        => 'workplace',
		'location'    => 'Rotterdam, Netherlands',
		'date'        => '2023-10-09',
		'excerpt'     => 'Four floors of a 1970s office block in Rotterdam reworked around one timber stair, for a company that wanted its people to meet more.',
		'facts'       => array(
			'Client' => 'Axis Mobility',
			'Area'   => '2,150 m²',
			'Status' => 'Completed 2023',
			'Team'   => 'Tomás Ribeiro, Marta Sousa, Oskar Lindqvist',
		),
		'labels'      => $forma_labels,
		'site'        => 'A deep-plan concrete office building with low ceilings, four identical floors and lifts as the only way between them. Teams on different floors had not met in person for months.',
		'idea'        => 'We cut a void through all four floors and built a wide stair of cross-laminated timber inside it, with landings big enough for a table. Meeting rooms and kitchens cluster around the stair, so every trip for coffee crosses another team. Ceilings were stripped back to the concrete, gaining 40 centimetres of height, and the original facade was kept and insulated from inside.',
		'result'      => 'Keeping the structure saved an estimated 1,100 tonnes of embodied carbon compared with a new building. Within six months, Axis reported that most internal meetings were happening on the stair landings.',
		'quote'       => array(
			'text' => 'The stair became our main meeting room. Nobody planned that, except the architects.',
			'cite' => 'Joris de Vries, Chief Operating Officer, Axis Mobility',
		),
		'recognition' => 'Winner, Workplace Reuse Award 2024',
		'layout'      => 'a',
		'drawing'     => false,
		'featured'    => 0,
	),
	array(
		'slug'        => 'casa-nera',
		'title'       => 'Casa Nera',
		'type'        => 'residential',
		'location'    => 'Comporta, Portugal',
		'date'        => '2024-06-14',
		'excerpt'     => 'A black timber house among the pines and rice fields of Comporta, raised above the sand and arranged around a shaded courtyard.',
		'facts'       => array(
			'Client' => 'Private client',
			'Area'   => '410 m²',
			'Status' => 'Completed 2024',
			'Team'   => 'Inês Carvalho, Kenji Aoki, Beatriz Nunes',
		),
		'labels'      => $forma_labels,
		'site'        => 'A sandy plot in a pine forest near the rice fields, in a protected landscape where new buildings must be low, light on the ground and built mainly of timber. Summers are hot and bright, and mosquitoes drift in from the paddies at dusk.',
		'idea'        => 'The house is a ring of rooms around a planted courtyard, raised on timber piles so the sand and its plants run underneath. The outside is clad in charred pine, which needs no paint and weathers to silver-black. Inside, everything is pale: lime-washed pine, linen and cork, with deep eaves that keep the summer sun off the glass.',
		'result'      => 'The courtyard stays shaded and cool through the hottest part of the day, and cross-ventilation means the house runs without air conditioning. The owners live there from April to November.',
		'quote'       => array(
			'text' => 'Black on the outside, all light on the inside. It is exactly what we asked for.',
			'cite' => 'The owners',
		),
		'recognition' => 'Featured, Houses of the Year 2024',
		'layout'      => 'b',
		'drawing'     => true,
		'featured'    => 1,
	),
	array(
		'slug'        => 'the-quiet-hotel',
		'title'       => 'The Quiet Hotel',
		'type'        => 'hospitality',
		'location'    => 'Kyoto, Japan',
		'date'        => '2025-03-28',
		'excerpt'     => 'A 24-room hotel on a quiet lane in Higashiyama, where two restored townhouses and a new garden wing share one long courtyard.',
		'facts'       => array(
			'Client' => 'Shizuka Hospitality',
			'Area'   => '3,200 m², 24 rooms',
			'Status' => 'Completed 2025',
			'Team'   => 'Kenji Aoki, Lea Brandt, Sami Haddad',
		),
		'labels'      => $forma_labels,
		'site'        => 'Five wooden machiya townhouses on a narrow lane, three of them too damaged to keep, with a deep strip of land behind. Local rules limit new buildings to two storeys and require the street front to keep its traditional rhythm.',
		'idea'        => 'We restored the two strongest townhouses with local carpenters, kept the street front unchanged, and placed a new two-storey wing at the back around a garden of moss and maple. A single covered walk links everything. The new wing is built in cedar and plaster with the proportions of the old houses, but its rooms open fully onto the garden.',
		'result'      => 'The hotel opened in spring 2025. There is no lobby and no sign on the street: guests arrive through the first townhouse, which works as a tea room and reception.',
		'quote'       => array(
			'text' => 'Guests tell us the first thing they notice is the silence.',
			'cite' => 'Kenji Watanabe, General Manager, The Quiet Hotel',
		),
		'recognition' => 'Winner, Hospitality Design Award 2025',
		'layout'      => 'c',
		'drawing'     => false,
		'featured'    => 2,
	),
	array(
		'slug'        => 'plinth-series',
		'title'       => 'Plinth Series',
		'type'        => 'objects',
		'location'    => 'Lisbon, Portugal',
		'date'        => '2025-10-01',
		'excerpt'     => 'Seven pieces of furniture in travertine and oak, made from stone offcuts left over from our building projects.',
		'facts'       => array(
			'Client' => 'Self-initiated, made with a stone workshop in Pêro Pinheiro',
			'Area'   => 'Seven pieces',
			'Status' => 'Made to order since 2025',
			'Team'   => 'Inês Carvalho, Sami Haddad',
		),
		'labels'      => array( 'The brief', 'The design', 'The result' ),
		'site'        => 'Every stone floor we specify leaves offcuts behind: slabs too small for a building and too good to crush. Over five years they had piled up at the workshop in Pêro Pinheiro that cuts our stone.',
		'idea'        => 'We designed a family of pieces around the sizes of those offcuts rather than the other way round. A low table, two side tables, a bench, a lamp base, a shelf and a stool are each made from stacked travertine blocks, joined to oak with hidden stainless pins and nothing else. No two pieces share the same veining.',
		'result'      => 'The first run of forty pieces used 2.6 tonnes of stone that would otherwise have been crushed for aggregate. The collection is made to order in the same workshop.',
		'quote'       => array(
			'text' => 'We used to sell these offcuts by the tonne. Now people come to choose them.',
			'cite' => 'Rui Matos, Master Stonemason, Pêro Pinheiro',
		),
		'recognition' => '',
		'layout'      => 'a',
		'drawing'     => false,
		'featured'    => 0,
	),
	array(
		'slug'        => 'northline-residence',
		'title'       => 'Northline Residence',
		'type'        => 'residential',
		'location'    => 'Tromsø, Norway',
		'date'        => '2026-03-16',
		'excerpt'     => 'A house on a fjord north of Tromsø, designed around two months of polar night and two months of midnight sun.',
		'facts'       => array(
			'Client' => 'Private client',
			'Area'   => '340 m²',
			'Status' => 'Under construction, completion 2027',
			'Team'   => 'Tomás Ribeiro, Kenji Aoki, Marta Sousa',
		),
		'labels'      => $forma_labels,
		'site'        => 'A rocky shoreline plot facing west across the fjord to the mountains. From late November to mid-January the sun does not rise; from late May to late July it does not set.',
		'idea'        => 'The house is a long timber volume bent around the rock, with the living room at the point where the views of the fjord and the mountains meet. Windows are placed for the low sun: wide and low to the south-west for winter twilight, small and deep to the north for summer nights. Larch shingles on the walls and roof will weather to grey within a few years.',
		'result'      => 'Construction began in spring 2026, with the timber frame prefabricated in a nearby workshop to fit the short building season. Completion is planned for spring 2027.',
		'quote'       => array(
			'text' => 'We wanted a house for the dark months, not just the light ones.',
			'cite' => 'The owners',
		),
		'recognition' => '',
		'layout'      => 'b',
		'drawing'     => false,
		'featured'    => 0,
	),
);
```

- [ ] **Step 5: Run the tests**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `19 passed, 0 failed`.

- [ ] **Step 6: Checkpoint** — commit message: `content: FORMA site structure and eleven projects`.

---

### Task 7: Setup seeder and `wp forma setup`

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/src/Seed/Setup.php`
- Create: `wp-content/plugins/forma-studio-engine/src/Cli/Command.php`
- Modify: `wp-content/plugins/forma-studio-engine/src/Plugin.php` (CLI registration)
- Test: `wp-content/plugins/forma-studio-engine/tests/SetupTest.php`

**Interfaces:**
- Consumes: `data/site.php` (`blog`), `Projects::register_types()`.
- Produces: `Forma\Engine\Seed\Setup( \Closure $log )` with `run(): void`; `Forma\Engine\Cli\Command` with
  subcommand `setup` (Task 8 adds `content` and `all`). `$log` is `fn( string $message ): void`.

- [ ] **Step 1: Write the failing test**

`tests/SetupTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Setup;

defined( 'ABSPATH' ) || exit;

final class SetupTest extends TestCase {

	public function test_setup_configures_site_media_permalinks_and_elementor(): void {
		( new Setup( static function ( string $message ): void {} ) )->run();

		$this->assert_same( 'FORMA', get_option( 'blogname' ) );
		$this->assert_same( 'Europe/Lisbon', get_option( 'timezone_string' ) );
		$this->assert_same( '/%postname%/', get_option( 'permalink_structure' ) );
		$this->assert_same( 'closed', get_option( 'default_comment_status' ) );
		$this->assert_same( 480, (int) get_option( 'medium_size_w' ) );
		$this->assert_same( 0, (int) get_option( 'medium_size_h' ) );
		$this->assert_same( 1600, (int) get_option( 'large_size_w' ) );
		$this->assert_same( 0, (int) get_option( 'large_size_h' ) );
		$this->assert_same( '0', get_option( 'elementor_google_font' ) );
		$this->assert_same( 'yes', get_option( 'elementor_disable_color_schemes' ) );
		$this->assert_same( 'yes', get_option( 'elementor_disable_typography_schemes' ) );
		$this->assert_same( 'swap', get_option( 'elementor_font_display' ) );
	}

	public function test_setup_turns_on_known_elementor_features(): void {
		( new Setup( static function ( string $message ): void {} ) )->run();

		$experiments = \Elementor\Plugin::$instance->experiments;

		foreach ( array( 'e_font_icon_svg', 'e_optimized_markup' ) as $feature ) {
			if ( $experiments->get_features( $feature ) ) {
				$this->assert_same( 'active', get_option( $experiments->get_feature_option_key( $feature ) ), "{$feature} is active" );
			}
		}
	}

	public function test_cli_command_is_registered(): void {
		$this->assert_true( array_key_exists( 'forma', \WP_CLI::get_root_command()->get_subcommands() ), '`wp forma` is registered' );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php SetupTest`
Expected: `0 passed, 3 failed` (`Class "Forma\Engine\Seed\Setup" not found`, `` `wp forma` is registered ``).

- [ ] **Step 3: Implement `src/Seed/Setup.php`**

```php
<?php

namespace Forma\Engine\Seed;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * Site settings the way an owner would set them in wp-admin: name, time zone, image sizes, permalinks and
 * Elementor's performance options. Safe to rerun.
 */
final class Setup {

	/**
	 * Elementor features for a lean front end. Names this Elementor version doesn't know are skipped
	 * (features graduate to always-on and disappear from the list).
	 */
	private const EXPERIMENTS = array( 'e_font_icon_svg', 'e_optimized_markup', 'e_lazyload', 'container', 'nested-elements' );

	public function __construct( private \Closure $log ) {}

	public function run(): void {
		$this->site();
		$this->media();
		$this->permalinks();
		$this->elementor();
	}

	private function site(): void {
		$site = require FORMA_ENGINE_PATH . 'data/site.php';

		update_option( 'blogname', $site['blog']['name'] );
		update_option( 'blogdescription', $site['blog']['description'] );
		update_option( 'timezone_string', 'Europe/Lisbon' );
		update_option( 'date_format', 'j F Y' );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );

		( $this->log )( 'Site name, time zone and discussion settings.' );
	}

	/**
	 * Medium 480 and large 1600 wide, any height; the 960 size and the 2400 cap come from the Media module.
	 */
	private function media(): void {
		update_option( 'medium_size_w', 480 );
		update_option( 'medium_size_h', 0 );
		update_option( 'large_size_w', 1600 );
		update_option( 'large_size_h', 0 );

		( $this->log )( 'Image sizes: 150, 480, 960, 1600, originals capped at 2400.' );
	}

	private function permalinks(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		Projects::register_types();
		flush_rewrite_rules( false );

		( $this->log )( 'Permalinks: /%postname%/, projects under /projects/.' );
	}

	private function elementor(): void {
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );
		update_option( 'elementor_google_font', '0' );
		update_option( 'elementor_font_display', 'swap' );
		update_option( 'elementor_load_fa4_shim', '' );

		if ( class_exists( \Elementor\Plugin::class ) ) {
			$experiments = \Elementor\Plugin::$instance->experiments;

			foreach ( self::EXPERIMENTS as $feature ) {
				if ( $experiments->get_features( $feature ) ) {
					update_option( $experiments->get_feature_option_key( $feature ), $experiments::STATE_ACTIVE );
				}
			}
		}

		( $this->log )( 'Elementor: kit globals only, Google Fonts off, SVG icons, optimised markup.' );
	}
}
```

- [ ] **Step 4: Implement `src/Cli/Command.php` and register it**

```php
<?php

namespace Forma\Engine\Cli;

use Forma\Engine\Seed\Setup;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the FORMA site from the plugin's data files. Every step is safe to rerun.
 *
 * ## EXAMPLES
 *
 *     wp forma all
 *     wp forma setup
 */
final class Command {

	/**
	 * Configure site, media, permalink and Elementor settings.
	 */
	public function setup(): void {
		( new Setup( $this->logger() ) )->run();
		WP_CLI::success( 'Site configured.' );
	}

	private function logger(): \Closure {
		return static function ( string $message ): void {
			WP_CLI::log( $message );
		};
	}
}
```

In `src/Plugin.php`, inside `boot()`, before the `do_action` call:

```php
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'forma', Cli\Command::class );
		}
```

- [ ] **Step 5: Run the tests**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `22 passed, 0 failed`.

- [ ] **Step 6: Checkpoint** — commit message: `feat(engine): wp forma setup configures site, media and Elementor`.

---

### Task 8: Content seeder and `wp forma content|all`

**Files:**
- Create: `wp-content/plugins/forma-studio-engine/src/Seed/Content.php`
- Modify: `wp-content/plugins/forma-studio-engine/src/Cli/Command.php` (`content`, `all`)
- Test: `wp-content/plugins/forma-studio-engine/tests/ContentTest.php`

**Interfaces:**
- Consumes: `data/site.php`, `data/projects.php`, `Projects::*` constants.
- Produces: `Forma\Engine\Seed\Content( \Closure $log )` with `run()`, `types()`, `projects()`, `pages()`,
  `front_page()`, `menus()`, `page_id( string $slug ): int`, `project_id( string $slug ): int`;
  constant `Content::SEED_KEY = '_forma_seed'` with values `project:{slug}` / `page:{slug}`. Plan 2 (images) and
  Plan 4 (design) find posts through `project_id()` / `page_id()`.

- [ ] **Step 1: Write the failing test**

`tests/ContentTest.php`:

```php
<?php

namespace Forma\Engine\Tests;

use Forma\Engine\Seed\Content;

defined( 'ABSPATH' ) || exit;

final class ContentTest extends TestCase {

	private Content $content;

	protected function set_up(): void {
		$this->content = new Content( static function ( string $message ): void {} );
	}

	public function test_run_creates_eleven_published_projects_with_terms(): void {
		$this->content->run();

		$this->assert_same( 11, count( $this->seeded( 'forma_project' ) ) );

		$casa = get_post( $this->content->project_id( 'casa-nera' ) );
		$this->assert_same( 'Casa Nera', $casa->post_title );
		$this->assert_same( 'publish', $casa->post_status );
		$this->assert_same( '2024', get_the_date( 'Y', $casa ) );
		$this->assert_true( '' !== $casa->post_excerpt, 'Casa Nera has an excerpt' );
		$this->assert_same( array( 'residential' ), wp_get_object_terms( $casa->ID, 'project_type', array( 'fields' => 'slugs' ) ) );
		$this->assert_same( array( 'Comporta, Portugal' ), wp_get_object_terms( $casa->ID, 'project_location', array( 'fields' => 'names' ) ) );
		$this->assert_same( 'http://forma.local/projects/casa-nera/', get_permalink( $casa ) );
	}

	public function test_run_is_idempotent(): void {
		$this->content->run();
		$this->content->run();

		$this->assert_same( 11, count( $this->seeded( 'forma_project' ) ) );
		$this->assert_same( 6, count( $this->seeded( 'page' ) ) );
		$this->assert_same( 6, count( get_terms( array( 'taxonomy' => 'project_type', 'hide_empty' => false ) ) ) );
		$this->assert_same( 5, count( wp_get_nav_menu_items( wp_get_nav_menu_object( 'Primary' )->term_id ) ) );
	}

	public function test_front_page_and_menus(): void {
		$this->content->run();

		$this->assert_same( 'page', get_option( 'show_on_front' ) );
		$this->assert_same( $this->content->page_id( 'home' ), (int) get_option( 'page_on_front' ) );

		$primary = wp_get_nav_menu_object( 'Primary' );
		$items   = wp_get_nav_menu_items( $primary->term_id );

		$this->assert_same( array( 'Projects', 'Studio', 'Services', 'Process', 'Contact' ), wp_list_pluck( $items, 'title' ) );
		$this->assert_same( get_post_type_archive_link( 'forma_project' ), $items[0]->url );
		$this->assert_same( (int) $primary->term_id, get_nav_menu_locations()['menu-1'] ?? 0 );
		$this->assert_same( 'Colophon', wp_list_pluck( wp_get_nav_menu_items( wp_get_nav_menu_object( 'Footer' )->term_id ), 'title' )[5] ?? null );
	}

	public function test_elementor_bodies_are_not_overwritten(): void {
		$this->content->run();

		$id = $this->content->project_id( 'casa-nera' );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => 'Built in Elementor',
			)
		);

		$this->content->run();

		$this->assert_same( 'Built in Elementor', get_post_field( 'post_content', $id ) );
	}

	/**
	 * @return int[]
	 */
	private function seeded( string $post_type ): array {
		return get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => Content::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
			)
		);
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php ContentTest`
Expected: `0 passed, 4 failed` with `Class "Forma\Engine\Seed\Content" not found`.

- [ ] **Step 3: Implement `src/Seed/Content.php`**

```php
<?php

namespace Forma\Engine\Seed;

use Forma\Engine\Projects\Projects;

defined( 'ABSPATH' ) || exit;

/**
 * Creates project types, the projects, pages, the static front page and menus from data/*.php.
 *
 * Every post it creates carries a hidden `_forma_seed` key, so reruns update instead of duplicating.
 * Once a post has been built in Elementor, its body belongs to Elementor and is never overwritten here.
 */
final class Content {

	public const SEED_KEY = '_forma_seed';

	private array $projects;
	private array $site;

	public function __construct( private \Closure $log ) {
		$this->projects = require FORMA_ENGINE_PATH . 'data/projects.php';
		$this->site     = require FORMA_ENGINE_PATH . 'data/site.php';
	}

	public function run(): void {
		$this->types();
		$this->projects();
		$this->pages();
		$this->front_page();
		$this->menus();
	}

	public function types(): void {
		foreach ( $this->site['types'] as $slug => $type ) {
			$existing = term_exists( $slug, Projects::TYPE_TAX );
			$args     = array(
				'slug'        => $slug,
				'description' => $type['description'],
			);

			if ( $existing ) {
				wp_update_term( (int) $existing['term_id'], Projects::TYPE_TAX, $args + array( 'name' => $type['name'] ) );
			} else {
				wp_insert_term( $type['name'], Projects::TYPE_TAX, $args );
			}
		}

		( $this->log )( sprintf( '%d project types.', count( $this->site['types'] ) ) );
	}

	public function projects(): void {
		foreach ( $this->projects as $project ) {
			$id = $this->upsert(
				'project:' . $project['slug'],
				array(
					'post_type'      => Projects::POST_TYPE,
					'post_status'    => 'publish',
					'post_title'     => $project['title'],
					'post_name'      => $project['slug'],
					'post_excerpt'   => $project['excerpt'],
					'post_content'   => $this->fallback_body( $project ),
					'post_date'      => $project['date'] . ' 10:00:00',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			wp_set_object_terms( $id, $project['type'], Projects::TYPE_TAX );
			wp_set_object_terms( $id, $project['location'], Projects::LOCATION_TAX );
		}

		( $this->log )( sprintf( '%d projects.', count( $this->projects ) ) );
	}

	public function pages(): void {
		foreach ( $this->site['pages'] as $slug => $page ) {
			$this->upsert(
				'page:' . $slug,
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_title'     => $page['title'],
					'post_name'      => $slug,
					'post_excerpt'   => $page['excerpt'],
					'post_content'   => '',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);
		}

		( $this->log )( sprintf( '%d pages.', count( $this->site['pages'] ) ) );
	}

	public function front_page(): void {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->page_id( $this->site['front_page'] ) );

		( $this->log )( 'Static front page: ' . $this->site['front_page'] . '.' );
	}

	/**
	 * Menus are rebuilt from scratch on each run; only the menu items this seeder created are replaced.
	 */
	public function menus(): void {
		$locations  = get_nav_menu_locations();
		$registered = get_registered_nav_menus();

		foreach ( $this->site['menus'] as $menu ) {
			$object  = wp_get_nav_menu_object( $menu['name'] );
			$menu_id = $object ? (int) $object->term_id : (int) wp_create_nav_menu( $menu['name'] );

			foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
				wp_delete_post( $item->ID, true );
			}

			foreach ( $menu['items'] as $index => $item ) {
				wp_update_nav_menu_item( $menu_id, 0, $this->menu_item( $item, $index + 1 ) );
			}

			if ( isset( $registered[ $menu['location'] ] ) ) {
				$locations[ $menu['location'] ] = $menu_id;
			}
		}

		set_theme_mod( 'nav_menu_locations', $locations );

		( $this->log )( sprintf( '%d menus.', count( $this->site['menus'] ) ) );
	}

	public function page_id( string $slug ): int {
		return $this->find( 'page', 'page:' . $slug );
	}

	public function project_id( string $slug ): int {
		return $this->find( Projects::POST_TYPE, 'project:' . $slug );
	}

	private function find( string $post_type, string $key ): int {
		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => self::SEED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- seed lookup, CLI only.
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	private function upsert( string $key, array $postarr ): int {
		$existing = $this->find( $postarr['post_type'], $key );

		if ( $existing ) {
			$postarr['ID'] = $existing;

			if ( 'builder' === get_post_meta( $existing, '_elementor_edit_mode', true ) ) {
				unset( $postarr['post_content'] );
			}
		}

		$id = wp_insert_post( wp_slash( $postarr ), true );

		if ( is_wp_error( $id ) ) {
			throw new \RuntimeException( esc_html( $key . ': ' . $id->get_error_message() ) );
		}

		update_post_meta( $id, self::SEED_KEY, $key );

		return (int) $id;
	}

	/**
	 * Plain HTML used until the project's Elementor body is built; also what feeds and search read.
	 */
	private function fallback_body( array $project ): string {
		$html = '';

		foreach ( array( 'site', 'idea', 'result' ) as $index => $field ) {
			$html .= sprintf( "<h2>%s</h2>\n<p>%s</p>\n", esc_html( $project['labels'][ $index ] ), esc_html( $project[ $field ] ) );
		}

		return $html;
	}

	/**
	 * @param string $ref "archive:{post_type}" or "page:{slug}".
	 */
	private function menu_item( string $ref, int $position ): array {
		[ $kind, $value ] = explode( ':', $ref, 2 );

		if ( 'archive' === $kind ) {
			return array(
				'menu-item-type'     => 'post_type_archive',
				'menu-item-object'   => $value,
				'menu-item-title'    => get_post_type_object( $value )->labels->name,
				'menu-item-status'   => 'publish',
				'menu-item-position' => $position,
			);
		}

		$id = $this->page_id( $value );

		return array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $id,
			'menu-item-title'     => get_the_title( $id ),
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $position,
		);
	}
}
```

- [ ] **Step 4: Add `content` and `all` to the CLI command**

In `src/Cli/Command.php` add `use Forma\Engine\Seed\Content;` and these methods after `setup()`:

```php
	/**
	 * Create project types, the eleven projects, pages, the front page and menus.
	 */
	public function content(): void {
		( new Content( $this->logger() ) )->run();
		WP_CLI::success( 'Content seeded.' );
	}

	/**
	 * Run every build step in order.
	 */
	public function all(): void {
		$this->setup();
		$this->content();
	}
```

Update the class docblock examples to:

```php
 * ## EXAMPLES
 *
 *     wp forma all
 *     wp forma setup
 *     wp forma content
```

- [ ] **Step 5: Run the tests**

Run: `../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `26 passed, 0 failed`.

- [ ] **Step 6: Checkpoint** — commit message: `feat(engine): wp forma content seeds projects, pages and menus`.

---

### Task 9: Build the site and verify end to end

**Files:** none created; runs the build against the real database.

- [ ] **Step 1: Run the build**

Run: `../../bin/wp forma all`
Expected: log lines for site, image sizes, permalinks, Elementor, `6 project types.`, `11 projects.`, `6 pages.`,
`Static front page: home.`, `2 menus.`, and two `Success:` lines.

- [ ] **Step 2: Run it again (idempotency on real data)**

Run: `../../bin/wp forma content && ../../bin/wp post list --post_type=forma_project --orderby=date --order=asc --fields=ID,post_name,post_date --format=table && ../../bin/wp post list --post_type=page --fields=ID,post_name,post_status`
Expected: still exactly 11 projects, oldest `forma-pavilion` 2019-05-08 to newest `northline-residence`
2026-03-16; pages home, studio, services, process, contact, colophon published (plus the pre-existing
"testing page" and draft Privacy Policy, untouched).

- [ ] **Step 3: Check every route**

```bash
for p in / /projects/ /projects/casa-nera/ /projects/type/residential/ /projects/location/kyoto-japan/ /studio/ /services/ /process/ /contact/ /colophon/; do printf "%-38s %s\n" "$p" "$(curl -s -o /dev/null -w '%{http_code}' "http://forma.local$p")"; done
```

Expected: `200` for every path.

- [ ] **Step 4: Check the debug log and full test suite**

Run: `(grep -i -E "forma|fatal" wp-content/debug.log || echo "no forma/fatal entries") && ../../bin/wp eval-file wp-content/plugins/forma-studio-engine/tests/run.php`
Expected: `no forma/fatal entries` and `26 passed, 0 failed`.

- [ ] **Step 5: Look at it**

In the browser: `http://forma.local/projects/` lists the 11 projects (Hello's plain archive for now),
`http://forma.local/projects/casa-nera/` shows the title and the three fallback sections, and wp-admin → Projects
shows the Types and Locations columns. Report results to the owner.

- [ ] **Step 6: Checkpoint** — commit message: `chore: Plan 1 complete — foundation and content model`.
