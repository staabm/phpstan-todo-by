<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\VersionNormalizer;

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
            true,
            new ReferenceVersionFinder($this->referenceVersion, new AlwaysThrowingTagFetcher()),
            new VersionNormalizer()
        );
    }

    public function testRule(): void
    {
        $this->referenceVersion = "nextMajor";

        $this->analyse([__DIR__ . '/data/regularComments.php'], []);
    }

}
