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
    | Stache Stores (File Driver)
    |--------------------------------------------------------------------------
    |
    | Configure the directories for the Stache stores used by the Simple
    | Redirects addon when using the file driver. Each key is the store name,
    | and the value is the directory path where the store files will be stored.
    |
    */

    'stores' => [
        'redirects'      => base_path('content/redirects'),
        'redirects-tree' => base_path('content/trees/redirects'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration (Eloquent Driver)
    |--------------------------------------------------------------------------
    |
    | Database connection and table name for the eloquent driver. When
    | connection is null, the default Laravel database connection is used.
    |
    */

    'database' => [
        'connection' => env('REDIRECTS_DB_CONNECTION'),
        'table'      => env('REDIRECTS_TABLE', 'redirects'),
    ],

];
