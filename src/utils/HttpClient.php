<?php

namespace staabm\PHPStanTodoBy\utils;

use RuntimeException;

use function is_string;

final class HttpClient
{
    /**
     * @param non-empty-array<non-empty-string> $urls
     * @param list<string> $headers
     *
     * @return non-empty-array<non-empty-string, array{int, string}>
     */
    public function getMulti(array $urls, array $headers): array
    {
        $mh = curl_multi_init();

        $handles = [];

        foreach ($urls as $url) {
            $curl = curl_init($url);
            if (!$curl) {
                throw new RuntimeException('Could not initialize cURL connection');
            }

            // see https://stackoverflow.com/a/27776164/1597388
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);

            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            if ([] !== $headers) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }

            $handles[$url] = $curl;
        }

        foreach ($handles as $handle) {
            curl_multi_add_handle($mh, $handle);
        }

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                // Wait a short time for more activity
                curl_multi_select($mh);
            }
        } while ($active && CURLM_OK == $status);

        $result = [];
        foreach ($handles as $url => $handle) {
            $response = curl_multi_getcontent($handle);
            $errno = curl_multi_errno($mh);

            if ($errno || !is_string($response)) {
                $message = curl_multi_strerror($errno);
                if (null === $message) {
                    $message = "Could not fetch url $url";
                }
                throw new RuntimeException($message);
            }

            $responseCode = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $result[$url] = [$responseCode, $response];

            curl_multi_remove_handle($mh, $handle);
            curl_close($handle);
        }
        curl_multi_close($mh);

        return $result;
    }
}
