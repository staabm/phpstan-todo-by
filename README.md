# phpstan-todo-by: comments with expiration date

PHPStan extension to check for TODO comments with expiration date.
Inspired by @parker_codes's [parker-codes/todo-by](https://github.com/parker-codes/todo_by).

## Example:

```php
<?php

// TODO: 2023-12-14 This comment turns into a PHPStan error as of 14th december 2023
function doFoo() {

}

```

## Supported todo formats

Every comment which matches the [supported pattern](https://github.com/staabm/phpstan-todo-by/blob/main/src/TodoByRule.php#L25-L31) will be checked.

A todo comment can also consist of just a date without any text, like `// @todo 2023-12-14`.
When a text is given after the date, this text will be picked up for the PHPStan error message.

The supported dateformat is `YYYY-MM-DD`. See [all supported examples](https://github.com/staabm/phpstan-todo-by/blob/main/tests/data/example.php) in the Testsuite.


## Configuration

Errors emitted by the extension are non-ignorable by default, so they cannot accidentally be put into the baseline.
You can change this behaviour with a configuration option within your `phpstan.neon`:

```neon
parameters:
    todo_by:
        nonIgnorable: false # default is true
```

## Installation

To use this extension, require it in [Composer](https://getcomposer.org/):

```
composer require --dev staabm/phpstan-todo-by
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```
includes:
    - vendor/staabm/phpstan-todo-by/extension.neon
```

</details>

## 💌 Give back some love

[Consider supporting the project](https://github.com/sponsors/staabm), so we can make this tool even better even faster for everyone.
