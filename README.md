# Temper

Temper offers a smoother approach to working with temp-files in PHP.

## Usage

There are two ways to use it:

- 'Consume' a temp-file: create, use, and remove a temp-file in a single operation
- Create and remove temp-files in separate steps

### Consume a Temp-File

```php
$temper = new Temper('/path/to/tmp/dir');

$temper->consumeFile(function (SplFileInfo $tempFileinfo): void {
    // Do something with temp-file
});

// Temp-file gone

$temper->consumeFile(function (SplFileInfo $tempFileinfo): void {
    // Do something with `.jpg` temp-file
}, 'jpg');

// Temp-file gone
```

### Create and Remove In Separate Steps

> [!NOTE]
> Since, at the end of its life, a Temper instance will automatically clean-up any remaining temp-files it knows about, you may never *need* to call `cleanUp()` by hand

```php
$temper = new Temper('/path/to/tmp/dir');

$tempFileWithoutExtension = $temper->createFile();

$tempImageFile = $temper->createFile('jpg');

// Removes *all* remaining temp-files created by the Temper instance
$temper->cleanUp();
```

## Installation

Install using [Composer](https://getcomposer.org/):

```sh
composer require danbettles/temper
```
