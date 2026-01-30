<?php

namespace Ndx\SimpleRedirect\Import;

use Illuminate\Support\Facades\Log;
use League\Csv\Info;
use League\Csv\Reader;
use Ndx\SimpleRedirect\Facades\Redirect;
use Statamic\Facades\Site;

class CsvImporter
{
    public function __construct(
        protected ColumnMapper $mapper
    ) {}

    public function getMapping(string $content): array
    {
        $reader = $this->createReader($content);

        return $this->mapper->map($reader->getHeader());
    }

    public function import(string $content): void
    {
        $reader         = $this->createReader($content);
        $mapping        = $this->mapper->map($reader->getHeader());
        $availableSites = Site::all()->keys()->all();

        $existingRedirects = Redirect::all()
            ->map(fn ($redirect) => $redirect->source() . '|' . $redirect->destination())
            ->toArray();

        $rows = array_map(fn ($record) => $this->transformRow($record, $mapping, $availableSites), iterator_to_array($reader->getRecords()));
        $rows = $this->sortByOrderColumn($rows, $mapping);

        foreach ($rows as $row) {
            if (empty($row['source']) || empty($row['destination'])) {
                Log::info('Simple Redirects Import: Skipped row - missing source or destination', [
                    'source'      => $row['source'] ?? null,
                    'destination' => $row['destination'] ?? null,
                ]);

                continue;
            }

            if (in_array($row['source'] . '|' . $row['destination'], $existingRedirects)) {
                Log::info('Simple Redirects Import: Skipped row - already exists', [
                    'source'      => $row['source'] ?? null,
                    'destination' => $row['destination'] ?? null,
                ]);

                continue;
            }

            $this->createRedirect($row);
        }
    }

    protected function createReader(string $content): Reader
    {
        $reader = Reader::fromString($content);

        $stats = Info::getDelimiterStats($reader, [',', ';', "\t", '|'], 10);

        if (! empty($stats)) {
            $reader->setDelimiter((string) array_key_first($stats));
        }

        $reader->setHeaderOffset(0);

        return $reader;
    }

    protected function transformRow(array $record, array $mapping, array $availableSites): array
    {
        $row = [
            'source'      => null,
            'destination' => null,
            'status_code' => 301,
            'enabled'     => true,
            'regex'       => false,
            'sites'       => null,
            'order'       => null,
        ];

        foreach ($record as $header => $value) {
            $field = $mapping[$header] ?? null;

            if (! $field) {
                continue;
            }

            $row[$field] = match ($field) {
                'enabled'     => in_array(strtolower($value), ['1', 'true', 'yes']),
                'regex'       => in_array(strtolower($value), ['1', 'true', 'yes']),
                'status_code' => (int) $value ?: 301,
                'sites'       => $this->filterValidSites($value, $availableSites),
                'order'       => $value !== '' ? (int) $value : null,
                'match_type'  => $row['regex'] = strtolower($value) === 'regex',
                default       => $value,
            };
        }

        return $row;
    }

    protected function createRedirect(array $row): void
    {
        $redirect = Redirect::make()
            ->source($row['source'])
            ->destination($row['destination'])
            ->statusCode($row['status_code'])
            ->enabled($row['enabled'])
            ->regex($row['regex'])
            ->sites($row['sites']);

        Redirect::save($redirect);
    }

    protected function sortByOrderColumn(array $rows, array $mapping): array
    {
        if (! in_array('order', array_values($mapping))) {
            return $rows;
        }

        usort($rows, fn ($a, $b) => ($a['order'] ?? PHP_INT_MAX) <=> ($b['order'] ?? PHP_INT_MAX));

        return $rows;
    }

    protected function filterValidSites(?string $value, array $availableSites): ?array
    {
        if (! $value) {
            return null;
        }

        $requestedSites = array_map('trim', explode(',', $value));
        $validSites     = array_values(array_intersect($requestedSites, $availableSites));

        return ! empty($validSites) ? $validSites : null;
    }
}
