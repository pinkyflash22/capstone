<?php

$configuredAiPythonBinary = (string) env('AI_PYTHON_BINARY', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
$aiPythonBinary = PHP_OS_FAMILY === 'Windows'
    && strtolower(trim($configuredAiPythonBinary)) === 'python'
    && is_file('C:/Program Files/Python314/python.exe')
        ? 'C:/Program Files/Python314/python.exe'
        : $configuredAiPythonBinary;

return [

    'aar_notifications' => [
        'queue_connection' => env('AAR_NOTIFICATION_QUEUE_CONNECTION', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'geoapify' => [
        'key' => env('GEOAPIFY_KEY'),
        'routing_base_url' => env('GEOAPIFY_ROUTING_BASE_URL', 'https://api.geoapify.com/v1/routing'),
        'connect_timeout' => (int) env('GEOAPIFY_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('GEOAPIFY_TIMEOUT', 15),
        'country_code' => env('GEOAPIFY_COUNTRY_CODE', 'ph'),
        'country' => env('GEOAPIFY_COUNTRY', 'Philippines'),
        'region' => env('GEOAPIFY_REGION', 'Ilocos Region'),
        'boundary_geometry' => env('GEOAPIFY_BOUNDARY_GEOMETRY', 'geometry_1000'),
        'boundary_sync_limit' => (int) env('GEOAPIFY_BOUNDARY_SYNC_LIMIT', 20),
    ],

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'connect_timeout' => (int) env('NOMINATIM_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('NOMINATIM_TIMEOUT', 10),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-r1-distill-llama-70b'),
        'fallback_models' => array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            explode(',', (string) env('OPENROUTER_FALLBACK_MODELS', 'openai/gpt-4.1-mini,openai/gpt-4o-mini,deepseek/deepseek-r1-distill-qwen-32b'))
        ))),
        'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 8),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 90),
        'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 450),
    ],

    'agora' => [
        'app_id' => env('AGORA_APP_ID'),
        'app_certificate' => env('AGORA_APP_CERTIFICATE'),
        'token_ttl' => (int) env('AGORA_TOKEN_TTL', 3600),
    ],

    'telnyx' => [
        'api_key' => env('TELNYX_API_KEY'),
        'public_key' => env('TELNYX_PUBLIC_KEY'),
        'verify_webhook_signature' => filter_var(env('TELNYX_VERIFY_WEBHOOK_SIGNATURE', true), FILTER_VALIDATE_BOOL),
        'webhook_tolerance_seconds' => (int) env('TELNYX_WEBHOOK_TOLERANCE_SECONDS', 300),
    ],

    'voice_calls' => [
        'missed_after_seconds' => (int) env('VOICE_CALL_MISSED_AFTER_SECONDS', 90),
        'missed_notification_window_minutes' => (int) env('VOICE_CALL_MISSED_NOTIFICATION_WINDOW_MINUTES', 30),
        'poll_interval_ms' => (int) env('VOICE_CALL_POLL_INTERVAL_MS', 2000),
        'stale_answered_after_seconds' => (int) env('VOICE_CALL_STALE_ANSWERED_AFTER_SECONDS', 3600),
        'max_concurrent_responder_calls' => (int) env('VOICE_CALL_MAX_CONCURRENT_RESPONDER_CALLS', 4),
    ],

    'met_norway' => [
        // MET Norway requires an identifiable application and contact URL.
        'user_agent' => env('MET_NORWAY_USER_AGENT', 'PyroSense/1.0 (+https://pyro-sense.onrender.com)'),
    ],

    'ai_prediction' => [
        'python_binary' => $aiPythonBinary,
        'python_path' => env('AI_PYTHONPATH', ''),
        'prediction_script' => env('AI_PREDICTION_SCRIPT', 'capstone-ai/predict_event.py'),
        'training_script' => env('AI_TRAINING_SCRIPT', 'capstone-ai/train_model.py'),
        'evaluation_script' => env('AI_EVALUATION_SCRIPT', 'capstone-ai/evaluate_model.py'),
        'dataset_path' => env('AI_DATASET_PATH', 'capstone-ai/station_barangay_monthly_db.json'),
        'model_path' => env('AI_MODEL_PATH', 'capstone-ai/fire_model.pkl'),
        'metadata_path' => env('AI_MODEL_METADATA_PATH', 'capstone-ai/fire_model_metadata.json'),
        'evaluation_cache_path' => env('AI_MODEL_EVALUATION_CACHE_PATH', 'storage/app/ai/fire_model_evaluation.json'),
        'prediction_timeout_seconds' => (int) env('AI_PREDICTION_TIMEOUT_SECONDS', 180),
        'prediction_timeout_per_payload_seconds' => (int) env('AI_PREDICTION_TIMEOUT_PER_PAYLOAD_SECONDS', 8),
        'prediction_timeout_max_seconds' => (int) env('AI_PREDICTION_TIMEOUT_MAX_SECONDS', 360),
        'training_timeout_seconds' => (int) env('AI_TRAINING_TIMEOUT_SECONDS', 240),
        'training_queue_connection' => env('AI_TRAINING_QUEUE_CONNECTION', 'database'),
        'training_queue' => env('AI_TRAINING_QUEUE', 'forecast-training'),
        'training_status_cache_store' => env('AI_TRAINING_STATUS_CACHE_STORE', 'database'),
        'failure_email_enabled' => filter_var(env('AI_FAILURE_EMAIL_ENABLED', true), FILTER_VALIDATE_BOOL),
        'artifact_persistence_enabled' => (bool) env('AI_ARTIFACT_PERSISTENCE_ENABLED', true),
        'artifact_disk' => env('AI_ARTIFACT_DISK', env('FILESYSTEM_DISK', 'local')),
        'artifact_prefix' => trim((string) env('AI_ARTIFACT_PREFIX', 'ai/forecast-model'), '/'),
    ],

];
