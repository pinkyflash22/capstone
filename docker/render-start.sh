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

apache2-foreground
