<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\utils\VersionNormalizer;

/**
 * @extends RuleTestCase<TodoByVersionRule>
 */
final class TodoByVersionRuleAlwaysThrowingFetchterTest extends RuleTestCase
{
    private string $referenceVersion;
    protected function getRule(): Rule
    {
        return new TodoByVersionRule(
            true,
            new ReferenceVersionFinder($this->referenceVersion, new AlwaysThrowingTagFetcher()),
            new VersionNormalizer(),
            new ExpiredCommentErrorBuilder(true)
        );
    }

    public function testRule(): void
    {
        $this->referenceVersion = "nextMajor";

        $this->analyse([__DIR__ . '/data/regularComments.php'], []);
    }

}
