<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | App credentials (Instagram app / client)
    |--------------------------------------------------------------------------
    */
    'app_id' => env('INSTAGRAM_APP_ID', ''),
    'app_secret' => env('INSTAGRAM_APP_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Access token & account
    |--------------------------------------------------------------------------
    |
    | The long-lived access token and (optionally) the Instagram user id. When
    | the user id is empty, "me" is used.
    |
    */
    'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
    'ig_user_id' => env('INSTAGRAM_USER_ID'),

    /*
    |--------------------------------------------------------------------------
    | OAuth
    |--------------------------------------------------------------------------
    */
    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | The verify token you configured for the webhook subscription. The
    | X-Hub-Signature-256 header is validated with the app secret above.
    |
    */
    'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Endpoints & version (advanced)
    |--------------------------------------------------------------------------
    */
    'graph_url' => env('INSTAGRAM_GRAPH_URL', 'https://graph.instagram.com'),
    'authorize_url' => env('INSTAGRAM_AUTHORIZE_URL', 'https://www.instagram.com/oauth/authorize'),
    'token_url' => env('INSTAGRAM_TOKEN_URL', 'https://api.instagram.com/oauth/access_token'),
    'version' => env('INSTAGRAM_API_VERSION', 'v23.0'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('INSTAGRAM_TIMEOUT', 30),
];
