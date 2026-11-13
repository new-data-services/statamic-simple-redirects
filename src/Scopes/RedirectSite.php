<?php

namespace Ndx\SimpleRedirect\Scopes;

use Statamic\Facades\Site;
use Statamic\Query\Scopes\Filter;

class RedirectSite extends Filter
{
    public $pinned = true;

    public static function title()
    {
        return __('Site');
    }

    public function fieldItems()
    {
        return [
            'sites' => [
                'type'    => 'checkboxes',
                'options' => $this->options(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        if (empty($values['sites'])) {
            return;
        }

        $query->where(fn ($query) => $query
            ->whereNull('sites')
            ->orWhereJsonLength('sites', 0)
            ->orWhereJsonOverlaps('sites', $values['sites']));
    }

    public function badge($values)
    {
        return collect($this->options())->only($values['sites'] ?? [])->implode(', ') ?: null;
    }

    public function visibleTo($key)
    {
        return $key === 'redirects' && Site::multiEnabled();
    }

    protected function options(): array
    {
        return Site::all()->mapWithKeys(fn ($site) => [$site->handle() => $site->name()])->all();
    }
}
