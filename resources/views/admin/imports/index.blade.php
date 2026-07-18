@extends('layouts.admin')

@section('title', 'Data Imports')

@section('content')
<div class="page-heading">
    <div>
        <p class="page-eyebrow">Bulk data tools</p>
        <h2>Import voters and candidates</h2>
        <p>Upload validated CSV files and review previous import results.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @can('import voters')
            <a class="btn btn-outline-secondary" href="{{ route('admin.imports.template', 'voters') }}">Voter template</a>
        @endcan
        @can('import candidates')
            <a class="btn btn-outline-secondary" href="{{ route('admin.imports.template', 'candidates') }}">Candidate template</a>
        @endcan
    </div>
</div>

@if (session('generated_pins_import_id'))
    <div class="alert alert-primary border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>PINs were generated for new voters. The encrypted report remains available for 24 hours.</span>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.imports.generated-pins', session('generated_pins_import_id')) }}">Download generated PINs</a>
    </div>
@endif

@if ($elections->isEmpty())
    <div class="alert alert-info border-0 shadow-sm">There are no unlocked elections available for import.</div>
@endif

<div class="row g-3 mb-4">
    @can('import voters')
        <div class="col-lg-6">
            <section class="card h-100">
                <div class="card-header">
                    <h3 class="h5 mb-1">Import voters</h3>
                    <p class="small text-muted mb-0">PINs are optional and will be generated for new voters when omitted.</p>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.voters.import') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="voter_election_id">Election</label>
                            <select class="form-select" id="voter_election_id" name="election_id" required @disabled($elections->isEmpty())>
                                <option value="">Choose an election</option>
                                @foreach ($elections as $election)
                                    <option value="{{ $election->id }}" @selected((string) old('election_id') === (string) $election->id)>{{ $election->title }} ({{ ucfirst($election->status) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="voter_file">Voter CSV file</label>
                            <input class="form-control" id="voter_file" type="file" name="file" accept=".csv,text/csv,text/plain" required @disabled($elections->isEmpty())>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="small text-muted">Required values: student ID and full name</span>
                            <button class="btn btn-primary" @disabled($elections->isEmpty())>Import voters</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    @endcan

    @can('import candidates')
        <div class="col-lg-6">
            <section class="card h-100">
                <div class="card-header">
                    <h3 class="h5 mb-1">Import candidates</h3>
                    <p class="small text-muted mb-0">Position names must match positions already configured for the election.</p>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.candidates.import') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="candidate_election_id">Election</label>
                            <select class="form-select" id="candidate_election_id" name="election_id" required @disabled($elections->isEmpty())>
                                <option value="">Choose an election</option>
                                @foreach ($elections as $election)
                                    <option value="{{ $election->id }}" @selected((string) old('election_id') === (string) $election->id)>{{ $election->title }} ({{ ucfirst($election->status) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="candidate_file">Candidate CSV file</label>
                            <input class="form-control" id="candidate_file" type="file" name="file" accept=".csv,text/csv,text/plain" required @disabled($elections->isEmpty())>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="small text-muted">Required values: position and candidate name</span>
                            <button class="btn btn-primary" @disabled($elections->isEmpty())>Import candidates</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    @endcan
</div>

<section class="card">
    <div class="card-header">
        <h3 class="h5 mb-1">Import history</h3>
        <p class="small text-muted mb-0">Download correction reports for failed rows and temporary generated-PIN reports.</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Time</th>
                <th>Type</th>
                <th>File</th>
                <th>Election</th>
                <th class="text-end">Rows</th>
                <th class="text-end">Successful</th>
                <th class="text-end">Failed</th>
                <th>Failed Rows</th>
                <th>Generated PINs</th>
                <th>Imported By</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($imports as $import)
                <tr>
                    <td class="text-nowrap">{{ optional($import->created_at)->format('M d, Y H:i') }}</td>
                    <td>{{ ucfirst($import->import_type) }}</td>
                    <td>{{ $import->filename }}</td>
                    <td>{{ $import->election?->title ?? '-' }}</td>
                    <td class="text-end">{{ $import->total_rows }}</td>
                    <td class="text-end">{{ $import->successful_rows }}</td>
                    <td class="text-end">{{ $import->failed_rows }}</td>
                    <td>
                        @if ($import->failed_rows_path)
                            <a href="{{ route('admin.imports.failed-rows', $import) }}">Download</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($import->generated_pins_expires_at?->isFuture() && $import->generated_pins_path)
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.imports.generated-pins', $import) }}">Download</a>
                            <div class="small text-muted mt-1">Expires {{ $import->generated_pins_expires_at->diffForHumans() }}</div>
                        @elseif ($import->generated_pins_expires_at)
                            <span class="text-muted">Expired</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $import->importedBy?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td class="text-center text-muted py-4" colspan="10">No imports have been recorded yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
{{ $imports->links() }}
@endsection
