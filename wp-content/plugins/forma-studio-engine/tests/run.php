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
