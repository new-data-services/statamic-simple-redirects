<?php

namespace Ndx\SimpleRedirect\Stache;

use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Symfony\Component\Finder\SplFileInfo;

class RedirectStore extends BasicStore
{
    public function key(): string
    {
        return 'redirects';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        return $file->getExtension() === 'md';
    }

    public function makeItemFromFile($path, $contents)
    {
        $data = YAML::parse($contents);
        $id   = pathinfo($path, PATHINFO_FILENAME);

        $redirect = new RedirectData;
        $redirect->id($id);

        if (isset($data['source'])) {
            $redirect->source($data['source']);
        }

        if (isset($data['destination'])) {
            $redirect->destination($data['destination']);
        }

        if (isset($data['type'])) {
            $redirect->type($data['type']);
        }

        if (isset($data['status_code'])) {
            $redirect->statusCode($data['status_code']);
        }

        if (isset($data['site'])) {
            $redirect->site($data['site']);
        }

        return $redirect;
    }
}
