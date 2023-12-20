<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPUnit\Framework\TestCase;
use staabm\PHPStanTodoBy\utils\ReferenceVersionFinder;

/**
 * @internal
 */
final class ReferenceVersionFinderTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testReferenceFinder(string $refernceVersion, string $staticTag, string $expected): void
    {
        $finder = new ReferenceVersionFinder($refernceVersion, new StaticTagFetcher($staticTag));
        static::assertSame($expected, $finder->find(null));
    }

    /**
     * @return iterable<array{string, string, string}>
     */
    public static function provideData(): iterable
    {
        yield [
            '1.2.3',
            '1.0.3',
            '1.2.3',
        ];

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
            '1',
            '2.0.0',
        ];
        yield [
            'nextMinor',
            '1',
            '1.1.0',
        ];
        yield [
            'nextPatch',
            '1',
            '1.0.1',
        ];

        yield [
            'nextMajor',
            '1.0-beta',
            '2.0.0',
        ];
        yield [
            'nextMinor',
            '1.0-beta',
            '1.1.0',
        ];
        yield [
            'nextPatch',
            '1.0-beta',
            '1.0.0',
        ];
    }
}
