<?php

namespace Ndx\SimpleRedirect\Scopes;

use Statamic\Query\Scopes\Filter;

class RedirectStatus extends Filter
{
    public $pinned = true;

    public static function title()
    {
        return __('simple-redirects::messages.filter_status');
    }

    public function fieldItems()
    {
        return [
            'status' => [
                'type'    => 'radio',
                'options' => $this->options(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        $query->where('is_enabled', $values['status'] === 'enabled');
    }

    public function badge($values)
    {
        return $this->options()[$values['status']] ?? null;
    }

    public function visibleTo($key)
    {
        return $key === 'redirects';
    }

    protected function options(): array
    {
        return [
            'enabled'  => __('simple-redirects::messages.filter_status_enabled'),
            'disabled' => __('simple-redirects::messages.filter_status_disabled'),
        ];
    }
}
