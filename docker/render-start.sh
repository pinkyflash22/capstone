#!/bin/sh
set -eu

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
    php artisan migrate --force
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
