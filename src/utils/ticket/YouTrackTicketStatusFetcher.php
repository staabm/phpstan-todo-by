<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\CredentialsHelper;
use staabm\PHPStanTodoBy\utils\HttpClient;

use function array_key_exists;
use function is_array;

final class YouTrackTicketStatusFetcher implements TicketStatusFetcher
{
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

        foreach ($ticketKeys as $ticketKey) {
            $ticketUrls[$ticketKey] = "{$this->host}/api/issues/$ticketKey?fields=resolved";
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
            if (200 !== $responseCode) {
                throw new RuntimeException("Could not fetch ticket's status from YouTrack with url $url");
            }

            $data = self::decodeAndValidateResponse($response);

            $ticketKey = $urlsToKeys[$url];
            $results[$ticketKey] = null === $data['resolved'] ? 'open' : 'resolved';
        }

        return $results;
    }

    public static function getKeyPattern(): string
    {
        return '[A-Z0-9]+-\d+';
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
