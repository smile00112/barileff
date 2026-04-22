<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Internal base URL for cache warm-up
    |--------------------------------------------------------------------------
    |
    | When warming up the FPC cache, HTTP requests are made to the application
    | itself. In Docker or environments where the public domain is not reachable
    | from inside the container, set this to the internal service address, e.g.:
    |   FPC_INTERNAL_URL=http://app
    |   FPC_INTERNAL_URL=http://127.0.0.1
    |
    | Falls back to APP_URL if not set.
    |
    */

    'internal_url' => env('FPC_INTERNAL_URL', env('APP_URL')),

];
