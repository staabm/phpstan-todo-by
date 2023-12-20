<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByPackageVersionRule;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\GitTagFetcher;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\utils\VersionNormalizer;

/**
 * @extends RuleTestCase<TodoByPackageVersionRule>
 */
final class TodoByPackageVersionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TodoByPackageVersionRule(
            new VersionNormalizer(),
            new ExpiredCommentErrorBuilder(true),
        );
    }

    /**
     * @param list<array{0: string, 1: int, 2?: string|null}> $errors
     * @dataProvider provideErrors
     */
    public function testRule(array $errors): void
    {
        $this->analyse([__DIR__ . '/data/packageVersion.php'], $errors);
    }

    /**
     * @return iterable<list<array{0: string, 1: int, 2?: string|null}>>
     */
    public static function provideErrors(): iterable
    {
        yield [
            [
                [
                    'phpunit/phpunit version requirement <5 not satisfied: This has to be fixed when updating to phpunit 5.x.',
                    7
                ]
            ]
        ];
    }

}
