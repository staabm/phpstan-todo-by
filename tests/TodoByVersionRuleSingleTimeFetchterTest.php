<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByVersionRule;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\utils\VersionNormalizer;

/**
 * @extends RuleTestCase<TodoByVersionRule>
 */
final class TodoByVersionRuleSingleTimeFetchterTest extends RuleTestCase
{
    private string $referenceVersion;
    protected function getRule(): Rule
    {
        return new TodoByVersionRule(
            true,
            false,
            new ReferenceVersionFinder($this->referenceVersion, new SingleTimeTagFetcher()),
            new VersionNormalizer()
        );
    }

    public function testMultiGitRepoCache(): void
    {
        $this->referenceVersion = "0.1";

        // scaning several files in the same folder should trigger only a single tag fetch
        $this->analyse([__DIR__ . '/data/version.php'], []);
        $this->analyse([__DIR__ . '/data/version.php'], []);
        $this->analyse([__DIR__ . '/data/version.php'], []);
    }

}
