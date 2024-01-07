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
    private const API_VERSION = '2022-11-28';
    private const KEY_REGEX = '
          ((?P<githubOwner>[\w\-\.]+)\/)? # optional owner with slash separator
          (?P<githubRepo>[\w\-\.]+)? # optional repo
          \#(?P<githubNumber>\d+) # ticket number
        ';

    private string $defaultOwner;
    private string $defaultRepo;
    private ?string $accessToken;

    /**
     * @var array<string, ?string>
     */
    private array $cache;

    private HttpClient $httpClient;

    public function __construct(string $defaultOwner, string $defaultRepo, ?string $credentials, ?string $credentialsFilePath, HttpClient $httpClient)
    {
        $this->defaultOwner = $defaultOwner;
        $this->defaultRepo = $defaultRepo;
        $this->accessToken = CredentialsHelper::getCredentials($credentials, $credentialsFilePath);

        $this->cache = [];
        $this->httpClient = $httpClient;
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
        $keyRegex = self::KEY_REGEX;
        preg_match_all("/$keyRegex/ix", $ticketKey, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        $owner = $matches[0]['githubOwner'][0] ?: $this->defaultOwner;
        $repo = $matches[0]['githubRepo'][0] ?: $this->defaultRepo;
        $number = $matches[0]['githubNumber'][0];
        $cacheKey = "$owner/$repo#$number";

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $url = "https://api.github.com/repos/$owner/$repo/issues/$number";
        $apiVersion = self::API_VERSION;

        $headers = [
            'User-agent: phpstan-todo-by',
            'Accept: application/vnd.github+json',
            "X-GitHub-Api-Version: $apiVersion",
        ];

        if ($this->accessToken) {
            $headers[] = "Authorization: Bearer $this->accessToken";
        }

        [$responseCode, $response] = $this->httpClient->get($url, $headers);

        if (404 === $responseCode) {
            return null;
        }

        if (200 !== $responseCode) {
            throw new RuntimeException("Could not fetch ticket's status from GitHub with $url");
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !array_key_exists('state', $data) || !is_string($data['state'])) {
            throw new RuntimeException("GitHub returned invalid response body with $url");
        }

        return $this->cache[$cacheKey] = $data['state'];
    }

    public static function getKeyPattern(): string
    {
        return self::KEY_REGEX;
    }
}
