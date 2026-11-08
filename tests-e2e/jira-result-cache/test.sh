#!/usr/bin/env bash
# Runs PHPStan with the result cache against a Jira mock.
# Asserts the reported tickets and the number of requests the mock gets in each run.
set -euo pipefail
cd "$(dirname "$0")"

rm -rf tmp requests.log
echo '{"JRA-1": "Closed", "JRA-2": "Open"}' > statuses.json

php -S 127.0.0.1:8123 mock/router.php > /dev/null 2>&1 &
server=$!
trap 'kill $server' EXIT
for _ in $(seq 1 50); do
    curl -s -o /dev/null http://127.0.0.1:8123/ && break
    sleep 0.1
done

# $1: description, $2: tickets expected to be reported as resolved, $3: expected number of requests
analyse() {
    : > requests.log
    tickets=$(vendor/bin/phpstan analyse --error-format=raw --no-progress | grep -o 'resolved in JRA-[0-9]*' | sed 's/resolved in //' | sort | tr '\n' ' ' || true)
    tickets=${tickets% }
    requests=$(wc -l < requests.log | tr -d ' ')
    echo "$1: tickets [$tickets], requests $requests"
    if [ "$tickets" != "$2" ] || [ "$requests" != "$3" ]; then
        echo "expected: tickets [$2], requests $3"
        exit 1
    fi
}

analyse "cold run" "JRA-1" 2
analyse "warm run, nothing changed" "JRA-1" 2

echo '{"JRA-1": "Closed", "JRA-2": "Closed"}' > statuses.json
analyse "warm run, JRA-2 closed" "JRA-1 JRA-2" 2
analyse "warm run, nothing changed" "JRA-1 JRA-2" 2
