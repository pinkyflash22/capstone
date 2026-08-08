<?php

return [

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
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'deepseek/deepseek-r1-distill-llama-70b'),
        'fallback_models' => array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            explode(',', (string) env('OPENROUTER_FALLBACK_MODELS', 'openai/gpt-4.1-mini,openai/gpt-4o-mini,deepseek/deepseek-r1-distill-qwen-32b'))
        ))),
        'connect_timeout' => (int) env('OPENROUTER_CONNECT_TIMEOUT', 8),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 20),
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
        'poll_interval_ms' => (int) env('VOICE_CALL_POLL_INTERVAL_MS', 2000),
        'stale_answered_after_seconds' => (int) env('VOICE_CALL_STALE_ANSWERED_AFTER_SECONDS', 180),
    ],

    'ai_prediction' => [
        'python_binary' => env('AI_PYTHON_BINARY', 'python'),
        'prediction_script' => env('AI_PREDICTION_SCRIPT', 'capstone-ai/predict_event.py'),
        'training_script' => env('AI_TRAINING_SCRIPT', 'capstone-ai/train_model.py'),
        'dataset_path' => env('AI_DATASET_PATH', 'capstone-ai/station_barangay_monthly_db.json'),
        'model_path' => env('AI_MODEL_PATH', 'capstone-ai/fire_model.pkl'),
        'metadata_path' => env('AI_MODEL_METADATA_PATH', 'capstone-ai/fire_model_metadata.json'),
    ],

];
