<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;

/**
 * @extends RuleTestCase<TodoByVersionRule>
 */
final class TodoByVersionRuleSingleTimeFetchterTest extends RuleTestCase
{
    private string $referenceVersion;
    protected function getRule(): Rule
    {
        return new TodoByVersionRule(
            false,
            new ReferenceVersionFinder($this->referenceVersion, new SingleTimeTagFetcher()),
            new ExpiredCommentErrorBuilder(true)
        );
    }

    public function testMultiGitRepoCache(): void
    {
        $tip = "Calculated reference version is '1.0.0.0'.\n\n   See also:\n https://github.com/staabm/phpstan-todo-by#reference-version";
        $this->referenceVersion = "1.0";

        // scaning several files in the same folder should trigger only a single tag fetch
        $this->analyse([__DIR__ . '/data/version.php'], [
            [
                'Version requirement >=1.0 satisfied.',
                12,
                $tip
            ]
        ]);
        $this->analyse([__DIR__ . '/data/version.php'], [
            [
                'Version requirement >=1.0 satisfied.',
                12,
                $tip
            ]

        ]);
        $this->analyse([__DIR__ . '/data/version.php'], [
            [
                'Version requirement >=1.0 satisfied.',
                12,
                $tip
            ]
        ]);
    }

}
