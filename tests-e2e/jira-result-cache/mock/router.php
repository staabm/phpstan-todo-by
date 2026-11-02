<?php

// a Jira mock: serves the statuses in statuses.json and logs every request to requests.log

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!preg_match('{^/rest/api/2/issue/([A-Z0-9]+-\d+)$}', $path, $matches)) {
    http_response_code(404);
    return;
}

file_put_contents(__DIR__ . '/../requests.log', $matches[1] . "\n", FILE_APPEND | LOCK_EX);

$statuses = json_decode(file_get_contents(__DIR__ . '/../statuses.json'), true);
if (!isset($statuses[$matches[1]])) {
    http_response_code(404);
    return;
}

header('Content-Type: application/json');
echo json_encode(['fields' => ['status' => ['name' => $statuses[$matches[1]]]]]);
