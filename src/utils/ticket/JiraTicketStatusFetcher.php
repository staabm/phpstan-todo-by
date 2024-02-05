<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\CredentialsHelper;
use staabm\PHPStanTodoBy\utils\HttpClient;

use function array_key_exists;
use function is_array;
use function is_string;

final class JiraTicketStatusFetcher implements TicketStatusFetcher
{
    private const API_VERSION = 2;

    private string $host;
    private ?string $authorizationHeader;

    private HttpClient $httpClient;

    public function __construct(string $host, ?string $credentials, ?string $credentialsFilePath, HttpClient $httpClient)
    {
        $credentials = CredentialsHelper::getCredentials($credentials, $credentialsFilePath);

        $this->host = $host;
        $this->authorizationHeader = $credentials ? self::createAuthorizationHeader($credentials) : null;

        $this->httpClient = $httpClient;
    }

    public function fetchTicketStatus(array $ticketKeys): array
    {
        $ticketUrls = [];

        $apiVersion = self::API_VERSION;
        foreach ($ticketKeys as $ticketKey) {
            $ticketUrls[$ticketKey] = "{$this->host}/rest/api/$apiVersion/issue/$ticketKey?expand=status";
        }

        $headers = [];
        if (null !== $this->authorizationHeader) {
            $headers = [
                "Authorization: $this->authorizationHeader",
            ];
        }

        $responses = $this->httpClient->getMulti($ticketUrls, $headers);

        $results = [];
        $urlsToKeys = array_flip($ticketUrls);
        foreach ($responses as $url => [$responseCode, $response]) {
            if (404 === $responseCode) {
                $results[$url] = null;
                continue;
            }

            if (200 !== $responseCode) {
                throw new RuntimeException("Could not fetch ticket's status from Jira with url $url");
            }

            $data = self::decodeAndValidateResponse($response);

            $ticketKey = $urlsToKeys[$url];
            $results[$ticketKey] = $data['fields']['status']['name'];
        }

        return $results;
    }

    public static function getKeyPattern(): string
    {
        return '[A-Z0-9]+-\d+';
    }

    public function resolveTicketUrl(string $ticketKey): string
    {
        return "$this->host/browse/$ticketKey";
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
