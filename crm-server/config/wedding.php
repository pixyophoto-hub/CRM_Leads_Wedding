<?php

return [
    // Static bearer token the dashboard sends on every /api call (Phase 1, local).
    'api_token' => env('API_TOKEN', ''),

    // Secret embedded in the Google webhook URL: /api/webhook/google/{secret}
    'webhook_secret' => env('WEBHOOK_SECRET', ''),
];
