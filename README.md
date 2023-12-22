# phpstan-todo-by: comments with expiration

PHPStan extension to check for TODO comments with expiration.
Inspired by [parker-codes/todo-by](https://github.com/parker-codes/todo_by).


## Examples

The main idea is, that comments within the source code will be turned into PHPStan errors when a condition is satisfied, e.g. a date reached, a version met.

```php
<?php

// TODO: 2023-12-14 This comment turns into a PHPStan error as of 14th december 2023
function doFoo() {

}

// TODO: <1.0.0 This has to be in the first major release of this repo
function doBar() {

}

// TODO: phpunit/phpunit:5.3 This has to be fixed when updating phpunit to 5.3.x or higher
function doFooBar() {

}

// TODO: php:8 drop this polyfill when php 8.x is required

```


## Supported todo formats

A todo comment can also consist of just a constraint without any text, like `// @todo 2023-12-14`.
When a text is given after the date, this text will be picked up for the PHPStan error message.

- the `todo`, `TODO`, `tOdO` keyword is case-insensitive
- the `todo` keyword can be suffixed or prefixed by a `@` character
- a username might be included after the `todo@`
- the comment might be mixed with `:` or `-` characters
- multi line `/* */` and `/** */` comments are supported

The comment can expire by different constraints, examples are:
- by date with format of `YYYY-MM-DD` matched against the [reference-time](https://github.com/staabm/phpstan-todo-by#reference-time)
- by a semantic version constraint matched against the projects [reference-version](https://github.com/staabm/phpstan-todo-by#reference-version)
- by a semantic version constraint matched against a Composer dependency (via `composer.lock`)

see examples of different comment variants which are supported:

```php
// todo 2023-12-14
// @todo: 2023-12-14 fix it
// @todo 2023-12-14: fix it
// todo - 2023-12-14 fix it
// todo 2023-12-14 - fix it

// TODO@staabm 2023-12-14 - fix it
// TODO@markus: 2023-12-14 - fix it

/*
 * other text
 *
 * @todo 2023-12-14 classic multi line comment
 *   more comment data
 */

// TODO: <1.0.0 This has to be in the first major release
// TODO >123.4: Must fix this or bump the version

// TODO: phpunit/phpunit:<5 This has to be fixed before updating to phpunit 5.x
// TODO@markus: phpunit/phpunit:5.3 This has to be fixed when updating phpunit to 5.3.x or higher
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
It is determined by fetching the latest local available git tag and incrementing the major version number.

The behaviour can be configured with the `referenceVersion` option.
Possible values are `"nextMajor"`, `"nextMinor"`, `"nextPatch"` - which will be computed based on the latest local git tag - or any other version string like `"1.2.3"`.

```neon
parameters:
    todo_by:
        # "nextMajor", "nextMinor", "nextPatch" or a version string like "1.2.3"
        referenceVersion: "nextMinor"
```

As shown in the "Reference time"-paragraph above, you might even use a env variable instead.

> [!NOTE]
> The reference version is not applied to package-version-todo-comments which are matched against `composer.lock` instead.

#### Prerequisite

Make sure tags are available within your git clone, e.g. by running `git fetch --tags origin` - otherwise you are likely running into a 'Could not determine latest git tag' error.

In a GitHub Action this can be done like this:

```yaml
    -   name: Checkout
        uses: actions/checkout@v4

    -   name: Get tags
        run: git fetch --tags origin
```


#### Multiple GIT repository support

By default the latest git tag to calculate the reference version is fetched once for all files beeing analyzed.

This behaviour can be configured with the `singleGitRepo` option.

In case you are using git submodules, or the analyzed codebase consists of multiple git repositories,
set the `singleGitRepo` option to `false` which resolves the reference version for each directory beeing analyzed.



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

## FAQ

### Unexpected '"php" version requirement ">=XXX" satisfied' error

If you get this errors too early, it might be caused by wrong version constraints in your `composer.json` file.
A `php` version constraint of e.g. `^7.4` means `>=7.4.0 && <= 7.999999.99999`,
which means comments like `// TODO >7.5` will emit an error.

For the `php` declaration, it is recommended to use a version constraint with a fixed upper bound, e.g. `7.4.*` or `^7 || <8.3`.

### 'Could not determine latest git tag' error

This error is thrown, when no git tags are available within your git clone.
Fetch git tags, as described in the ["Reference version"-chapter](https://github.com/staabm/phpstan-todo-by#reference-version) above.

## 💌 Give back some love

[Consider supporting the project](https://github.com/sponsors/staabm), so we can make this tool even better even faster for everyone.
