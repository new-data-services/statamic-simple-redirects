<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the redirect functionality. When disabled, the addon
    | will not boot and no redirects will be processed.
    |
    */

    'enabled' => env('REDIRECTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Redirect Driver
    |--------------------------------------------------------------------------
    |
    | Configure how redirects should be stored. By default, redirects are
    | stored as flat files. You can switch to database storage by changing
    | the driver to 'eloquent'.
    |
    | Supported: "file", "eloquent"
    |
    */

    'driver' => env('REDIRECTS_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Flat File Path
    |--------------------------------------------------------------------------
    |
    | When using the flat file driver, this is the path where redirect files
    | will be stored.
    |
    */

    'path' => base_path('content/redirects'),

];
