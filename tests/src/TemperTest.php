<?php

declare(strict_types=1);

namespace DanBettles\Temper\Tests;

use DanBettles\Temper\Temper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

use function basename;
use function unlink;

use const null;

class TemperTest extends TestCase
{
    private string $fixturesDir;

    public function testIsInstantiable()
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);
        $temper = new Temper($fixturesDir);

        $this->assertSame($fixturesDir, $temper->getDir());
    }

    public function testThrowsAnExceptionIfTheDirectoryDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('~^The directory, `[^`]+`, does not exist\.$~');

        new Temper($this->createFixturePathname('non_existent_subdir'));
    }

    public function testCreatefileCreatesANewTempFileAndReturnsItsPathname()
    {
        $tempFilePathname = null;

        try {
            $fixturesDir = $this->createFixturePathname(__FUNCTION__);

            $temper = new Temper($fixturesDir);
            $tempFilePathname = $temper->createFile();

            $expectedPathname = "{$fixturesDir}/" . basename($tempFilePathname);

            $this->assertSame($expectedPathname, $tempFilePathname);
            $this->assertFileExists($tempFilePathname);
        } finally {
            @unlink($tempFilePathname);
        }
    }

    public function testCreatefileCanCreateATempFileWithAParticularExtension()
    {
        $tempFilePathname = null;

        try {
            $fixturesDir = $this->createFixturePathname(__FUNCTION__);

            $temper = new Temper($fixturesDir);
            $tempFilePathname = $temper->createFile('txt');

            $expectedPathname = "{$fixturesDir}/" . basename($tempFilePathname);

            $this->assertSame($expectedPathname, $tempFilePathname);
            $this->assertStringEndsWith('.txt', $tempFilePathname);
            $this->assertFileExists($tempFilePathname);
        } finally {
            @unlink($tempFilePathname);
        }
    }

    public function testCleanupRemovesRemainingTempFiles()
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));

        $tempFilePathname1 = $temper->createFile();
        $tempFilePathname2 = $temper->createFile();

        $temper->cleanUp();

        $this->assertFileDoesNotExist($tempFilePathname1);
        $this->assertFileDoesNotExist($tempFilePathname2);

        // Will do nothing because there are no remaining temp files.
        $temper->cleanUp();
    }

    public function testCleanupDoesNotCareIfFilesDoNotExist()
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));

        $tempFilePathname1 = $temper->createFile();
        $tempFilePathname2 = $temper->createFile();

        unlink($tempFilePathname1);

        $this->assertFileDoesNotExist($tempFilePathname1);
        $this->assertFileExists($tempFilePathname2);

        $temper->cleanUp();

        $this->assertFileDoesNotExist($tempFilePathname2);
    }

    public function testConsumefileCreatesANewTempFileAndRemovesItImmediatelyAfterUse()
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);

        $tempFilePathname = '';

        $closureReturnValue = (new Temper($fixturesDir))->consumeFile(function (string $pathname) use (
            $fixturesDir,
            &$tempFilePathname
        ) {
            $tempFilePathname = $pathname;

            $expectedPathname = "{$fixturesDir}/" . basename($tempFilePathname);

            $this->assertSame($expectedPathname, $tempFilePathname);
            $this->assertFileExists($tempFilePathname);

            return 'Something from inside closure.';
        });

        $this->assertFileDoesNotExist($tempFilePathname);
        $this->assertSame('Something from inside closure.', $closureReturnValue);
    }

    public function testConsumefileCanCreateATempFileWithAParticularExtension()
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);

        $tempFilePathname = '';

        $closureReturnValue = (new Temper($fixturesDir))->consumeFile(function (string $pathname) use (
            $fixturesDir,
            &$tempFilePathname
        ) {
            $tempFilePathname = $pathname;

            $expectedPathname = "{$fixturesDir}/" . basename($tempFilePathname);

            $this->assertSame($expectedPathname, $tempFilePathname);
            $this->assertStringEndsWith('.jpg', $tempFilePathname);
            $this->assertFileExists($tempFilePathname);

            return 'Something from inside closure.';
        }, 'jpg');

        $this->assertFileDoesNotExist($tempFilePathname);
        $this->assertSame('Something from inside closure.', $closureReturnValue);
    }

    private function createFixturePathname(string $basename = ''): string
    {
        if (!isset($this->fixturesDir)) {
            $this->fixturesDir = __DIR__ . '/' . (new ReflectionClass($this))->getShortName();
        }

        return $this->fixturesDir . ('' === $basename ? '' : "/{$basename}");
    }
}
