<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use staabm\PHPStanTodoBy\TodoByKeywordRule;
use staabm\PHPStanTodoBy\utils\ExpiredCommentErrorBuilder;

/**
 * @extends RuleTestCase<TodoByKeywordRule>
 * @internal
 */
final class TodoByKeywordRuleTest extends RuleTestCase
{
    private array $keywords;

    protected function getRule(): Rule
    {
        return new TodoByKeywordRule(
            $this->keywords,
            new ExpiredCommentErrorBuilder(true),
        );
    }

    public function testRule(): void
    {
        $this->keywords = [
            'keyword1',
            'keyword2',
        ];

        $this->analyse([__DIR__ . '/data/keyword.php'], [
            [
                'Should be processed (keyword1): comment1.',
                9,
            ],
            [
                'Should be processed (keyword2): comment2.',
                10,
            ],
            [
                'Should be processed (kEywORd1): comment3.',
                11,
            ],
            [
                'Should be processed (keyword1): comment4.',
                12,
            ],
            [
                'Should be processed (keyword1): comment5.',
                13,
            ],
            [
                'Should be processed (keyword1): comment6.',
                14,
            ],
            [
                'Should be processed (keyword1): comment7.',
                15,
            ],
            [
                'Should be processed (keyword1): commentX.',
                20,
            ],
            [
                'Comment should be processed (keyword1).',
                22,
            ],
            [
                'Should be processed (keyword1): class comment.',
                30,
            ],
            [
                'Should be processed (keyword1): class comment.',
                31,
            ],
            [
                'Should be processed (keyword1): method comment.',
                35,
            ],
            [
                'Should be processed (keyword1): in method comment1.',
                37,
            ],
            [
                'Should be processed (keyword1): in method comment2.',
                39,
            ],
            [
                'Should be processed (keyword1): Convert to standard Drupal $content code.',
                44,
            ],
            [
                'Should be processed (keyword1): Decide to fix all the broken instances of class as a string.',
                47,
            ],
            [
                'Should be processed (keyword1): fix it.',
                49,
            ],
            [
                'Should be processed (keyword1): fix it.',
                50,
            ],
            [
                'Should be processed (keyword1): fix it.',
                51,
            ],
            [
                'Should be processed (keyword1): fix it.',
                52,
            ],
            [
                'Should be processed (keyword1): fix it.',
                54,
            ],
            [
                'Should be processed (keyword1): fix it.',
                55,
            ],
            [
                'Should be processed (keyword1): classic multi line comment.',
                60,
            ],
        ]);
    }

    public function testNoKeywordRule(): void
    {
        $this->keywords = [];

        $this->analyse([__DIR__ . '/data/keyword.php'], []);
    }
}
