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

use const null;

class Temper
{
    private string $dir;

    private string $basenamePrefix;

    /**
     * @var array<string, string>
     */
    private array $pathnames = [];

    public function __construct(string $dir)
    {
        $this
            ->setDir($dir)
            ->setBasenamePrefix((new ReflectionClass($this))->getShortName() . '_')
        ;
    }

    /**
     * Creates a temp-file in the registered directory and returns its pathname.
     */
    public function createFile(string $extension = null): string
    {
        $pathname = tempnam($this->getDir(), $this->getBasenamePrefix());

        if (null !== $extension) {
            rename($pathname, $pathname .= ".{$extension}");
        }

        $this->rememberFile($pathname);

        return $pathname;
    }

    private function removeFile(string $pathname): void
    {
        if (is_file($pathname)) {
            unlink($pathname);
        }

        $this->forgetFile($pathname);
    }

    /**
     * Creates a new temp-file and passes the pathname to the closure; the temp-file is removed immediately after the
     * closure returns.
     *
     * @return mixed
     */
    public function consumeFile(Closure $closure, string $extension = null)
    {
        $pathname = $this->createFile($extension);
        $closureReturnValue = $closure($pathname);
        $this->removeFile($pathname);

        return $closureReturnValue;
    }

    /**
     * Removes remaining temp files.
     */
    public function cleanUp(): void
    {
        foreach ($this->pathnames as $pathname) {
            $this->removeFile($pathname);
        }
    }

    /**
     * @throws RuntimeException If the directory does not exist.
     */
    private function setDir(string $dir): self
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("The directory, `{$dir}`, does not exist.");
        }

        $this->dir = $dir;

        return $this;
    }

    public function getDir(): string
    {
        return $this->dir;
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

    private function rememberFile(string $pathname): self
    {
        $this->pathnames[$pathname] = $pathname;
        return $this;
    }

    private function forgetFile(string $pathname): self
    {
        unset($this->pathnames[$pathname]);
        return $this;
    }
}
