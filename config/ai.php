<?php

return [

    'default_provider' => env('AI_PROVIDER', 'gemini'),

    'api_key' => env('AI_API_KEY', ''),

    'models' => [
        'vision' => env('AI_VISION_MODEL', 'gemini-3.5-flash'),
        'text'   => env('AI_TEXT_MODEL',   'gemini-3.5-flash'),
    ],

    'allowed_image_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],

];
