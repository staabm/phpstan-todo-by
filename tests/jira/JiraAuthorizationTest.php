<?php

namespace staabm\PHPStanTodoBy\Tests\jira;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use staabm\PHPStanTodoBy\utils\jira\JiraAuthorization;

/**
 * @internal
 */
final class JiraAuthorizationTest extends TestCase
{
    public function testThrowsIfNeitherCredentialsNorFilePathConfigured(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Either credentials or credentialsFilePath parameter must be configured');

        JiraAuthorization::getCredentials(null, null);
    }

    public function testCredentialsStringIsPreferredOverFilePath(): void
    {
        $credentials = JiraAuthorization::getCredentials('secret_token', __DIR__ . '/data/jira-credentials.txt');

        static::assertSame('secret_token', $credentials);
    }

    public function testReadsCredentialsFromFile(): void
    {
        $credentials = JiraAuthorization::getCredentials(null, __DIR__ . '/data/jira-credentials.txt');

        static::assertSame('john.doe@example.com:th!s#isSecret_token', $credentials);
    }

    public function testCreatesBasicAuthorizationHeader(): void
    {
        $authorization = JiraAuthorization::createAuthorizationHeader('john.doe@example.com:th!s#isSecret_token');

        static::assertSame('Basic am9obi5kb2VAZXhhbXBsZS5jb206dGghcyNpc1NlY3JldF90b2tlbg==', $authorization);
    }

    public function testCreatesBearerAuthorizationHeader(): void
    {
        $authorization = JiraAuthorization::createAuthorizationHeader('th!s#isSecret_token');

        static::assertSame('Bearer th!s#isSecret_token', $authorization);
    }
}
