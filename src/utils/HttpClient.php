<?php

namespace staabm\PHPStanTodoBy\utils;

use CurlShareHandle;
use CurlSharePersistentHandle;
use RuntimeException;

use function function_exists;
use function is_string;

final class HttpClient
{
    /**
     * @var CurlShareHandle|CurlSharePersistentHandle|resource|null
     */
    private $shareHandle;

    /**
     * @param non-empty-array<non-empty-string> $urls
     * @param list<string> $headers
     *
     * @return non-empty-array<non-empty-string, array{int, string}>
     */
    public function getMulti(array $urls, array $headers): array
    {
        if (null === $this->shareHandle) {
            if (function_exists('curl_share_init_persistent')) {
                $this->shareHandle = curl_share_init_persistent([
                    CURL_LOCK_DATA_DNS,
                    CURL_LOCK_DATA_CONNECT,
                    CURL_LOCK_DATA_SSL_SESSION,
                ]);
            } else {
                $this->shareHandle = curl_share_init();
                curl_share_setopt($this->shareHandle, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
                curl_share_setopt($this->shareHandle, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);
                curl_share_setopt($this->shareHandle, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);
            }
        }

        $mh = curl_multi_init();

        $handles = [];

        foreach ($urls as $url) {
            $curl = curl_init($url);
            if (!$curl) {
                throw new RuntimeException('Could not initialize cURL connection');
            }

            curl_setopt($curl, CURLOPT_SHARE, $this->shareHandle);

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
            /** @phpstan-ignore function.deprecated */
            curl_close($handle);
        }
        curl_multi_close($mh);

        return $result;
    }
}
