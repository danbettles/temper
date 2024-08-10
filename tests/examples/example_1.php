<?php
// phpcs:ignoreFile

use DanBettles\Temper\Temper;

$projectDir = __DIR__ . '/../..';

require "{$projectDir}/vendor/autoload.php";

$temper = new Temper("{$projectDir}/var/tmp");

$fileinfoFromConsumefile = null;

$temper->consumeFile(function (SplFileInfo $tempFileinfo) use (&$fileinfoFromConsumefile): void {
    $fileinfoFromConsumefile = $tempFileinfo;

    assert($tempFileinfo->isFile());
});

/** @var SplFileInfo $fileinfoFromConsumefile */

assert(!$fileinfoFromConsumefile->isFile());

$fileinfoFromConsumefile = null;

$temper->consumeFile(function (SplFileInfo $tempFileinfo) use (&$fileinfoFromConsumefile): void {
    $fileinfoFromConsumefile = $tempFileinfo;

    assert($tempFileinfo->isFile());
}, 'jpg');

/** @var SplFileInfo $fileinfoFromConsumefile */

assert($fileinfoFromConsumefile->isFile());
