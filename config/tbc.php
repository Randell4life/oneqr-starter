<?php

return [
    'api_key' => env('TBC_API_KEY'),
    'client_id' => env('TBC_CLIENT_ID'),
    'client_secret' => env('TBC_CLIENT_SECRET'),
    'enabled' => (bool) env('TBC_ENABLED', false)
        && filled(env('TBC_API_KEY'))
        && filled(env('TBC_CLIENT_ID'))
        && filled(env('TBC_CLIENT_SECRET')),
];
