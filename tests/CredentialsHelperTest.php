<?php

namespace staabm\PHPStanTodoBy\Tests;

use PHPUnit\Framework\TestCase;
use staabm\PHPStanTodoBy\utils\CredentialsHelper;

/**
 * @internal
 */
final class CredentialsHelperTest extends TestCase
{
    public function testReturnsNullIfNeitherCredentialsNorFilePathConfigured(): void
    {
        static::assertNull(CredentialsHelper::getCredentials(null, null));
    }

    public function testCredentialsStringIsPreferredOverFilePath(): void
    {
        $credentials = CredentialsHelper::getCredentials('secret_token', __DIR__ . '/data/credentials.txt');

        static::assertSame('secret_token', $credentials);
    }

    public function testReadsCredentialsFromFile(): void
    {
        $credentials = CredentialsHelper::getCredentials(null, __DIR__ . '/data/credentials.txt');

        static::assertSame('john.doe@example.com:th!s#isSecret_token', $credentials);
    }
}
