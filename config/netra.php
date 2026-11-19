<?php

return [
    'api_key' => env('NETRA_API_KEY', ''),
    'base_url' => env('NETRA_API_URL', 'https://api.netra.ai/v1'),
    'model' => env('NETRA_MODEL', 'netra-chat-v1'),
    'timeout' => (int) env('NETRA_TIMEOUT', 15),
];
