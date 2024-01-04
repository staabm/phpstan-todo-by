<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\CredentialsHelper;

use function array_key_exists;
use function is_array;
use function is_string;

final class JiraTicketStatusFetcher implements TicketStatusFetcher
{
    private const API_VERSION = 2;

    private string $host;
    private string $authorizationHeader;

    /**
     * @var array<string, ?string>
     */
    private array $cache;

    public function __construct(string $host, ?string $credentials, ?string $credentialsFilePath)
    {
        $credentials = CredentialsHelper::getCredentials($credentials, $credentialsFilePath);
        if (!$credentials) {
            throw new RuntimeException('Either credentials or credentialsFilePath parameter must be configured');
        }

        $this->host = $host;
        $this->authorizationHeader = self::createAuthorizationHeader($credentials);

        $this->cache = [];
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
        if (array_key_exists($ticketKey, $this->cache)) {
            return $this->cache[$ticketKey];
        }

        $apiVersion = self::API_VERSION;

        $curl = curl_init("{$this->host}/rest/api/$apiVersion/issue/$ticketKey?expand=status");
        if (!$curl) {
            throw new RuntimeException('Could not initialize cURL connection');
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: $this->authorizationHeader",
        ]);

        $response = curl_exec($curl);

        if (404 === $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE)) {
            return null;
        }

        if (!is_string($response) || 200 !== $responseCode) {
            throw new RuntimeException("Could not fetch ticket's status from Jira");
        }

        curl_close($curl);

        $data = self::decodeAndValidateResponse($response);

        return $this->cache[$ticketKey] = $data['fields']['status']['name'];
    }

    public static function getKeyPattern(): string
    {
        return '[A-Z0-9]+-\d+';
    }

    /** @return array{fields: array{status: array{name: string}}} */
    private static function decodeAndValidateResponse(string $body): array
    {
        $data = json_decode($body, true);

        if (!is_array($data) || !array_key_exists('fields', $data)) {
            self::throwInvalidResponse();
        }

        $fields = $data['fields'];

        if (!is_array($fields) || !array_key_exists('status', $fields)) {
            self::throwInvalidResponse();
        }

        $status = $fields['status'];
        if (!is_array($status) || !array_key_exists('name', $status)) {
            self::throwInvalidResponse();
        }

        $name = $status['name'];

        if (!is_string($name) || '' === trim($name)) {
            self::throwInvalidResponse();
        }

        return $data;
    }

    private static function createAuthorizationHeader(string $credentials): string
    {
        if (str_contains($credentials, ':')) {
            return 'Basic ' . base64_encode($credentials);
        }

        return "Bearer $credentials";
    }

    /** @return never */
    private static function throwInvalidResponse(): void
    {
        throw new RuntimeException('Jira returned invalid response body');
    }
}
