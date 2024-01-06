<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByPackageVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

use function dirname;

/**
 * @extends RuleTestCase<TodoByPackageVersionRule>
 * @internal
 */
final class TodoByPackageVersionRuleTest extends RuleTestCase
{
    /**
     * @var array<string, string>
     */
    private array $virtualPackages = [];

    protected function getRule(): Rule
    {
        return new TodoByPackageVersionRule(
            new ExpiredCommentErrorBuilder(true),
            dirname(__DIR__),
            $this->virtualPackages,
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
     * @return iterable<array{list<array{0: string, 1: int, 2?: string|null}>}>
     */
    public static function provideErrors(): iterable
    {
        yield [
            [
                [
                    '"phpunit/phpunit" version requirement "<50" satisfied: This has to be fixed before updating to phpunit 50.x.',
                    5,
                ],
                [
                    '"phpunit/phpunit" version requirement ">=5.3" satisfied: This has to be fixed when updating to phpunit 5.3.* or higher.',
                    8,
                ],
                [
                    'Unknown package "not-installed/package". It is neither installed via composer.json nor declared as virtual package via PHPStan config.',
                    11,
                ],
                [
                    '"phpunit/phpunit" version requirement "<10" satisfied.',
                    14,
                ],
                [
                    '"phpunit/phpunit" version requirement "<11" satisfied.',
                    15,
                ],
                [
                    'Invalid version constraint "<inValid.12" for package "phpunit/phpunit".',
                    17,
                ],
                [
                    '"php" version requirement ">7.3" satisfied: drop this code after min-version raise.',
                    19,
                ],
                [
                    '"php" version requirement ">=7" satisfied: drop this code after min-version raise.',
                    20,
                ],
                [
                    '"php" version requirement ">=7" satisfied.',
                    22,
                ],
            ],
        ];
    }

    public function testBug44(): void
    {
        $this->analyse([__DIR__ . '/data/bug44.php'], []);
    }

    public function testBug64(): void
    {
        $this->analyse([__DIR__ . '/data/bug64.php'], [
            [
                '"php" version requirement ">=7" satisfied: drop this code after min-version raise.',
                7,
            ],
        ]);
    }

    public function testVirtualPackage(): void
    {
        $this->virtualPackages = [
            'my-virtual/package' => '1.0.0',
        ];
        $this->analyse([__DIR__ . '/data/virtualPackages.php'], [
            [
                '"my-virtual/package" version requirement ">=1.0" satisfied: comment v1.',
                5,
            ],
            [
                'Unknown package "some/unknown". It is neither installed via composer.json nor declared as virtual package via PHPStan config.',
                8,
            ],
        ]);
    }

    public function testInvalidVirtualPackage(): void
    {
        $this->virtualPackages = [
            'my-virtual/package' => 'not-a-version',
        ];
        $this->analyse([__DIR__ . '/data/virtualPackages.php'], [
            [
                'Invalid virtual-package "my-virtual/package": "not-a-version" provided via PHPStan config file.',
                5,
            ],
            [
                'Invalid virtual-package "my-virtual/package": "not-a-version" provided via PHPStan config file.',
                6,
            ],
            [
                'Unknown package "some/unknown". It is neither installed via composer.json nor declared as virtual package via PHPStan config.',
                8,
            ],
        ]);
    }
}
