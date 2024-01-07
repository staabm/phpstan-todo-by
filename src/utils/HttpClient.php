<?php

namespace staabm\PHPStanTodoBy\utils;

use RuntimeException;

use function is_string;

final class HttpClient
{
    /**
     * @param list<string> $headers
     * @return array{int, string}
     */
    public function get(string $url, array $headers): array
    {
        $curl = curl_init($url);
        if (!$curl) {
            throw new RuntimeException('Could not initialize cURL connection');
        }

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ([] !== $headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($curl);

        if (!is_string($response)) {
            throw new RuntimeException("Could not fetch url $url");
        }

        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        return [$responseCode, $response];
    }
}
