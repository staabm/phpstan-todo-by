<?php

namespace staabm\PHPStanTodoBy\utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\StreamInterface;

final class JiraTicketStatusFetcher implements TicketStatusFetcher
{
    private const API_VERSION = 2;

    private Client $client;
    private string $credentials;

    public function __construct(string $host, string $username, string $passwordOrApiKeyFilePath)
    {
        $passwordOrApiKey = self::getPasswordOrApiKey($passwordOrApiKeyFilePath);

        $this->credentials = base64_encode("$username:$passwordOrApiKey");
        $this->client = new Client([
            'base_uri' => $host,
        ]);
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
        $apiVersion = self::API_VERSION;

        try {
            $response = $this->client->get("/rest/api/$apiVersion/issue/$ticketKey", [
                'query' => [
                    'expand' => 'status',
                ],
                'headers' => [
                    'Authorization' => "Basic {$this->credentials}",
                ]
            ]);
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $exception;
        }

        $data = self::decodeAndValidateResponse($response->getBody());

        return $data['fields']['status']['name'];
    }

    /** @return array{fields: array{status: array{name: string}}} */
    private static function decodeAndValidateResponse(StreamInterface $body): array
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

        if (!is_string($name) || trim($name) === '') {
            throw self::throwInvalidResponse();
        }

        return $data;
    }

    private static function getPasswordOrApiKey(string $passwordOrApiKeyFilePath): string
    {
        $passwordOrApiKey = file_get_contents($passwordOrApiKeyFilePath);

        if ($passwordOrApiKey === false) {
            throw new \RuntimeException("Cannot read $passwordOrApiKeyFilePath file");
        }

        return trim($passwordOrApiKey);
    }

    /** @return never */
    private static function throwInvalidResponse(): void
    {
        throw new \RuntimeException("Jira returned invalid response body");
    }
}
