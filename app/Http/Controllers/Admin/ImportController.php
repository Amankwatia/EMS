<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Import;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __invoke()
    {
        return view('admin.imports.index', [
            'imports' => Import::query()
                ->with(['election', 'importedBy'])
                ->latest('created_at')
                ->paginate(30),
        ]);
    }

    public function template(string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['voters', 'candidates'], true), 404);

        $headers = $type === 'voters'
            ? ['student_id', 'full_name', 'class_name', 'programme', 'house', 'gender', 'pin']
            : ['position', 'candidate_name', 'student_id', 'class_name', 'programme', 'house', 'gender', 'manifesto'];

        return response()->streamDownload(function () use ($headers): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            fclose($output);
        }, "{$type}-import-template.csv", ['Content-Type' => 'text/csv']);
    }

    public function failedRows(Import $import)
    {
        abort_if(blank($import->failed_rows_path) || ! Storage::disk('local')->exists($import->failed_rows_path), 404);

        return Storage::disk('local')->download($import->failed_rows_path);
    }
}
