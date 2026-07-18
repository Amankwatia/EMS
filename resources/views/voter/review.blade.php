@extends('layouts.voter')

@section('content')
<div class="card voter-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-3">Review Your Vote</h1>
        <div class="alert alert-warning">Submission is final. You cannot vote again after this step.</div>
        <dl class="row">
            @foreach ($election->positions as $position)
                <dt class="col-sm-5">{{ $position->name }}</dt>
                <dd class="col-sm-7">
                    @php($choice = $choices[$position->id] ?? null)
                    @php($choiceList = is_array($choice) ? $choice : array_filter([$choice]))
                    @if (in_array('abstain', $choiceList, true))
                        Abstain
                    @elseif (count($choiceList))
                        {{ collect($choiceList)->filter(fn ($candidateId) => $candidates->has((int) $candidateId))->map(fn ($candidateId) => $candidates[(int) $candidateId]->candidate_name)->join(', ') }}
                    @else
                        No selection
                    @endif
                </dd>
            @endforeach
        </dl>
        <form method="POST" action="{{ route('voter.submit') }}">
            @csrf
            <button class="btn btn-success btn-lg w-100">Submit Final Vote</button>
        </form>
        <a class="btn btn-outline-secondary w-100 mt-3" href="{{ route('voter.ballot') }}">Go Back</a>
    </div>
</div>
@endsection
