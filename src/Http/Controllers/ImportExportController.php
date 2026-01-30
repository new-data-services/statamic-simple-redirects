<?php

namespace Ndx\SimpleRedirect\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ndx\SimpleRedirect\Import\ColumnMapper;
use Ndx\SimpleRedirect\Import\CsvExporter;
use Ndx\SimpleRedirect\Import\CsvImporter;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends CpController
{
    public function export(CsvExporter $exporter): StreamedResponse
    {
        $this->authorize('manage redirects');

        return response()->streamDownload(
            fn () => print $exporter->export(),
            $exporter->filename(),
            ['Content-Type' => 'text/csv']
        );
    }

    public function import(Request $request, CsvImporter $importer, ColumnMapper $mapper): JsonResponse
    {
        $this->authorize('manage redirects');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $content = file_get_contents($request->file('file')->path());
        $mapping = $importer->getMapping($content);

        if (! $mapper->hasRequiredColumns($mapping)) {
            Log::warning('Simple Redirects: Import failed - missing required columns (source, destination)', [
                'filename' => $request->file('file')->getClientOriginalName(),
                'mapping'  => $mapping,
            ]);

            return response()->json([
                'error' => __('simple-redirects::messages.required_columns_missing'),
            ], 422);
        }

        $importer->import($content);

        return response()->json(['success' => true]);
    }
}
