<?php

declare(strict_types=1);

namespace DanBettles\Temper\Tests;

use DanBettles\Temper\Temper;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\ErrorException;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

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

    public function testCreatefileCreatesANewTempFileAndReturnsASplfileinfo(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);
        $temper = new Temper($fixturesDir);
        $tempFileinfo = null;

        try {
            /** @var SplFileInfo */
            $tempFileinfo = $temper->createFile();

            $this->assertInstanceOf(SplFileInfo::class, $tempFileinfo);
            // Make sure the file is where we're expecting it to be
            $this->assertSame("{$fixturesDir}/" . $tempFileinfo->getBasename(), $tempFileinfo->getPathname());
            // Make sure the file actually exists
            $this->assertTrue($tempFileinfo->isFile());
        } finally {
            if (null !== $tempFileinfo) {
                unlink($tempFileinfo->getPathname());
            }
        }
    }

    public function testCreatefileCanCreateATempFileWithAParticularExtension(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);
        $temper = new Temper($fixturesDir);
        $tempFileinfo = null;

        try {
            /** @var SplFileInfo */
            $tempFileinfo = $temper->createFile('txt');

            $this->assertSame("{$fixturesDir}/" . $tempFileinfo->getBasename(), $tempFileinfo->getPathname());
            $this->assertSame('txt', $tempFileinfo->getExtension());
            $this->assertTrue($tempFileinfo->isFile());
        } finally {
            if (null !== $tempFileinfo) {
                unlink($tempFileinfo->getPathname());
            }
        }
    }

    public function testCleanupRemovesRemainingTempFiles(): void
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));

        /** @var SplFileInfo */
        $tempFileinfo1 = $temper->createFile();
        /** @var SplFileInfo */
        $tempFileinfo2 = $temper->createFile();

        $temper->cleanUp();

        $this->assertFalse($tempFileinfo1->isFile());
        $this->assertFalse($tempFileinfo2->isFile());

        // Will do nothing because there are no remaining temp-files
        $temper->cleanUp();
    }

    public function testCleanupDoesNotCareIfFilesDoNotExist(): void
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));

        /** @var SplFileInfo */
        $tempFileinfo1 = $temper->createFile();
        /** @var SplFileInfo */
        $tempFileinfo2 = $temper->createFile();

        unlink($tempFileinfo1->getPathname());

        $this->assertFalse($tempFileinfo1->isFile());
        $this->assertTrue($tempFileinfo2->isFile());

        $temper->cleanUp();

        $this->assertFalse($tempFileinfo2->isFile());
    }

    public function testConsumefileCreatesANewTempFileAndRemovesItImmediatelyAfterUse(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);

        $tempFileinfo = null;

        $closureReturnValue = (new Temper($fixturesDir))->consumeFile(function ($closureInput) use (
            $fixturesDir,
            &$tempFileinfo,
        ) {
            $tempFileinfo = $closureInput;

            $this->assertInstanceOf(SplFileInfo::class, $closureInput);

            /** @var SplFileInfo $tempFileinfo */

            $this->assertSame("{$fixturesDir}/" . $tempFileinfo->getBasename(), $tempFileinfo->getPathname());
            $this->assertTrue($tempFileinfo->isFile());

            return 'Something from inside closure.';
        });

        /** @var SplFileInfo $tempFileinfo */

        $this->assertSame('Something from inside closure.', $closureReturnValue);
        $this->assertFalse($tempFileinfo->isFile());
    }

    public function testConsumefileCanCreateATempFileWithAParticularExtension(): void
    {
        $fixturesDir = $this->createFixturePathname(__FUNCTION__);

        $tempFileinfo = null;

        $closureReturnValue = (new Temper($fixturesDir))->consumeFile(function ($closureInput) use (
            $fixturesDir,
            &$tempFileinfo,
        ) {
            $tempFileinfo = $closureInput;

            $this->assertInstanceOf(SplFileInfo::class, $closureInput);

            /** @var SplFileInfo $tempFileinfo */

            $this->assertSame("{$fixturesDir}/" . $tempFileinfo->getBasename(), $tempFileinfo->getPathname());
            $this->assertSame('jpg', $tempFileinfo->getExtension());
            $this->assertTrue($tempFileinfo->isFile());

            return 'Something from inside closure.';
        }, 'jpg');

        /** @var SplFileInfo $tempFileinfo */

        $this->assertSame('Something from inside closure.', $closureReturnValue);
        $this->assertFalse($tempFileinfo->isFile());
    }

    public function testConsumefileRemovesTheTempFileIfAnExceptionIsThrownInTheClosure(): void
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));
        $tempFileinfo = null;

        try {
            $temper->consumeFile(function (SplFileInfo $closureInput) use (&$tempFileinfo): void {
                $tempFileinfo = $closureInput;

                // More for clarity's sake -- because this has already been tested
                $this->assertTrue($closureInput->isFile());

                throw new RuntimeException('Bam!');
            });
        } catch (Exception $ex) {
            $this->assertInstanceOf(RuntimeException::class, $ex);
            $this->assertSame('Bam!', $ex->getMessage());
        }

        /** @var SplFileInfo $tempFileinfo */

        $this->assertFalse($tempFileinfo->isFile());
    }

    public function testConsumefileRemovesTheTempFileIfAPhpErrorOccursInTheClosure(): void
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));
        $tempFileinfo = null;

        try {
            $temper->consumeFile(function (SplFileInfo $closureInput) use (&$tempFileinfo): void {
                $tempFileinfo = $closureInput;

                // More for clarity's sake -- because this has already been tested
                $this->assertTrue($closureInput->isFile());

                @trigger_error('Pow!', E_USER_ERROR);
            });
        } catch (ErrorException $ex) {
            $this->assertSame('E_USER_ERROR was triggered', $ex->getMessage());
        }

        /** @var SplFileInfo $tempFileinfo */

        $this->assertFalse($tempFileinfo->isFile());
    }

    public function testDestructorRemovesAllRemainingTempFiles(): void
    {
        $temper = new Temper($this->createFixturePathname(__FUNCTION__));

        /** @var SplFileInfo */
        $tempFileinfo1 = $temper->createFile();
        /** @var SplFileInfo */
        $tempFileinfo2 = $temper->createFile();

        $this->assertTrue($tempFileinfo1->isFile());
        $this->assertTrue($tempFileinfo2->isFile());

        unset($temper);

        $this->assertFalse($tempFileinfo1->isFile());
        $this->assertFalse($tempFileinfo2->isFile());
    }
}
