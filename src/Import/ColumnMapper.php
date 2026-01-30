<?php

namespace Ndx\SimpleRedirect\Import;

class ColumnMapper
{
    protected array $columnMap = [
        'source'      => ['source', 'from', 'old_url', 'url', 'path'],
        'destination' => ['destination', 'to', 'new_url', 'redirect', 'target'],
        'status_code' => ['status_code', 'type', 'code', 'http_code', 'redirect_type'],
        'enabled'     => ['enabled', 'active', 'is_enabled'],
        'regex'       => ['regex', 'is_regex', 'pattern'],
        'sites'       => ['sites', 'site'],
        'order'       => ['order'],
        'match_type'  => ['match_type'],
    ];

    public function map(array $headers): array
    {
        $mapping = [];

        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));

            foreach ($this->columnMap as $field => $aliases) {
                if (in_array($normalized, $aliases)) {
                    $mapping[$header] = $field;

                    break;
                }
            }
        }

        return $mapping;
    }

    public function hasRequiredColumns(array $mapping): bool
    {
        $mapped = array_values($mapping);

        return in_array('source', $mapped) && in_array('destination', $mapped);
    }
}
