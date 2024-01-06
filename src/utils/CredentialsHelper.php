<?php

namespace staabm\PHPStanTodoBy\utils;

use RuntimeException;

final class CredentialsHelper
{
    public static function getCredentials(?string $credentials, ?string $credentialsFilePath): ?string
    {
        if (null !== $credentials) {
            return trim($credentials);
        }

        if (null === $credentialsFilePath) {
            return null;
        }

        $credentials = file_get_contents($credentialsFilePath);

        if (false === $credentials) {
            throw new RuntimeException("Cannot read $credentialsFilePath file");
        }

        return trim($credentials);
    }
}
