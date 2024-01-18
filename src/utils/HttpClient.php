<?php

namespace staabm\PHPStanTodoBy\utils;

use RuntimeException;

use function is_string;

final class HttpClient
{
    /**
     * @param list<string> $urls
     * @param list<string> $headers
     * @return array<string, array{int, string}>
     */
    public function getMulti(array $urls, array $headers): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach($urls as $url) {
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

        foreach($handles as $handle) {
            curl_multi_add_handle($mh, $handle);
        }

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                // Wait a short time for more activity
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        $result = [];
        foreach($handles as $url => $handle) {
            $response = curl_multi_getcontent($handle);

            if (!is_string($response)) {
                throw new RuntimeException("Could not fetch url $url");
            }

            $responseCode = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $result[$url] = [$responseCode, $response];

            curl_multi_remove_handle($mh, $handle);
        }
        curl_multi_close($mh);

        return $result;

    }
}
