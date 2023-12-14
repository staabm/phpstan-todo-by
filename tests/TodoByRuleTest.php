<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByRule;

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
                "'Expired comment1' expired on 2023-12-14.",
                9,
            ],
            [
                "'Expired comment2' expired on 2023-12-14.",
                10,
            ],
            [
                "'Expired comment3' expired on 2023-12-14.",
                11,
            ],
            [
                "'Expired comment4' expired on 2023-12-14.",
                12,
            ],
            [
                "'Expired comment5' expired on 2023-12-14.",
                13,
            ],
            [
                "'Expired commentX' expired on 2023-12-14.",
                19,
            ],
            [
                "comment expired on 2023-12-14.",
                21,
            ],
            [
                "'method comment' expired on 2023-12-14.",
                27,
            ],
            [
                "'in method comment' expired on 2023-12-14.",
                29,
            ],
        ]);
    }
}
