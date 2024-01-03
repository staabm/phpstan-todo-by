<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByDateRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

/**
 * @extends RuleTestCase<TodoByDateRule>
 * @internal
 */
final class TodoByDateRuleTest extends RuleTestCase
{
    private string $referenceTime;

    protected function getRule(): Rule
    {
        return new TodoByDateRule(
            $this->referenceTime,
            new ExpiredCommentErrorBuilder(true)
        );
    }

    public function testRule(): void
    {
        $this->referenceTime = 'now';

        $this->analyse([__DIR__ . '/data/example.php'], [
            [
                'Expired on 2023-12-14: Expired comment1.',
                9,
            ],
            [
                'Expired on 2023-12-14: Expired comment2.',
                10,
            ],
            [
                'Expired on 2023-12-14: Expired comment3.',
                11,
            ],
            [
                'Expired on 2023-12-14: Expired comment4.',
                12,
            ],
            [
                'Expired on 2023-12-14: Expired comment5.',
                13,
            ],
            [
                'Expired on 2023-12-14: Expired commentX.',
                19,
            ],
            [
                'Comment expired on 2023-12-14.',
                21,
            ],
            [
                'Expired on 2023-12-14: class comment.',
                29,
            ],
            [
                'Expired on 2023-12-13: class comment.',
                30,
            ],
            [
                'Expired on 2023-12-14: method comment.',
                34,
            ],
            [
                'Expired on 2023-12-14: in method comment1.',
                36,
            ],
            [
                'Expired on 2023-12-14: in method comment2.',
                38,
            ],
            [
                'Expired on 2023-12-14: Convert to standard Drupal $content code.',
                43,
            ],
            [
                'Expired on 2023-12-14: Decide to fix all the broken instances of class as a string.',
                46,
            ],
            [
                'Expired on 2023-12-14: fix it.',
                48,
            ],
            [
                'Expired on 2023-12-14: fix it.',
                49,
            ],
            [
                'Expired on 2023-12-14: fix it.',
                50,
            ],
            [
                'Expired on 2023-12-14: fix it.',
                51,
            ],
            [
                'Expired on 2023-12-14: fix it.',
                53,
            ],
            [
                'Expired on 2023-12-14: fix it.',
                54,
            ],
            [
                'Expired on 2023-12-14: classic multi line comment.',
                59,
            ],
            [
                'Expired on 2024-1-2: support single digit month, day.',
                63,
            ],
            [
                'Expired on 2024-1-3: support single digit month, day.',
                64,
            ],
            [
                'Expired on 2023-11-2: support single digit day.',
                65,
            ],
            [
                'Expired on 2023-1-22: support single digit month.',
                66,
            ],
        ]);
    }

    public function testReferenceTime(): void
    {
        $this->referenceTime = '1st january 2023';

        $this->analyse([__DIR__ . '/data/referenceTime.php'], []);
    }

    public function testReferenceTime2(): void
    {
        $this->referenceTime = '18th january 2023';

        $this->analyse([__DIR__ . '/data/referenceTime.php'], [
            [
                'Expired on 2023-01-14: fix it.',
                5,
            ],
        ]);
    }

    public function testInvalidDate(): void
    {
        $this->referenceTime = '18th january 2023';

        $this->analyse([__DIR__ . '/data/invalidDate.php'], [
            [
                'Invalid date "2099-13-01". Expected format "YYYY-MM-DD".',
                6,
            ],
            [
                'Invalid date "2099-11-31". Expected format "YYYY-MM-DD".',
                7,
            ],
            [
                'Invalid date "2096-02-30". Expected format "YYYY-MM-DD".',
                12,
            ],
            [
                'Invalid date "2095-02-29". Expected format "YYYY-MM-DD".',
                16,
            ],
        ]);
    }
}
