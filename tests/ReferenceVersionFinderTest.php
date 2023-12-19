<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\TestCase;
use staabm\PHPStanTodoBy\ReferenceVersionFinder;
use staabm\PHPStanTodoBy\TodoByDateRule;

final class ReferenceVersionFinderTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testReferenceFinder(string $refernceVersion, string $staticTag, string $expected): void
    {
        $finder = new ReferenceVersionFinder($refernceVersion, new StaticTagFetcher($staticTag));
        $this->assertSame($expected, $finder->find());
    }

    public static function provideData(): iterable
    {
        yield [
            'nextMajor',
            '1.0.3',
            '2.0.0',
        ];
        yield [
            'nextMinor',
            '1.0.3',
            '1.1.0',
        ];
        yield [
            'nextPatch',
            '1.0.3',
            '1.0.4',
        ];

        yield [
            'nextMajor',
            '1.0',
            '2.0.0',
        ];
        yield [
            'nextMinor',
            '1.0',
            '1.1.0',
        ];
        yield [
            'nextPatch',
            '1.0',
            '1.0.1',
        ];

        yield [
            'nextMajor',
            '1.0',
            '2.0.0',
        ];
        yield [
            'nextMinor',
            '1.0',
            '1.1.0',
        ];
        yield [
            'nextPatch',
            '1.0',
            '1.0.1',
        ];
    }
}
