<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Import;
use App\Services\AuditLogger;
use App\Services\CsvWriter;
use App\Services\GeneratedPinReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $canImportVoters = $request->user()->can('import voters');
        $canImportCandidates = $request->user()->can('import candidates');

        return view('admin.imports.index', [
            'elections' => Election::query()
                ->where('status', '!=', 'locked')
                ->latest()
                ->get(),
            'imports' => Import::query()
                ->with(['election', 'importedBy'])
                ->when(! $canImportVoters, fn ($query) => $query->where('import_type', '!=', 'voters'))
                ->when(! $canImportCandidates, fn ($query) => $query->where('import_type', '!=', 'candidates'))
                ->latest('created_at')
                ->paginate(30),
        ]);
    }

    public function template(string $type, CsvWriter $csvWriter): StreamedResponse
    {
        abort_unless(in_array($type, ['voters', 'candidates'], true), 404);

        $headers = $type === 'voters'
            ? ['student_id', 'full_name', 'class_name', 'programme', 'house', 'gender', 'pin']
            : ['position', 'candidate_name', 'student_id', 'class_name', 'programme', 'house', 'gender', 'manifesto'];

        return response()->streamDownload(function () use ($headers, $csvWriter): void {
            $output = fopen('php://output', 'w');
            $csvWriter->writeRow($output, $headers);
            fclose($output);
        }, "{$type}-import-template.csv", ['Content-Type' => 'text/csv']);
    }

    public function failedRows(Request $request, Import $import): Response
    {
        $requiredPermission = match ($import->import_type) {
            'voters' => 'import voters',
            'candidates' => 'import candidates',
            default => null,
        };

        abort_unless($requiredPermission && $request->user()->can($requiredPermission), 403);
        abort_if(blank($import->failed_rows_path) || ! Storage::disk('local')->exists($import->failed_rows_path), 404);

        return Storage::disk('local')->download($import->failed_rows_path);
    }

    public function generatedPins(
        Request $request,
        Import $import,
        AuditLogger $auditLogger,
        GeneratedPinReportService $reportService,
    ): StreamedResponse {
        abort_unless($import->import_type === 'voters' && $import->generated_pins_expires_at, 404);

        if ($import->generated_pins_expires_at->isPast()) {
            $reportService->delete($import);

            abort(410, 'This generated PIN report has expired.');
        }

        abort_unless($reportService->exists($import), 404);

        $contents = $reportService->decryptedContents($import);

        $auditLogger->record(
            $request,
            'voter.generated_pins_downloaded',
            "Downloaded generated voter PINs for import #{$import->id}.",
            $import->election_id,
            'warning',
        );

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            'generated-voter-pins-import-'.$import->id.'.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}
