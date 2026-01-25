<?php

namespace Ndx\SimpleRedirect\Blueprints;

use Ndx\SimpleRedirect\Rules\ValidRedirectDestination;
use Ndx\SimpleRedirect\Rules\ValidRedirectSource;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
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
                                        'instructions' => __('simple-redirects::messages.instructions.source'),
                                        'validate'     => ['required', new ValidRedirectSource],
                                    ],
                                ],
                                [
                                    'handle' => 'destination',
                                    'field'  => [
                                        'type'         => 'text',
                                        'display'      => __('Destination URL'),
                                        'instructions' => __('simple-redirects::messages.instructions.destination'),
                                        'validate'     => ['required', new ValidRedirectDestination],
                                    ],
                                ],
                                [
                                    'handle' => 'regex',
                                    'field'  => [
                                        'type'         => 'toggle',
                                        'display'      => __('Regex'),
                                        'instructions' => __('simple-redirects::messages.instructions.regex'),
                                        'default'      => false,
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
                            'fields' => array_values(array_filter([
                                [
                                    'handle' => 'enabled',
                                    'field'  => [
                                        'type'    => 'toggle',
                                        'display' => __('Enabled'),
                                        'default' => true,
                                    ],
                                ],
                                $this->sitesField(),
                            ])),
                        ],
                    ],
                ],
            ],
        ])->setHandle('redirect');
    }

    protected function sitesField(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        return [
            'handle' => 'sites',
            'field'  => [
                'type'    => 'sites',
                'display' => __('simple-redirects::messages.sites'),
                'mode'    => 'select',
            ],
        ];
    }
}
