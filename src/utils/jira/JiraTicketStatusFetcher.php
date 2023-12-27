<?php

namespace staabm\PHPStanTodoBy\utils\jira;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\TicketStatusFetcher;

use function array_key_exists;
use function is_array;
use function is_string;

final class JiraTicketStatusFetcher implements TicketStatusFetcher
{
    private const API_VERSION = 2;

    private string $host;
    private string $authorizationHeader;

    public function __construct(string $host, ?string $credentials, ?string $credentialsFilePath)
    {
        $credentials = JiraAuthorization::getCredentials($credentials, $credentialsFilePath);

        $this->host = $host;
        $this->authorizationHeader = JiraAuthorization::createAuthorizationHeader($credentials);
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
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
        if (!is_string($response) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
            throw new RuntimeException("Could not fetch ticket's status from Jira");
        }

        curl_close($curl);

        $data = self::decodeAndValidateResponse($response);

        return $data['fields']['status']['name'];
    }

    /** @return array{fields: array{status: array{name: string}}} */
    private static function decodeAndValidateResponse(string $body): array
    {
        $data = json_decode($body, true);

        if (!is_array($data) || !array_key_exists('fields', $data)) {
            throw self::throwInvalidResponse();
        }

        $fields = $data['fields'];

        if (!is_array($fields) || !array_key_exists('status', $fields)) {
            throw self::throwInvalidResponse();
        }

        $status = $fields['status'];
        if (!is_array($status) || !array_key_exists('name', $status)) {
            throw self::throwInvalidResponse();
        }

        $name = $status['name'];

        if (!is_string($name) || '' === trim($name)) {
            throw self::throwInvalidResponse();
        }

        return $data;
    }

    /** @return never */
    private static function throwInvalidResponse(): void
    {
        throw new RuntimeException('Jira returned invalid response body');
    }
}
