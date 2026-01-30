<?php

namespace Ndx\SimpleRedirect\Import;

use League\Csv\Writer;
use Ndx\SimpleRedirect\Facades\Redirect;

class CsvExporter
{
    protected array $columns = [
        'source',
        'destination',
        'status_code',
        'enabled',
        'regex',
        'sites',
    ];

    public function export(): string
    {
        $writer = Writer::fromString('');
        $writer->insertOne($this->columns);

        foreach (Redirect::ordered() as $redirect) {
            $writer->insertOne([
                $redirect->source(),
                $redirect->destination(),
                $redirect->statusCode(),
                $redirect->isEnabled() ? '1' : '0',
                $redirect->isRegex() ? '1' : '0',
                $redirect->sites() ? implode(',', $redirect->sites()) : '',
            ]);
        }

        return $writer->toString();
    }

    public function filename(): string
    {
        return 'redirects-' . date('Y-m-d') . '.csv';
    }
}
