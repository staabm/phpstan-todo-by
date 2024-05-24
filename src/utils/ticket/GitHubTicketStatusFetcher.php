<?php

namespace staabm\PHPStanTodoBy\utils\ticket;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\CredentialsHelper;
use staabm\PHPStanTodoBy\utils\HttpClient;

use function array_key_exists;
use function is_array;
use function is_string;

final class GitHubTicketStatusFetcher implements TicketStatusFetcher
{
    public const RESOLVED_STATUSES = ['closed'];

    private const API_VERSION = '2022-11-28';

    private const KEY_REGEX = '
          ((?P<githubOwner>[\w\-\.]+)\/)? # optional owner with slash separator
          (?P<githubRepo>[\w\-\.]+)? # optional repo
          (\#|GH-)(?P<githubNumber>\d+) # ticket number
        ';

    private string $defaultOwner;
    private string $defaultRepo;
    private ?string $accessToken;

    private HttpClient $httpClient;

    public function __construct(string $defaultOwner, string $defaultRepo, ?string $credentials, ?string $credentialsFilePath, HttpClient $httpClient)
    {
        $this->defaultOwner = $defaultOwner;
        $this->defaultRepo = $defaultRepo;
        $this->accessToken = CredentialsHelper::getCredentials($credentials, $credentialsFilePath);

        $this->httpClient = $httpClient;
    }

    public function fetchTicketStatus(array $ticketKeys): array
    {
        $ticketUrls = [];

        foreach ($ticketKeys as $ticketKey) {
            [$owner, $repo, $number] = $this->processKey($ticketKey);

            $ticketUrls[$ticketKey] = $this->buildUrl($owner, $repo, $number);
        }

        return $this->fetchTicketStatusByUrls($ticketUrls);
    }

    /** @return non-empty-string */
    public function buildUrl(string $owner, string $repo, string $number): string
    {
        return "https://api.github.com/repos/$owner/$repo/issues/$number";
    }

    /**
     * @param non-empty-array<non-empty-string, non-empty-string> $ticketUrls
     *
     * @return non-empty-array<non-empty-string, string|null>
     */
    public function fetchTicketStatusByUrls(array $ticketUrls): array
    {
        $apiVersion = self::API_VERSION;

        $headers = [
            'User-agent: phpstan-todo-by',
            'Accept: application/vnd.github+json',
            "X-GitHub-Api-Version: $apiVersion",
        ];

        if ($this->accessToken) {
            $headers[] = "Authorization: Bearer $this->accessToken";
        }

        $responses = $this->httpClient->getMulti($ticketUrls, $headers);

        $results = [];

        $urlsToKeys = [];
        foreach ($ticketUrls as $key => $url) {
            $urlsToKeys[$url][] = $key;
        }

        foreach ($responses as $url => [$responseCode, $response]) {
            if (404 === $responseCode) {
                $results[$url] = null;
                continue;
            }

            if (200 !== $responseCode) {
                throw new RuntimeException("Could not fetch ticket's status from GitHub with $url");
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !array_key_exists('state', $data) || !is_string($data['state'])) {
                throw new RuntimeException("GitHub returned invalid response body with $url");
            }

            foreach ($urlsToKeys[$url] as $ticketKey) {
                $results[$ticketKey] = $data['state'];
            }
        }

        return $results;
    }

    public static function getKeyPattern(): string
    {
        return self::KEY_REGEX;
    }

    public function resolveTicketUrl(string $ticketKey): string
    {
        [$owner, $repo, $number] = $this->processKey($ticketKey);

        return "https://github.com/$owner/$repo/issues/$number";
    }

    /** @return array{string,string,string} */
    private function processKey(string $ticketKey): array
    {
        $keyRegex = self::KEY_REGEX;
        preg_match_all("/$keyRegex/ix", $ticketKey, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $owner = $matches[0]['githubOwner'][0] ?: $this->defaultOwner;
        $repo = $matches[0]['githubRepo'][0] ?: $this->defaultRepo;
        $number = $matches[0]['githubNumber'][0];

        return [$owner, $repo, $number];
    }
}
