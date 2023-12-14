# phpstan-todo-by: comments with expiration date

PHPStan extension to check for TODO comments with expiration date.
Inspired by @parker_codes's [parker-codes/todo-by](https://github.com/parker-codes/todo_by).

## Example:

```php
<?php

// TODO: 2023-12-14 This comment turns into a PHPStan error after 14th December 2023
function doFoo() {

}

```

## Supported todo formats

Every comment which matches the [supported pattern](https://github.com/staabm/phpstan-todo-by/blob/main/src/TodoByRule.php#L14) will be checked.
See [all supported examples](https://github.com/staabm/phpstan-todo-by/blob/main/tests/data/example.php) in the Testsuite.
