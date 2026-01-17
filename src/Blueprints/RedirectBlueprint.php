<?php

namespace Ndx\SimpleRedirect\Blueprints;

use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as BlueprintInstance;

class RedirectBlueprint
{
    public function __invoke(): BlueprintInstance
    {
        return Blueprint::make()->setContents([
            'tabs' => [
                'main' => [
                    'display'  => __('Main'),
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'source',
                                    'field'  => [
                                        'type'         => 'text',
                                        'display'      => __('Source URL'),
                                        'instructions' => __('The URL path to redirect from (e.g., /old-page)'),
                                        'validate'     => ['required'],
                                    ],
                                ],
                                [
                                    'handle' => 'destination',
                                    'field'  => [
                                        'type'         => 'text',
                                        'display'      => __('Destination URL'),
                                        'instructions' => __('The URL to redirect to (e.g., /new-page or https://example.com)'),
                                        'validate'     => ['required'],
                                    ],
                                ],
                                [
                                    'handle' => 'type',
                                    'field'  => [
                                        'type'         => 'select',
                                        'display'      => __('Match Type'),
                                        'instructions' => __('How the source URL should be matched'),
                                        'options'      => [
                                            'exact' => __('Exact Match'),
                                            'regex' => __('Regular Expression'),
                                        ],
                                        'default'  => 'exact',
                                        'validate' => ['required'],
                                    ],
                                ],
                                [
                                    'handle' => 'status_code',
                                    'field'  => [
                                        'type'         => 'select',
                                        'display'      => __('Status Code'),
                                        'instructions' => __('HTTP status code for the redirect'),
                                        'options'      => [
                                            '301' => '301 - ' . __('Permanent'),
                                            '302' => '302 - ' . __('Temporary'),
                                            '410' => '410 - ' . __('Gone'),
                                        ],
                                        'default'  => '301',
                                        'validate' => ['required'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sidebar' => [
                    'display'  => __('Sidebar'),
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'enabled',
                                    'field'  => [
                                        'type'    => 'toggle',
                                        'display' => __('Enabled'),
                                        'default' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])->setHandle('redirect');
    }
}
