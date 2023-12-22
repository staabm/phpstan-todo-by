<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByConfigVersionRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

/**
 * @extends RuleTestCase<TodoByConfigVersionRule>
 * @internal
 */
final class TodoByConfigVersionRuleTest extends RuleTestCase
{
    /**
     * @var array<string, string>
     */
    private array $configs;

    protected function getRule(): Rule
    {
        return new TodoByConfigVersionRule(
            $this->configs,
            new ExpiredCommentErrorBuilder(true),
        );
    }

    public function testRule(): void
    {
        $this->configs = ['minimumApiClientVersion' => '1.2'];

        $this->analyse([__DIR__ . '/data/versionToggles.php'], [
            [
                'Unknown config versionToggle "unknownToggle".',
                7,
            ],
        ]);
    }

    public function testRuleSatisfied(): void
    {
        $this->configs = ['minimumApiClientVersion' => '2.0'];

        $this->analyse([__DIR__ . '/data/versionToggles.php'], [
            [
                'Version requirement >=2.0 satisfied: This can be removed once all API consumers use client version 2.0 or higher.',
                5,
            ],
            [
                'Unknown config versionToggle "unknownToggle".',
                7,
            ],
        ]);
    }
}
