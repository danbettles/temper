<?php
// phpcs:ignoreFile

use DanBettles\Temper\Temper;

$projectDir = __DIR__ . '/../..';

require "{$projectDir}/vendor/autoload.php";

$temper = new Temper("{$projectDir}/var/tmp");
// $temper = new Temper("/path/to/temper/var/tmp");

$pathnameCreatedInConsumeFile = '';

$temper->consumeFile(function (string $tempFilePathname) use (&$pathnameCreatedInConsumeFile): void {
    $pathnameCreatedInConsumeFile = $tempFilePathname;

    var_dump($tempFilePathname);  // => `string(37) "/path/to/temper/var/tmp/Temper_n184kh"`
    var_dump(is_file($tempFilePathname));  // => `bool(true)`
});

var_dump(is_file($pathnameCreatedInConsumeFile));  // => `bool(false)`

$temper->consumeFile(function (string $tempFilePathname) use (&$pathnameCreatedInConsumeFile): void {
    $pathnameCreatedInConsumeFile = $tempFilePathname;

    var_dump($tempFilePathname);  // => `string(41) "/path/to/temper/var/tmp/Temper_NBrsVf.jpg"`
    var_dump(is_file($tempFilePathname));  // => `bool(true)`
}, 'jpg');

var_dump(is_file($pathnameCreatedInConsumeFile));  // => `bool(false)`
