<?php
// phpcs:ignoreFile

use DanBettles\Temper\Temper;

$projectDir = __DIR__ . '/../..';

require "{$projectDir}/vendor/autoload.php";

$temper = new Temper("{$projectDir}/var/tmp");
// $temper = new Temper("/path/to/temper/var/tmp");

$tempFilePathnameWithoutExtension = $temper->createFile();
var_dump($tempFilePathnameWithoutExtension);  // => `string(37) "/path/to/temper/var/tmp/Temper_foBD75"`
var_dump(is_file($tempFilePathnameWithoutExtension));  // => `bool(true)`

$tempImageFilePathname = $temper->createFile('jpg');
var_dump($tempImageFilePathname);  // => `string(41) "/path/to/temper/var/tmp/Temper_gc3UF4.jpg"`
var_dump(is_file($tempImageFilePathname));  // => `bool(true)`

$temper->cleanUp();

var_dump(is_file($tempFilePathnameWithoutExtension));  // => `bool(false)`
var_dump(is_file($tempImageFilePathname));  // => `bool(false)`
