<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByRule;

/**
 * @extends RuleTestCase<TodoByRule>
 */
final class TodoByRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new TodoByRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/example.php'], [
            [
                "comment 'Expired comment1' expired on 2023-12-14.",
                9,
            ],
            [
                "comment 'Expired comment2' expired on 2023-12-14.",
                10,
            ],
            [
                "comment 'Expired comment3' expired on 2023-12-14.",
                11,
            ],
            [
                "comment 'Expired comment4' expired on 2023-12-14.",
                12,
            ],
            [
                "comment 'Expired comment5' expired on 2023-12-14.",
                13,
            ],
            [
                "comment 'Expired commentX' expired on 2023-12-14.",
                19,
            ],
            [
                "comment expired on 2023-12-14.",
                21,
            ],
            [
                "comment 'method comment' expired on 2023-12-14.",
                27,
            ],
            [
                "comment 'in method comment1' expired on 2023-12-14.",
                29,
            ],
            [
                "comment 'in method comment2' expired on 2023-12-14.",
                31,
            ],
        ]);
    }
}
