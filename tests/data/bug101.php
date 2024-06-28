<?php

/**
 * TODO APP-123 please change me
 * TODO https://github.com/staabm/phpstan-todo-by/issues/47
 */
#[SomeAttribute]
class Foo {}

/** TODO 2020-01-01 do not forget about me */
#[SomeAttribute]
class Bar {}

/**
 * TODO phpunit/phpunit:5.3
 */
#[SomeAttribute]
class Baz {}

/**
 * TODO: 1.0 fix me
 */
#[SomeAttribute]
class FooBar {}

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class SomeAttribute {}
