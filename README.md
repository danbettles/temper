# Temper

Temper offers a smoother approach to working with temp files in PHP.

## Usage

There are two ways to use it:
- *Consume* a temp file: create and remove a temp-file in a single operation.
- Create and remove temp-files in separate steps.

### Consume a Temp File

```php
$temper = new Temper('/path/to/tmp/dir');

$temper->consumeFile(function (string $tempFilePathname): void {
    // Do something with temp file.
});

// Temp file gone.

$temper->consumeFile(function (string $tempFilePathname): void {
    // Do something with `.jpg` temp file.
}, 'jpg');

// Temp file gone.
```

See [tests/src/example_1.php](tests/src/example_1.php).

### Create and Remove In Separate Steps

```php
$temper = new Temper('/path/to/tmp/dir');

$tempFilePathnameWithoutExtension = $temper->createFile();

$tempImageFilePathname = $temper->createFile('jpg');

// Removes all remaining temp-files created by the Temper instance.
$temper->cleanUp();
```

See [tests/src/example_2.php](tests/src/example_2.php).

## Installation

Install using [Composer](https://getcomposer.org/):

```sh
composer require danbettles/temper
```
