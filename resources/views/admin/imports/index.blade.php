@extends('layouts.admin')

@section('title', 'Import History')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.imports.template', 'voters') }}">Voter CSV Template</a>
    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.imports.template', 'candidates') }}">Candidate CSV Template</a>
</div>
<div class="card border-0 shadow-sm">
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
                <th>Imported By</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($imports as $import)
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
                    <td>{{ $import->importedBy?->name ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $imports->links() }}
@endsection
