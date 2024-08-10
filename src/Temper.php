<?php

declare(strict_types=1);

namespace DanBettles\Temper;

use Closure;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

use function is_dir;
use function rename;
use function tempnam;
use function unlink;

use const false;
use const null;
use const true;

class Temper
{
    private string $basenamePrefix;

    /**
     * Pathname => SplFileInfo
     *
     * @var array<string,SplFileInfo>
     */
    private array $tempFiles;

    /**
     * @throws RuntimeException If the directory does not exist
     */
    public function __construct(
        private string $tempDir,
    ) {
        if (!is_dir($tempDir)) {
            throw new RuntimeException("The directory, `{$tempDir}`, does not exist");
        }

        $this->basenamePrefix = (new ReflectionClass($this))->getShortName() . '_';
        $this->tempFiles = [];
    }

    public function getTempDir(): string
    {
        return $this->tempDir;
    }

    public function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * Creates a temp-file in the registered temp-directory
     *
     * @throws RuntimeException If it failed to create a temp-file
     */
    private function createTempFileOnly(?string $extension): SplFileInfo
    {
        $pathname = tempnam($this->getTempDir(), $this->basenamePrefix);

        if (false === $pathname) {
            throw new RuntimeException('Failed to create a temp-file');
        }

        if (null !== $extension) {
            rename($pathname, $pathname .= ".{$extension}");
        }

        return new SplFileInfo($pathname);
    }

    /**
     * Creates, and remembers, a temp-file
     */
    public function createFile(string $extension = null): SplFileInfo
    {
        $fileInfo = $this->createTempFileOnly($extension);
        // Remember the temp-file
        $this->tempFiles[$fileInfo->getPathname()] = $fileInfo;

        return $fileInfo;
    }

    private function removeFileOnly(
        SplFileInfo $fileInfo,
        bool $force = true,
    ): void {
        if ($force && !$fileInfo->isFile()) {
            return;
        }

        unlink($fileInfo->getPathname());
    }

    /**
     * Creates a new temp-file and passes a `SplFileInfo` to the closure; the temp-file is removed immediately after the
     * closure returns
     */
    public function consumeFile(
        Closure $closure,
        string $extension = null,
    ): mixed {
        $fileInfo = $this->createTempFileOnly($extension);

        try {
            return $closure($fileInfo);
        } finally {
            $this->removeFileOnly($fileInfo);
        }
    }

    /**
     * Removes *all* remaining temp-files
     */
    public function cleanUp(): void
    {
        foreach ($this->tempFiles as $fileInfo) {
            $this->removeFileOnly($fileInfo);
            // Forget the temp-file
            unset($this->tempFiles[$fileInfo->getPathname()]);
        }
    }
}
