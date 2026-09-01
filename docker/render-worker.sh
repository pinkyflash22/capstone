#!/bin/sh
set -eu

WORKER_NAME="${WORKER_NAME:-general}"
QUEUE_CONNECTION_NAME="${WORKER_QUEUE_CONNECTION:-database}"
QUEUE_NAMES="${WORKER_QUEUES:-default,notifications,emails}"
WORKER_TIMEOUT_SECONDS="${WORKER_TIMEOUT:-120}"
WORKER_TRIES_COUNT="${WORKER_TRIES:-3}"
WORKER_MAX_JOBS_COUNT="${WORKER_MAX_JOBS:-250}"
WORKER_MAX_TIME_SECONDS="${WORKER_MAX_TIME:-300}"

php artisan config:cache

while true; do
    php artisan ops:worker-heartbeat "$WORKER_NAME" || true
    php artisan queue:work "$QUEUE_CONNECTION_NAME" \
        --queue="$QUEUE_NAMES" \
        --sleep=2 \
        --backoff=5 \
        --tries="$WORKER_TRIES_COUNT" \
        --timeout="$WORKER_TIMEOUT_SECONDS" \
        --max-jobs="$WORKER_MAX_JOBS_COUNT" \
        --max-time="$WORKER_MAX_TIME_SECONDS" \
        --stop-when-empty || true
    sleep 2
done
