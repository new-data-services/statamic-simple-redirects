<?php

return [
    'redirects'           => 'Redirects',
    'sites'               => 'Sites',
    'redirect_created'    => 'Redirect created',
    'redirect_saved'      => 'Redirect saved',
    'redirect_deleted'    => 'Redirect deleted',
    'delete_confirmation' => 'Are you sure you want to delete this redirect?',
    'redirects_reordered' => 'Redirects reordered',
    'order_save_failed'   => 'Failed to save order',
    'save_failed'         => 'Failed to save',
    'validation_failed'   => 'Validation failed',

    'instructions' => [
        'source'      => 'The URL path to redirect from. Use * as wildcard (e.g., /blog/*).',
        'destination' => 'The URL to redirect to. Use $1, $2, etc. for captured wildcards.',
        'regex'       => 'Enable regular expression support (advanced).',
        'sites'       => 'Leave empty to apply to all sites, or select specific sites.',
    ],

    'validation' => [
        'blocked_protocol'        => 'The destination URL contains a blocked protocol.',
        'invalid_regex'           => 'The regular expression is invalid.',
        'dangerous_regex_pattern' => 'The regular expression is potentially dangerous.',
    ],
];
