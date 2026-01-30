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
                                        'instructions' => __('simple-redirects::messages.instructions.status_code'),
                                        'options'      => [
                                            '301' => '301 - ' . __('Permanent'),
                                            '302' => '302 - ' . __('Temporary'),
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
                    'sections' => array_filter([
                        [
                            'fields' => [
                                [
                                    'handle' => 'enabled',
                                    'field'  => [
                                        'type'         => 'toggle',
                                        'display'      => __('Enabled'),
                                        'instructions' => __('simple-redirects::messages.instructions.enabled'),
                                        'default'      => true,
                                    ],
                                ],
                            ],
                        ],
                        $this->sitesSection(),
                    ]),
                ],
            ],
        ])->setHandle('redirect');
    }

    protected function sitesSection(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        return [
            'fields' => [
                [
                    'handle' => 'sites',
                    'field'  => [
                        'type'         => 'sites',
                        'display'      => __('Sites'),
                        'instructions' => __('simple-redirects::messages.instructions.sites'),
                        'mode'         => 'select',
                        'default'      => Site::all()->keys()->all(),
                    ],
                ],
            ],
        ];
    }
}
