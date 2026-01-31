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
                                        'display'      => __('simple-redirects::fields.source.title'),
                                        'instructions' => __('simple-redirects::fields.source.instructions'),
                                        'validate'     => ['required', new ValidRedirectSource],
                                    ],
                                ],
                                [
                                    'handle' => 'destination',
                                    'field'  => [
                                        'type'         => 'text',
                                        'display'      => __('simple-redirects::fields.destination.title'),
                                        'instructions' => __('simple-redirects::fields.destination.instructions'),
                                        'validate'     => ['required', new ValidRedirectDestination],
                                    ],
                                ],
                                [
                                    'handle' => 'regex',
                                    'field'  => [
                                        'type'         => 'toggle',
                                        'display'      => __('simple-redirects::fields.regex.title'),
                                        'instructions' => __('simple-redirects::fields.regex.instructions'),
                                        'default'      => false,
                                    ],
                                ],
                                [
                                    'handle' => 'status_code',
                                    'field'  => [
                                        'type'         => 'select',
                                        'display'      => __('simple-redirects::fields.status_code.title'),
                                        'instructions' => __('simple-redirects::fields.status_code.instructions'),
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
                                        'display'      => __('simple-redirects::fields.enabled.title'),
                                        'instructions' => __('simple-redirects::fields.enabled.instructions'),
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
                        'display'      => __('simple-redirects::fields.sites.title'),
                        'instructions' => __('simple-redirects::fields.sites.instructions'),
                        'mode'         => 'select',
                        'default'      => Site::all()->keys()->all(),
                    ],
                ],
            ],
        ];
    }
}
