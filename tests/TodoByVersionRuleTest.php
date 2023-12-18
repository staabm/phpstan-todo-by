<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\GitTagFetcher;
use staabm\PHPStanTodoBy\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\TodoByDateRule;
use staabm\PHPStanTodoBy\TodoByVersionRule;

/**
 * @extends RuleTestCase<TodoByVersionRule>
 */
final class TodoByVersionRuleTest extends RuleTestCase
{
    private string $referenceVersion;
    protected function getRule(): Rule
    {
        return new TodoByVersionRule(true, new ReferenceVersionFinder($this->referenceVersion, new GitTagFetcher()));
    }

    /**
     * @param list<array{0: string, 1: int, 2?: string|null}> $errors
     * @dataProvider provideErrors
     */
    public function testRule(string $referenceVersion, array $errors): void
    {
        $this->referenceVersion = $referenceVersion;

        $this->analyse([__DIR__ . '/data/version.php'], $errors);
    }

    /**
     * @return iterable<array{string, list<array{0: string, 1: int, 2?: string|null}>}>
     */
    static public function provideErrors(): iterable
    {
        yield [
            "0.1",
            [
            ]
        ];

        yield [
            "1.0",
            [
                [
                    'Version requirement <1.0.0 not satisfied: This has to be in the first major release.',
                    5,
                ],
                [
                'Version requirement <1.0.0 not satisfied.',
                    10,
                ]
            ]
        ];

        yield [
            "123.4",
            [
                [
                    'Version requirement <1.0.0 not satisfied: This has to be in the first major release.',
                    5,
                ],
                [
                    'Version requirement <1.0.0 not satisfied.',
                    10,
                ]
            ]
        ];

        yield [
            "123.5",
            [
                [
                    'Version requirement <1.0.0 not satisfied: This has to be in the first major release.',
                    5,
                ],
                [
                    'Version requirement >123.4 not satisfied: Must fix this or bump the version.',
                    7,
                ],
                [
                    'Version requirement <1.0.0 not satisfied.',
                    10,
                ]
            ]
        ];
    }

    /**
     * @param list<array{0: string, 1: int, 2?: string|null}> $errors
     * @dataProvider provideSemanticVersions
     */
    public function testSemanticVersions(string $referenceVersion, array $errors): void
    {
        $this->referenceVersion = $referenceVersion;

        $this->analyse([__DIR__ . '/data/version.php'], $errors);
    }

    static public function provideSemanticVersions(): iterable {
        yield [
            'nextMajor', // we assume this resolves to 1.0
            [
                [
                    'Version requirement <1.0.0 not satisfied: This has to be in the first major release.',
                    5,
                ],
                [
                    'Version requirement <1.0.0 not satisfied.',
                    10,
                ]
            ]
        ];
    }

}
