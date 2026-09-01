#!/bin/sh
set -eu

# Render containers use an ephemeral filesystem. Recreate Laravel's writable
# runtime directories on every boot before commands can read or write cache.
mkdir -p \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache
chown -R www-data:www-data \
    /var/www/html/storage/framework \
    /var/www/html/bootstrap/cache
chmod -R ug+rwX \
    /var/www/html/storage/framework \
    /var/www/html/bootstrap/cache

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan storage:link || true

# Render Shell is a paid feature, so publish a safe AI runtime health check in
# the normal deploy logs. This is diagnostic only and must not block Apache.
AI_RUNTIME_BINARY="${AI_PYTHON_BINARY:-python3}"
if ! command -v "$AI_RUNTIME_BINARY" >/dev/null 2>&1; then
    if command -v python3 >/dev/null 2>&1; then
        AI_RUNTIME_BINARY="python3"
    elif command -v python >/dev/null 2>&1; then
        AI_RUNTIME_BINARY="python"
    fi
fi

echo "[ai-runtime] configured=${AI_PYTHON_BINARY:-not-set} selected=${AI_RUNTIME_BINARY}"
if command -v "$AI_RUNTIME_BINARY" >/dev/null 2>&1; then
    "$AI_RUNTIME_BINARY" --version 2>&1 | sed 's/^/[ai-runtime] /' || true
    if "$AI_RUNTIME_BINARY" -c "import joblib, pandas, sklearn" >/dev/null 2>&1; then
        echo "[ai-runtime] dependencies=ready"
    else
        echo "[ai-runtime] WARNING dependencies unavailable: joblib, pandas, or scikit-learn"
    fi
else
    echo "[ai-runtime] WARNING executable unavailable"
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    # The distributed lock prevents overlapping Render instances from racing
    # the same migration during a rolling deploy.
    php artisan migrate --force --isolated
fi

# A retrained model is written to object storage because Render's container
# filesystem is ephemeral. Restore the latest verified artifact set before the
# web server starts; the repository model remains available if no backup exists.
php artisan ai:restore-forecast-artifacts || true
php artisan config:cache

# Process only forecast-training jobs in this existing web service so Render's
# paid background-worker service is not required. The database queue keeps the
# HTTP request short and survives process restarts.
if [ "${RUN_FORECAST_QUEUE_WORKER:-true}" = "true" ]; then
    (
        while true; do
            php artisan queue:work "${AI_TRAINING_QUEUE_CONNECTION:-database}" \
                --queue="${AI_TRAINING_QUEUE:-forecast-training}" \
                --sleep=2 \
                --tries=1 \
                --timeout="${AI_QUEUE_WORKER_TIMEOUT:-300}" \
                --max-jobs=100 || true
            sleep 2
        done
    ) &
fi

# Deliver AAR in-app and email notifications outside the review request. Failed
# jobs remain visible in Laravel's failed_jobs table and permanent failures are
# also written to the application log by the notification class.
if [ "${RUN_NOTIFICATION_QUEUE_WORKER:-true}" = "true" ]; then
    (
        while true; do
            php artisan queue:work "${AAR_NOTIFICATION_QUEUE_CONNECTION:-database}" \
                --queue="notifications,emails" \
                --sleep=2 \
                --tries=3 \
                --timeout=90 \
                --max-jobs=200 || true
            sleep 2
        done
    ) &
fi

# Render free web services do not run Laravel's scheduler, and a separate
# Render Cron service has a monthly charge. Keep this weather-only worker
# isolated from every other scheduled command. It catches up yesterday on
# container start, then runs daily at 00:30 Asia/Manila while the web service
# remains awake. The database write is idempotent for safe restarts/retries.
if [ "${RUN_DAILY_WEATHER_WORKER:-true}" = "true" ]; then
    (
        php artisan weather:collect-daily --catch-up=7 || true
        while true; do
            WEATHER_NOW_EPOCH="$(date +%s)"
            WEATHER_NEXT_EPOCH="$(TZ=Asia/Manila date -d 'tomorrow 00:30' +%s)"
            WEATHER_DELAY="$((WEATHER_NEXT_EPOCH - WEATHER_NOW_EPOCH))"
            if [ "$WEATHER_DELAY" -lt 60 ]; then
                WEATHER_DELAY=60
            fi
            sleep "$WEATHER_DELAY"
            php artisan weather:collect-daily || true
        done
    ) &
fi

apache2-foreground
