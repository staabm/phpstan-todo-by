<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoBySymfonyDeprecationRule;

use function dirname;

/**
 * @extends RuleTestCase<TodoBySymfonyDeprecationRule>
 * @internal
 */
final class TodoBySymfonyDeprecationTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TodoBySymfonyDeprecationRule(
            dirname(__DIR__),
        );
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/sf-trigger-deprecation.php'], [
            [
                'Since phpunit/phpunit 9.5: Using this is deprecated. Use that instead..',
                5,
            ],
        ]);
    }
}
