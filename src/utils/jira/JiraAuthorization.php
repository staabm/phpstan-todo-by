<?php

namespace staabm\PHPStanTodoBy\utils\jira;

final class JiraAuthorization
{
    public static function getCredentials(?string $credentials, ?string $credentialsFilePath): string
    {
        if ($credentials !== null) {
            return trim($credentials);
        }

        if ($credentialsFilePath === null) {
            throw new \RuntimeException("Either credentials or credentialsFilePath parameter must be configured");
        }

        $credentials = file_get_contents($credentialsFilePath);

        if ($credentials === false) {
            throw new \RuntimeException("Cannot read $credentialsFilePath file");
        }

        return trim($credentials);
    }

    public static function createAuthorizationHeader(string $credentials): string
    {
        if (strpos($credentials, ':') !== false) {
            return 'Basic ' . base64_encode($credentials);
        }

        return "Bearer $credentials";
    }
}
