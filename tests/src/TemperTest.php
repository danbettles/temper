<?php

declare(strict_types=1);

namespace DanBettles\Temper\Tests;

use DanBettles\Temper\Temper;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\ErrorException;
use ReflectionClass;
use RuntimeException;

use function basename;
use function trigger_error;
use function unlink;

use const E_USER_ERROR;
use const null;

class TemperTest extends TestCase
{
    private string $fixturesDir;

    private function createFixturePathname(string $basename = ''): string
    {
        if (!isset($this->fixturesDir)) {
            $this->fixturesDir = __DIR__ . '/' . (new ReflectionClass($this))->getShortName();
        }

        return $this->fixturesDir . ('' === $basename ? '' : "/{$basename}");
    }

    public function testIsInstantiable(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);
        $temper = new Temper($fixturesDir);

        $this->assertSame($fixturesDir, $temper->getTempDir());
    }

    public function testThrowsAnExceptionIfTheDirectoryDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('~^The directory, `[^`]+`, does not exist$~');

        new Temper($this->createFixturePathname('non_existent_subdir'));
    }

    public function testCreatefileCreatesANewTempFileAndReturnsItsPathname(): void
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
            if (null !== $tempFilePathname) {
                unlink($tempFilePathname);
            }
        }
    }

    public function testCreatefileCanCreateATempFileWithAParticularExtension(): void
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
            if (null !== $tempFilePathname) {
                unlink($tempFilePathname);
            }
        }
    }

    public function testCleanupRemovesRemainingTempFiles(): void
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

    public function testCleanupDoesNotCareIfFilesDoNotExist(): void
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

    public function testConsumefileCreatesANewTempFileAndRemovesItImmediatelyAfterUse(): void
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

    public function testConsumefileCanCreateATempFileWithAParticularExtension(): void
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

    public function testConsumefileRemovesTheTempFileIfAnExceptionIsThrownInTheClosure(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);
        $temper = new Temper($fixturesDir);
        $actualTempFilePathname = null;

        try {
            $temper->consumeFile(function (string $tempFilePathname) use (&$actualTempFilePathname): void {
                $this->assertFileExists($actualTempFilePathname = $tempFilePathname);

                throw new RuntimeException('Bam!');
            });
        } catch (Exception $ex) {
            $this->assertInstanceOf(RuntimeException::class, $ex);
            $this->assertSame('Bam!', $ex->getMessage());
        }

        /** @var string $actualTempFilePathname */

        $this->assertFileDoesNotExist($actualTempFilePathname);
    }

    public function testConsumefileRemovesTheTempFileIfAnErrorOccursInTheClosure(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);
        $temper = new Temper($fixturesDir);
        $actualTempFilePathname = null;

        try {
            $temper->consumeFile(function (string $tempFilePathname) use (&$actualTempFilePathname): void {
                $this->assertFileExists($actualTempFilePathname = $tempFilePathname);

                @trigger_error('Pow!', E_USER_ERROR);
            });
        } catch (ErrorException $ex) {
            $this->assertSame('E_USER_ERROR was triggered', $ex->getMessage());
        }

        /** @var string $actualTempFilePathname */

        $this->assertFileDoesNotExist($actualTempFilePathname);
    }
}
