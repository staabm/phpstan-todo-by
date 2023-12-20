<?php

namespace staabm\PHPStanTodoBy\utils\jira;

use RuntimeException;

final class JiraAuthorization
{
    public static function getCredentials(?string $credentials, ?string $credentialsFilePath): string
    {
        if (null !== $credentials) {
            return trim($credentials);
        }

        if (null === $credentialsFilePath) {
            throw new RuntimeException('Either credentials or credentialsFilePath parameter must be configured');
        }

        $credentials = file_get_contents($credentialsFilePath);

        if (false === $credentials) {
            throw new RuntimeException("Cannot read $credentialsFilePath file");
        }

        return trim($credentials);
    }

    public static function createAuthorizationHeader(string $credentials): string
    {
        if (str_contains($credentials, ':')) {
            return 'Basic ' . base64_encode($credentials);
        }

        return "Bearer $credentials";
    }
}
