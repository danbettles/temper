<?php

declare(strict_types=1);

namespace DanBettles\Temper;

use Closure;
use ReflectionClass;
use RuntimeException;

use function is_dir;
use function is_file;
use function rename;
use function tempnam;
use function unlink;

use const false;
use const null;

class Temper
{
    private string $tempDir;

    private string $basenamePrefix;

    /**
     * @var array<string,string>
     */
    private array $tempFilePathnames = [];

    public function __construct(string $tempDir)
    {
        $this
            ->setTempDir($tempDir)
            ->setBasenamePrefix((new ReflectionClass($this))->getShortName() . '_')
        ;
    }

    /**
     * Creates a temp-file in the registered directory and returns its pathname
     *
     * @throws RuntimeException If it failed to create a temp file
     */
    private function createTempFileOnly(?string $extension): string
    {
        $pathname = tempnam($this->getTempDir(), $this->getBasenamePrefix());

        if (false === $pathname) {
            throw new RuntimeException('Failed to create a temp file');
        }

        if (null !== $extension) {
            rename($pathname, $pathname .= ".{$extension}");
        }

        return $pathname;
    }

    /**
     * Creates, and remembers, a temp-file and returns its pathname
     */
    public function createFile(string $extension = null): string
    {
        $pathname = $this->createTempFileOnly($extension);
        // Remember the temp file
        $this->tempFilePathnames[$pathname] = $pathname;

        return $pathname;
    }

    private function removeFileOnly(string $pathname): void
    {
        if (is_file($pathname)) {
            unlink($pathname);
        }
    }

    /**
     * Creates a new temp-file and passes the pathname to the closure; the temp-file is removed immediately after the
     * closure returns
     */
    public function consumeFile(
        Closure $closure,
        string $extension = null,
    ): mixed {
        $pathname = $this->createTempFileOnly($extension);

        try {
            return $closure($pathname);
        } finally {
            $this->removeFileOnly($pathname);
        }
    }

    /**
     * Removes *all* remaining temp files
     */
    public function cleanUp(): void
    {
        foreach ($this->tempFilePathnames as $pathname) {
            $this->removeFileOnly($pathname);
            // Forget the temp file
            unset($this->tempFilePathnames[$pathname]);
        }
    }

    /**
     * @throws RuntimeException If the directory does not exist
     */
    private function setTempDir(string $dir): self
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("The directory, `{$dir}`, does not exist");
        }

        $this->tempDir = $dir;

        return $this;
    }

    public function getTempDir(): string
    {
        return $this->tempDir;
    }

    private function setBasenamePrefix(string $prefix): self
    {
        $this->basenamePrefix = $prefix;

        return $this;
    }

    private function getBasenamePrefix(): string
    {
        return $this->basenamePrefix;
    }
}
