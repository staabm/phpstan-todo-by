<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\HttpClient;
use function array_key_exists;
use function is_array;
use function is_null;
use function is_string;

final class YouTrackTicketStatusFetcher implements TicketStatusFetcher
{
    private string $host;
    private string $authorizationHeader;

    private HttpClient $httpClient;

    /**
     * @var array<string, ?string>
     */
    private array $cache;

    public function __construct(string $host, ?string $credentials, ?string $credentialsFilePath, HttpClient $httpClient)
    {
        $credentials = self::getCredentials($credentials, $credentialsFilePath);

        $this->host = $host;
        $this->authorizationHeader = self::createAuthorizationHeader($credentials);

        $this->cache = [];
        $this->httpClient = $httpClient;
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
        if (array_key_exists($ticketKey, $this->cache)) {
            return $this->cache[$ticketKey];
        }

        $url = "{$this->host}/api/issues/$ticketKey?fields=resolved";
        $headers = [
            "Authorization: $this->authorizationHeader",
        ];

        [$responseCode, $response] = $this->httpClient->get($url, $headers);

        if (200 !== $responseCode) {
            throw new RuntimeException("Could not fetch ticket's status from Jira with url $url");
        }

        $data = self::decodeAndValidateResponse($response);

        return $this->cache[$ticketKey] = is_null($data['resolved']) ? 'Open' : 'Resolved';
    }

    public static function getKeyPattern(): string
    {
        return '[A-Z0-9]+-\d+';
    }

    private static function getCredentials(?string $credentials, ?string $credentialsFilePath): string
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

    private static function createAuthorizationHeader(string $credentials): string
    {
        return "Bearer $credentials";
    }

    /** @return array{resolved: ?int} */
    private static function decodeAndValidateResponse(string $body): array
    {
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || !array_key_exists('resolved', $data)) {
            self::throwInvalidResponse();
        }

        return $data;
    }

    /** @return never */
    private static function throwInvalidResponse(): void
    {
        throw new RuntimeException('YouTrack returned invalid response body');
    }
}
