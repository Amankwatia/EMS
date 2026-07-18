<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\SystemSetting;
use App\Models\Voter;
use App\Services\ElectionResultService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function resultsPdf(Request $request, Election $election, ElectionResultService $resultService): Response
    {
        $this->ensureResultsCanBeExported($election);
        $this->auditExport($request, $election, 'results PDF');
        $payload = $resultService->payload($election) + $this->reportSettings();

        return Pdf::loadView('admin.reports.results-pdf', $payload)
            ->download(str($election->title)->slug().'-results.pdf');
    }

    public function resultsCsv(Request $request, Election $election, ElectionResultService $resultService): StreamedResponse
    {
        $this->ensureResultsCanBeExported($election);
        $this->auditExport($request, $election, 'results CSV');
        $payload = $resultService->payload($election);

        return response()->streamDownload(function () use ($payload): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Position', 'Candidate', 'Votes', 'Percentage', 'Winner/Tie']);

            foreach ($payload['positions'] as $position) {
                foreach ($payload['results'][$position->id]['candidateCounts'] as $row) {
                    fputcsv($output, [
                        $position->name,
                        $row['candidate']->candidate_name,
                        $row['votes'],
                        $row['percentage'].'%',
                        $payload['results'][$position->id]['isTie'] ? 'Tie' : ($payload['results'][$position->id]['winner']?->id === $row['candidate']->id ? 'Winner' : ''),
                    ]);
                }
            }

            fclose($output);
        }, str($election->title)->slug().'-results.csv', ['Content-Type' => 'text/csv']);
    }

    public function votersCsv(Request $request, Election $election): StreamedResponse
    {
        $this->auditExport($request, $election, 'voter CSV');

        return response()->streamDownload(function () use ($election): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Student ID', 'Full Name', 'Class', 'Programme', 'House', 'Gender', 'Eligible', 'Voted', 'Voted At']);

            Voter::where('election_id', $election->id)->orderBy('student_id')->each(function (Voter $voter) use ($output): void {
                fputcsv($output, [
                    $voter->student_id,
                    $voter->full_name,
                    $voter->class_name,
                    $voter->programme,
                    $voter->house,
                    $voter->gender,
                    $voter->is_eligible ? 'Yes' : 'No',
                    $voter->has_voted ? 'Yes' : 'No',
                    optional($voter->voted_at)->toDateTimeString(),
                ]);
            });

            fclose($output);
        }, str($election->title)->slug().'-voters.csv', ['Content-Type' => 'text/csv']);
    }

    public function votedStatusCsv(Request $request, Election $election): StreamedResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:voted,not_voted'],
        ]);
        $status = $data['status'] ?? null;
        $label = match ($status) {
            'voted' => 'voted students CSV',
            'not_voted' => 'students who have not voted CSV',
            default => 'voted status CSV',
        };
        $this->auditExport($request, $election, $label);

        return response()->streamDownload(function () use ($election, $status): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Status', 'Student ID', 'Full Name', 'Class', 'Programme', 'House', 'Gender', 'Eligible', 'Voted At']);

            Voter::where('election_id', $election->id)
                ->when($status !== null, fn ($query) => $query->where('has_voted', $status === 'voted'))
                ->orderBy('has_voted')
                ->orderBy('student_id')
                ->each(function (Voter $voter) use ($output): void {
                    fputcsv($output, [
                        $voter->has_voted ? 'Voted' : 'Not Voted',
                        $voter->student_id,
                        $voter->full_name,
                        $voter->class_name,
                        $voter->programme,
                        $voter->house,
                        $voter->gender,
                        $voter->is_eligible ? 'Yes' : 'No',
                        optional($voter->voted_at)->toDateTimeString(),
                    ]);
                });

            fclose($output);
        }, str($election->title)->slug().'-'.($status ? str_replace('_', '-', $status) : 'voted-status').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function candidatesCsv(Request $request, Election $election): StreamedResponse
    {
        $this->auditExport($request, $election, 'candidate CSV');

        return response()->streamDownload(function () use ($election): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Position', 'Candidate', 'Student ID', 'Class', 'Programme', 'House', 'Gender', 'Status']);

            $election->candidates()->with('position')->orderBy('position_id')->orderBy('display_order')->each(function ($candidate) use ($output): void {
                fputcsv($output, [
                    $candidate->position->name,
                    $candidate->candidate_name,
                    $candidate->student_id,
                    $candidate->class_name,
                    $candidate->programme,
                    $candidate->house,
                    $candidate->gender,
                    $candidate->status,
                ]);
            });

            fclose($output);
        }, str($election->title)->slug().'-candidates.csv', ['Content-Type' => 'text/csv']);
    }

    public function turnoutPdf(Request $request, Election $election): Response
    {
        $this->auditExport($request, $election, 'turnout PDF');

        $registeredVoters = Voter::where('election_id', $election->id)->count();
        $eligibleVoters = Voter::where('election_id', $election->id)->where('is_eligible', true)->count();
        $voted = Voter::where('election_id', $election->id)->where('has_voted', true)->count();
        $notVoted = Voter::where('election_id', $election->id)->where('has_voted', false)->count();
        $turnout = $registeredVoters > 0 ? round(($voted / $registeredVoters) * 100, 1) : 0;
        $byClass = Voter::query()
            ->selectRaw('class_name, count(*) as registered, sum(case when has_voted = 1 then 1 else 0 end) as voted')
            ->where('election_id', $election->id)
            ->groupBy('class_name')
            ->orderBy('class_name')
            ->get();
        $reportSettings = $this->reportSettings();

        return Pdf::loadView('admin.reports.turnout-pdf', compact('election', 'registeredVoters', 'eligibleVoters', 'voted', 'notVoted', 'turnout', 'byClass') + $reportSettings)
            ->download(str($election->title)->slug().'-turnout.pdf');
    }

    private function ensureResultsCanBeExported(Election $election): void
    {
        abort_unless($election->resultsViewableByAdmins(), 403);
    }

    private function auditExport(Request $request, Election $election, string $reportName): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'election_id' => $election->id,
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'report.exported',
            'description' => "Exported {$reportName} for {$election->title}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function reportSettings(): array
    {
        $settings = SystemSetting::query()->pluck('value', 'key');
        $logoPath = $settings['school_logo_path'] ?? null;
        $logoAbsolutePath = $logoPath ? public_path('storage/'.$logoPath) : null;

        return [
            'schoolName' => $settings['school_name'] ?? config('app.name'),
            'schoolLogoPath' => $logoAbsolutePath && file_exists($logoAbsolutePath) ? $logoAbsolutePath : null,
        ];
    }
}
