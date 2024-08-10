<?php
// phpcs:ignoreFile

use DanBettles\Temper\Temper;

$projectDir = __DIR__ . '/../..';

require "{$projectDir}/vendor/autoload.php";

$temper = new Temper("{$projectDir}/var/tmp");

$tempFileWithoutExtension = $temper->createFile();

assert($tempFileWithoutExtension instanceof SplFileInfo);
assert($tempFileWithoutExtension->isFile());

$tempImageFile = $temper->createFile('jpg');

assert($tempImageFile instanceof SplFileInfo);
assert($tempImageFile->isFile());

$temper->cleanUp();

/** @phpstan-ignore-next-line */
assert(!$tempFileWithoutExtension->isFile());
/** @phpstan-ignore-next-line */
assert(!$tempImageFile->isFile());
