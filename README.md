# phpstan-todo-by: comments with expiration date/version

PHPStan extension to check for TODO comments with expiration date/version.
Inspired by [parker-codes/todo-by](https://github.com/parker-codes/todo_by).

## Example:

```php
<?php

// TODO: 2023-12-14 This comment turns into a PHPStan error as of 14th december 2023
function doFoo() {

}

// TODO: <1.0.0 This has to be in the first major release
function doBar() {

}

```

## Supported todo formats

A todo comment can also consist of just a date without any text, like `// @todo 2023-12-14`.
When a text is given after the date, this text will be picked up for the PHPStan error message.

- the `todo`, `TODO`, `tOdO` keyword is case-insensitive
- the `todo` keyword can be suffixed or prefixed by a `@` character
- a username might be included after the `todo@`
- the comment might be mixed with `:` or `-` characters
- dateformat is `YYYY-MM-DD`
- multi line `/* */` and `/** */` comments are supported
- support for semantic version expiration constraints

see examples of different comment variants which are supported:

```php
// todo 2023-12-14
// @todo: 2023-12-14 fix it
// @todo 2023-12-14: fix it
// todo - 2023-12-14 fix it
// todo 2023-12-14 - fix it

// TODO@lars 2023-12-14 - fix it
// TODO@lars: 2023-12-14 - fix it

/*
 * other text
 *
 * @todo 2023-12-14 classic multi line comment
 *   more comment data
 */

// TODO: <1.0.0 This has to be in the first major release
// TODO >123.4: Must fix this or bump the version
```

## Configuration

### Non-ignorable errors

Errors emitted by the extension are non-ignorable by default, so they cannot accidentally be put into the baseline.
You can change this behaviour with a configuration option within your `phpstan.neon`:

```neon
parameters:
    todo_by:
        nonIgnorable: false # default is true
```

### Reference time

By default date-todo-comments are checked against todays date.

You might be interested, which comments will expire e.g. within the next 7 days, which can be configured with the `referenceTime` option.
You need to configure a date parsable by `strtotime`.

```neon
parameters:
    todo_by:
        # any strtotime() compatible string
        referenceTime: "now+7days"
```

It can be especially handy to use a env variable for it, so you can pass the reference date e.g. via the CLI:

```neon
parameters:
    todo_by:
        referenceTime: %env.TODOBY_REF_TIME%
```

`TODOBY_REF_TIME="now+7days" vendor/bin/phpstan analyze`

### Reference version

By default version-todo-comments are checked against `"nextMajor"` version.
This is determined by fetching the latest local available git tag and incrementing the major version number.

This behaviour can be configured with the `referenceVersion` option.
Possible values are `"nextMajor"`, `"nextMinor"`, `"nextPatch"` - which will be computed based on the latest local git tag - or any other version string like `"1.2.3"`.

```neon
parameters:
    todo_by:
        # "nextMajor", "nextMinor", "nextPatch" or a version string like "1.2.3"
        referenceVersion: "nextMinor"
```

As shown in the "Reference time"-paragraph above, you might even use a env variable instead.

Make sure tags are available within your git clone, e.g. by running `git fetch --tags origin`.

In a GitHub Action this can be done like this:

```yaml
    -   name: Checkout
        uses: actions/checkout@v4

    -   name: Get tags
        run: git fetch --tags origin
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
