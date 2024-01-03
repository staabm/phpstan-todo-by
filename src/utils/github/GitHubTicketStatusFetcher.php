<?php

namespace staabm\PHPStanTodoBy\utils\github;

use RuntimeException;
use staabm\PHPStanTodoBy\utils\CredentialsHelper;
use staabm\PHPStanTodoBy\utils\TicketStatusFetcher;

use function array_key_exists;
use function is_array;
use function is_string;

final class GitHubTicketStatusFetcher implements TicketStatusFetcher
{
    private const API_VERSION = '2022-11-28';

    private string $owner;
    private string $repo;
    private ?string $accessToken;

    /**
     * @var array<string, ?string>
     */
    private array $cache;

    public function __construct(string $owner, string $repo, ?string $credentials, ?string $credentialsFilePath)
    {
        $this->owner = $owner;
        $this->repo = $repo;
        $this->accessToken = CredentialsHelper::getCredentials($credentials, $credentialsFilePath);

        $this->cache = [];
    }

    public function fetchTicketStatus(string $ticketKey): ?string
    {
        // trim "#"
        $ticketKey = substr($ticketKey, 1);

        if (array_key_exists($ticketKey, $this->cache)) {
            return $this->cache[$ticketKey];
        }

        $apiVersion = self::API_VERSION;

        $curl = curl_init("https://api.github.com/repos/$this->owner/$this->repo/issues/$ticketKey");
        if (!$curl) {
            throw new RuntimeException('Could not initialize cURL connection');
        }

        $headers = [
            'User-agent: phpstan-todo-by',
            'Accept: application/vnd.github+json',
            "X-GitHub-Api-Version: $apiVersion",
        ];

        if ($this->accessToken) {
            $headers[] = "Authorization: Bearer $this->accessToken";
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        if (404 === $responseCode) {
            return null;
        }

        if (!is_string($response) || 200 !== $responseCode) {
            throw new RuntimeException("Could not fetch ticket's status from GitHub");
        }

        curl_close($curl);

        $data = json_decode($response, true);

        if (!is_array($data) || !array_key_exists('state', $data) || !is_string($data['state'])) {
            throw new RuntimeException('GitHub returned invalid response body');
        }

        return $this->cache[$ticketKey] = $data['state'];
    }

    public static function getKeyPattern(): string
    {
        return '\#\d+';
    }
}
